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
     * 传统 OCR：image/pdf -> text （不落库）
     */
    public function ocr(Request $request)
    {
        $data = $request->validate([
            'file' => ['required','file','mimetypes:image/jpeg,image/png,image/webp,application/pdf'],
            'max_pages' => ['nullable','integer','min:1','max:10'],
        ]);
        $maxPages = $data['max_pages'] ?? 3;

        $path = $request->file('file')->store('essays/uploads');
        $abs  = Storage::path($path);
        $mime = $request->file('file')->getMimeType();

        $text  = '';
        $pages = 0;

        if ($mime === 'application/pdf') {
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf    = $parser->parseFile($abs);
                    $text   = trim($pdf->getText() ?? '');
                    $pages  = count($pdf->getPages());
                } catch (\Throwable $e) { $text = ''; }
            }
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
                foreach ($im as $page) {
                    if ($i++ >= $n) break;
                    $page->setImageFormat('png');
                    $blob = $page->getImageBlob();
                    $text .= ($text ? "\n\n" : "") . $this->visionOCR($blob, 'image/png');
                }
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
     * 打分（Rubric），不落库
     */
    public function grade(Request $request)
    {
        $payload = $request->validate([
            'title'  => ['nullable','string'],
            'rubric' => ['required','string'], // SPM_P1 | SPM_P2 | SPM_P3 | UASA_P1 | UASA_P2
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
Return JSON only with keys: scores{content,communicative,organisation,language,total}, suggestions[].
Keep suggestions concrete and actionable.
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

        $scores = $parsed['scores'] ?? [
            'content'=>null,'communicative'=>null,'organisation'=>null,'language'=>null,'total'=>null
        ];
        $sugs   = $parsed['suggestions'] ?? [];

        return response()->json(['ok'=>true,'scores'=>$scores,'suggestions'=>$sugs]);
    }

    /**
     * 直接“提取 + 润色”：文件(图片/PDF)或纯文本 -> 提取文本/润色/解释（不落库）
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
     * 导出完整报告 DOCX（含 AI Score, Explanations, Suggestions, InlineDiff）
     */
    public function exportDocx(Request $request)
    {
        try {
            $title = trim($request->input('title', 'Essay Report'));
            $rubric = $request->input('rubric', '');
            $scores = $request->input('scores', []);
            $extracted = trim($request->input('extracted', ''));
            $corrected = trim($request->input('corrected', ''));
            $explanations = $request->input('explanations', []);
            $suggestions = $request->input('suggestions', []);
            $inlineDiff = $request->input('diffHtml', '');

            $phpWord = new PhpWord();
            $section = $phpWord->addSection([
                'marginTop' => 800,
                'marginBottom' => 800,
                'marginLeft' => 1000,
                'marginRight' => 1000,
            ]);

            $section->addText("Essay Report", ['bold' => true, 'size' => 18, 'color' => '1F4E79']);
            $section->addTextBreak(1);
            $section->addText("Title: {$title}", ['bold' => true, 'size' => 12]);
            if ($rubric) $section->addText("Rubric: {$rubric}", ['italic' => true, 'size' => 11]);
            $section->addTextBreak(1);

            $scores = array_merge([
                'content' => null,
                'communicative' => null,
                'organisation' => null,
                'language' => null,
                'total' => null
            ], (array)$scores);

            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '999999',
                'alignment' => JcTable::CENTER,
                'cellMargin' => 80
            ]);
            $table->addRow();
            $table->addCell(3000)->addText('Criterion', ['bold' => true]);
            $table->addCell(1000)->addText('Score', ['bold' => true]);
            $table->addCell(1000)->addText('Range', ['bold' => true]);

            $criteria = [
                'Content' => $scores['content'],
                'Communicative' => $scores['communicative'],
                'Organisation' => $scores['organisation'],
                'Language' => $scores['language'],
                'Total' => $scores['total'],
            ];

            foreach ($criteria as $key => $val) {
                $table->addRow();
                $table->addCell(3000)->addText($key);
                $table->addCell(1000)->addText($val !== null ? (string)$val : '-');
                $table->addCell(1000)->addText($key === 'Total' ? '/20' : '0–5');
            }

            $section->addTextBreak(1);

            $section->addText("Criterion Explanations", ['bold' => true, 'size' => 13, 'color' => '1F4E79']);
            if (empty($explanations)) {
                $section->addText("No detailed explanations returned by the API.", ['italic' => true]);
            } else {
                foreach ($explanations as $line) $section->addListItem(strip_tags($line), 0, ['size' => 11]);
            }
            $section->addTextBreak(1);

            $section->addText("Revision Suggestions", ['bold' => true, 'size' => 13, 'color' => '1F4E79']);
            if (empty($suggestions)) {
                $section->addText("No revision suggestions returned by the API.", ['italic' => true]);
            } else {
                foreach ($suggestions as $line) $section->addListItem(strip_tags($line), 0, ['size' => 11]);
            }
            $section->addTextBreak(1);

            $section->addText("Inline Diff", ['bold' => true, 'size' => 13, 'color' => '1F4E79']);
            if ($inlineDiff) {
                Html::addHtml($section, $inlineDiff, false, false);
            } else {
                $section->addText("No inline diff data available.", ['italic' => true]);
            }
            $section->addTextBreak(1);

            $section->addText("Original Essay", ['bold' => true, 'size' => 13, 'color' => '1F4E79']);
            $section->addText($extracted ?: '-', ['size' => 11]);
            $section->addTextBreak(1);

            $section->addText("Corrected Essay", ['bold' => true, 'size' => 13, 'color' => '1F4E79']);
            $section->addText($corrected ?: '-', ['size' => 11]);
            $section->addTextBreak(1);

            return response()->streamDownload(function () use ($phpWord) {
                IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
            }, 'essay-report.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'error'=>'DOCX export failed: '.$e->getMessage()], 500);
        }
    }

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

    private function imageExtractAndCorrect(array $imageBlobs, string $apiKey, string $model, string $base): array
    {
        $makeContent = function(string $type) use ($imageBlobs) {
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
            'messages' => [[ 'role'=>'user', 'content'=>$makeContent('image_url') ]],
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
