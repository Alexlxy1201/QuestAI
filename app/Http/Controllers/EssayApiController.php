<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Shared\Html;

class EssayApiController extends Controller
{
    /**
     * OCR endpoint (image/pdf -> text). Non-persistent.
     */
    public function ocr(Request $request)
    {
        $data = $request->validate([
            'file' => ['required','file','mimetypes:image/jpeg,image/png,image/webp,application/pdf'],
            'max_pages' => ['nullable','integer','min:1','max:10'],
            'mode' => ['nullable','string']
        ]);
        $maxPages = $data['max_pages'] ?? 3;

        $path = $request->file('file')->store('essays/uploads');
        $abs  = Storage::path($path);
        $mime = $request->file('file')->getMimeType();

        $text  = '';
        $pages = 0;

        if ($mime === 'application/pdf') {
            // try text layer first
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf    = $parser->parseFile($abs);
                    $text   = trim($pdf->getText() ?? '');
                } catch (\Throwable $e) { $text = ''; }
            }

            // if empty, try imagick + OCR (vision API)
            if ($text === '') {
                if (!class_exists(\Imagick::class)) {
                    return response()->json([
                        'ok'=>false,
                        'error'=>'PDF looks scanned. Please enable Imagick to OCR scanned PDFs.',
                    ], 400);
                }
                $im = new \Imagick();
                $im->setResolution(200, 200);
                $im->readImage($abs);
                $n = min($im->getNumberImages(), $maxPages);
                $i = 0;
                $collected = '';
                foreach ($im as $page) {
                    if ($i++ >= $n) break;
                    $page->setImageFormat('png');
                    $blob = $page->getImageBlob();
                    $collected .= ($collected ? "\n\n" : "") . $this->visionOCR($blob, 'image/png');
                }
                $text = $collected;
                $pages = $n;
                $im->clear(); $im->destroy();
            }
        } else {
            $blob  = file_get_contents($abs);
            $text  = $this->visionOCR($blob, $mime);
            $pages = 1;
        }

        return response()->json(['ok'=>true,'text'=>$text,'pages'=>$pages]);
    }

    /**
     * Grade endpoint: call LLM to produce structured scores + rationales/suggestions.
     */
    public function grade(Request $request)
    {
        $payload = $request->validate([
            'title'  => ['nullable','string'],
            'rubric' => ['required','string'],
            'text'   => ['required','string'],
        ]);

        $apiKey = env('OPENAI_API_KEY');
        $model  = env('OPENAI_MODEL', 'gpt-4o-mini');
        $base   = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        if (!$apiKey) return response()->json(['ok'=>false,'error'=>'OPENAI_API_KEY missing'], 500);

        $system = <<<SYS
You are an SPM/UASA essay grader.
Score strictly on 4 dimensions (0–5 each):
- Content
- Communicative Achievement
- Organisation
- Language
Total = sum of four (0–20).
Return JSON only with keys: scores{content,communicative,organisation,language,total}, rationales[], suggestions[], inline_diff_html (if possible), original_text (optional), corrected_text (optional).
Keep rationales concrete and short.
SYS;

        $user = "RUBRIC={$payload['rubric']}\nTITLE={$payload['title']}\n\nESSAY:\n".$payload['text'];

        $res = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$base}/chat/completions", [
            'model' => $model,
            'messages' => [
                ['role'=>'system','content'=>$system],
                ['role'=>'user','content'=>$user],
            ],
            'temperature' => 0.2,
            'response_format' => ['type'=>'json_object'],
        ]);

        if (!$res->successful()) {
            return response()->json(['ok'=>false,'error'=>'OpenAI grade request failed','details'=>$res->json()], 500);
        }

        $content = $res->json('choices.0.message.content') ?? '{}';
        $parsed  = json_decode($content, true) ?: [];

        // Normalize fields
        $scores = $parsed['scores'] ?? [
            'content'=>null,'communicative'=>null,'organisation'=>null,'language'=>null,'total'=>null
        ];
        $rationales = $parsed['rationales'] ?? $parsed['explanations'] ?? [];
        $suggestions = $parsed['suggestions'] ?? [];
        $inline = $parsed['inline_diff_html'] ?? $parsed['inline_diff'] ?? '';

        // Return structured JSON
        $report = [
            'title' => $payload['title'] ?? '',
            'rubric_text' => $payload['rubric'] ?? '',
            'scores' => $scores,
            'rationales' => $rationales,
            'suggestions' => $suggestions,
            'inline_diff_html' => $inline,
        ];

        return response()->json(['ok'=>true,'report'=>$report]);
    }

    /**
     * Direct extract + correct endpoint (file or text -> extracted,corrected,explanations).
     */
    public function directCorrect(Request $request)
    {
        $data = $request->validate([
            'text'        => ['nullable','string'],
            'file'        => ['nullable','file','mimetypes:image/jpeg,image/png,image/webp,application/pdf'],
            'max_pages'   => ['nullable','integer','min:1','max:10'],
            'make_docx'   => ['nullable','boolean'],
            'title'       => ['nullable','string'],
        ]);

        if (!$request->hasFile('file') && !isset($data['text'])) {
            return response()->json(['ok'=>false, 'error'=>'Provide either text or file'], 422);
        }

        $apiKey = env('OPENAI_API_KEY');
        $model  = env('OPENAI_MODEL', 'gpt-4o-mini');
        $base   = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        if (!$apiKey) return response()->json(['ok'=>false,'error'=>'OPENAI_API_KEY missing'], 500);

        $extracted = null;
        $corrected = null;
        $explainArr = [];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();
            $path = $file->store('essays/uploads');
            $abs  = Storage::path($path);

            if ($mime === 'application/pdf') {
                $textLayer = '';
                if (class_exists(\Smalot\PdfParser\Parser::class)) {
                    try {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf    = $parser->parseFile($abs);
                        $textLayer = trim($pdf->getText() ?? '');
                    } catch (\Throwable $e) { /* ignore */ }
                }
                if ($textLayer !== '') {
                    $extracted = $textLayer;
                } elseif (class_exists(\Imagick::class)) {
                    $maxPages = $data['max_pages'] ?? 3;
                    $im = new \Imagick();
                    $im->setResolution(200, 200);
                    $im->readImage($abs);
                    $n = min($im->getNumberImages(), $maxPages);
                    $images = [];
                    $i = 0;
                    foreach ($im as $page) {
                        if ($i++ >= $n) break;
                        $page->setImageFormat('png');
                        $images[] = $page->getImageBlob();
                    }
                    $im->clear(); $im->destroy();

                    $result = $this->imageExtractAndCorrect($images, $apiKey, $model, $base);
                    $extracted  = $result['extracted'] ?? '';
                    $corrected  = $result['corrected'] ?? '';
                    $explainArr = $result['explanations'] ?? [];
                } else {
                    return response()->json([
                        'ok'=>false,
                        'error'=>'PDF has no text layer. Install Imagick to process scanned PDFs.',
                    ], 400);
                }
            } else {
                $blob = file_get_contents($abs);
                $result = $this->imageExtractAndCorrect([$blob], $apiKey, $model, $base);
                $extracted  = $result['extracted'] ?? '';
                $corrected  = $result['corrected'] ?? '';
                $explainArr = $result['explanations'] ?? [];
            }
        }

        if ($extracted === null) {
            $extracted = $data['text'] ?? '';
        }

        if ($corrected === null) {
            $prompt = <<<PROMPT
You are an English grammar and clarity corrector.
Please correct/improve the text and explain the changes.
Return JSON with keys:
{
  "extracted": "<the original text you receive>",
  "corrected": "<improved text>",
  "explanations": ["pointwise explanations ..."]
}
Text:
"""{$extracted}"""
PROMPT;

            $res = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post("{$base}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role'=>'system','content'=>'You are an accurate English writing corrector.'],
                    ['role'=>'user','content'=>$prompt],
                ],
                'temperature' => 0.2,
                'response_format' => ['type'=>'json_object'],
            ]);

            if (!$res->successful()) {
                return response()->json([
                    'ok'=>false,
                    'error'=>'OpenAI correct request failed',
                    'details'=>$res->json() ?: ['status'=>$res->status()],
                ], 500);
            }

            $content = $res->json('choices.0.message.content') ?? '{}';
            $parsed  = json_decode($content, true) ?: [];
            $extracted  = $parsed['extracted'] ?? $extracted;
            $corrected  = $parsed['corrected'] ?? '';
            $explainArr = $parsed['explanations'] ?? [];
        }

        $resp = [
            'ok'           => true,
            'extracted'    => $extracted,
            'corrected'    => $corrected,
            'explanations' => $explainArr,
        ];

        if (!empty($data['make_docx'])) {
            $resp['docx_url'] = $this->buildDocxAndGetUrl($data['title'] ?? 'Essay Report', $extracted, $corrected, $explainArr);
        }

        return response()->json($resp);
    }

    /**
     * EXPORT DOCX (server route) — CHANGED to return structured JSON report
     * Front-end will POST data and expect JSON { ok:true, report: { ... } }.
     * This avoids server-side docx streaming issues across platforms.
     */
    public function exportDocx(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable','string'],
            'rubric_text' => ['nullable','string'],
            'rubric' => ['nullable','string'],
            'extracted' => ['nullable','string'],
            'corrected' => ['nullable','string'],
            'scores' => ['nullable'],
            'explanations' => ['nullable'],
            'suggestions' => ['nullable'],
            'inline_diff_html' => ['nullable','string'],
        ]);

        // Normalize report
        $report = [
            'title' => trim($data['title'] ?? 'Essay Report'),
            'rubric_text' => $data['rubric_text'] ?? $data['rubric'] ?? '',
            'scores' => is_array($data['scores'] ?? null) ? $data['scores'] : [],
            // rationales/explanations
            'rationales' => is_array($data['explanations'] ?? null) ? $data['explanations'] : (is_array($data['rationales'] ?? null) ? $data['rationales'] : []),
            // suggestions included in report object but front-end docx generation will omit them from final docx per requirement
            'suggestions' => is_array($data['suggestions'] ?? null) ? $data['suggestions'] : [],
            'inline_diff_html' => $data['inline_diff_html'] ?? ($data['diffHtml'] ?? ''),
            'original_text' => $data['extracted'] ?? '',
            'corrected' => $data['corrected'] ?? '',
        ];

        return response()->json(['ok' => true, 'report' => $report]);
    }

    /**
     * Smoke test: server-side docx generation OK
     */
    public function exportDocxSmoke()
    {
        $w = new PhpWord();
        $s = $w->addSection();
        $s->addTitle('Smoke OK', 1);
        $s->addText('✅ Railway runtime and PhpWord work fine.');

        return response()->streamDownload(function () use ($w) {
            IOFactory::createWriter($w, 'Word2007')->save('php://output');
        }, 'smoke-test.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="smoke-test.docx"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Lower-level vision OCR using LLM multimodal (image embedded as data URI).
     */
    private function visionOCR(string $blob, string $mime): string
    {
        $apiKey = env('OPENAI_API_KEY');
        $model  = env('OPENAI_MODEL', 'gpt-4o-mini');
        $base   = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        if (!$apiKey) throw new \RuntimeException('OPENAI_API_KEY missing');

        $b64 = base64_encode($blob);
        $payload = [
            'model' => $model,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type'=>'text','text'=>"Extract all readable text. Return plain text only."],
                    ['type'=>'image_url','image_url'=>['url'=>"data:{$mime};base64,{$b64}"]],
                ],
            ]],
            'temperature' => 0.0,
        ];

        $res = Http::withHeaders([
            'Authorization'=>"Bearer {$apiKey}",
            'Content-Type'=>'application/json',
        ])->post("{$base}/chat/completions", $payload);

        if (!$res->successful()) {
            throw new \RuntimeException('OpenAI OCR failed: '.json_encode($res->json()));
        }
        return trim($res->json('choices.0.message.content') ?? '');
    }

    /**
     * Use LLM to extract + correct from an array of image blobs (binary strings)
     */
    private function imageExtractAndCorrect(array $imageBlobs, string $apiKey, string $model, string $base): array
    {
        $makeContent = function() use ($imageBlobs) {
            $content = [[
                'type' => 'text',
                'text' => "Extract all readable English text from the images, then correct/improve it. Return JSON with keys: {extracted, corrected, explanations[]}.",
            ]];
            foreach ($imageBlobs as $blob) {
                $b64 = base64_encode($blob);
                $content[] = ['type'=>'image_url','image_url'=>['url'=>"data:image/png;base64,{$b64}"]];
            }
            return $content;
        };

        $res = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$base}/chat/completions", [
            'model' => $model,
            'messages' => [[ 'role'=>'user', 'content'=>$makeContent() ]],
            'temperature' => 0.0,
            'response_format' => ['type'=>'json_object'],
        ]);

        if (!$res->successful()) {
            $detail = $res->json() ?: ['status'=>$res->status()];
            throw new \RuntimeException('Vision extract+correct failed: '.json_encode($detail, JSON_UNESCAPED_UNICODE));
        }

        $content = $res->json('choices.0.message.content') ?? '{}';
        return json_decode($content, true) ?: [];
    }

    /**
     * Utility: server-side docx writer (kept for optional server generation)
     * Not used by front-end flow by default.
     */
    private function buildDocxAndGetUrl(string $title, string $extracted, string $corrected, array $explanations): string
    {
        if (!class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
            throw new \RuntimeException('PhpOffice\\PhpWord not installed. Run: composer require phpoffice/phpword');
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection();
        $section->addTitle('Essay Report', 1);
        if ($title) { $section->addText("Title: {$title}", ['bold'=>true]); }
        $section->addTextBreak(1);

        $section->addTitle('Extracted Text', 2);
        $section->addText($extracted !== '' ? $extracted : '-', []);
        $section->addTextBreak(1);

        $section->addTitle('Corrected / Improved', 2);
        $section->addText($corrected !== '' ? $corrected : '-', []);
        $section->addTextBreak(1);

        if (!empty($explanations)) {
            $section->addTitle('Explanations', 2);
            foreach ($explanations as $ex) {
                $section->addListItem($ex, 0, [], ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED]);
            }
        }

        if (!Storage::disk('public')->exists('essay_reports')) {
            Storage::disk('public')->makeDirectory('essay_reports');
        }
        $filename = 'essay_report_'.date('Ymd_His').'.docx';
        $fullPath = Storage::path('public/essay_reports/'.$filename);

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return asset('storage/essay_reports/'.$filename);
    }
}
