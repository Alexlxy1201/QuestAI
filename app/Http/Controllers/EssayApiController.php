<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
            // 优先：pdfparser（文本层）
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf    = $parser->parseFile($abs);
                    $text   = trim($pdf->getText() ?? '');
                    $pages  = count($pdf->getPages());
                } catch (\Throwable $e) { $text = ''; }
            }
            // 若无文本层：需要 Imagick 分页截图 + Vision OCR
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
Score strictly on 4 dimensions (0-5 each):
- Content
- Communicative Achievement
- Organisation
- Language
Total = sum of four (0-20).
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
     * 直接“提取 + 润色”：文件(图片/PDF)或纯文本 -> 提取文本/润色/解释
     * 不落库
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

        // A) 有文件：先处理文件
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
                // 单张图片：一次性提取+润色
                $blob = file_get_contents($abs);
                $result = $this->imageExtractAndCorrect([$blob], $apiKey, $model, $base);
                $extracted  = $result['extracted'] ?? '';
                $corrected  = $result['corrected'] ?? '';
                $explainArr = $result['explanations'] ?? [];
            }
        }

        // B) 文本输入（或 PDF 有文本层）
        if ($extracted === null) {
            $extracted = $data['text'] ?? '';
        }

        // 若还没有 corrected，再走一次文本纠错
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
     * 导出 Word（.docx）—— 改为“直接流式下载”，不落地、不依赖 URL
     * 需要：composer require phpoffice/phpword 且启用 ext-zip
     */
    public function exportDocx(Request $request)
    {
        $payload = $request->validate([
            'title'        => ['nullable','string'],
            'extracted'    => ['required','string'],
            'corrected'    => ['required','string'],
            'explanations' => ['nullable','array'],
        ]);

        $title        = trim($payload['title'] ?? 'Essay Report');
        $extracted    = $payload['extracted'];
        $corrected    = $payload['corrected'];
        $explanations = $payload['explanations'] ?? [];

        // 构建 PhpWord 文档对象（内存）
        $phpWord = $this->buildDocxPhpWord($title, $extracted, $corrected, $explanations);

        // 安全文件名
        $filename = preg_replace('/[^\w\-]+/u', '_', $title) . '.docx';

        return response()->streamDownload(function () use ($phpWord) {
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control'     => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'            => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /* ===== 占位：由于“仅本地存储”，这两个接口不提供服务端历史 ===== */
    public function history(Request $request)
    {
        return response()->json(['ok'=>false,'error'=>'Server-side history is disabled. Use localStorage only.'], 501);
    }
    public function exportHistory(Request $request)
    {
        return response()->json(['ok'=>false,'error'=>'Server-side history export is disabled. Use localStorage only.'], 501);
    }

    /* ====== 工具函数：OpenAI Vision OCR（单图 -> 文本） ====== */
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

    /* ====== 工具函数：多图像 -> 一次性提取+润色 ====== */
    private function imageExtractAndCorrect(array $imageBlobs, string $apiKey, string $model, string $base): array
    {
        $makeContent = function(string $type) use ($imageBlobs) {
            $content = [[
                'type' => 'text',
                'text' => "Extract all readable English text from the images, then correct/improve it. Return JSON with keys: {extracted, corrected, explanations[]}.",
            ]];
            foreach ($imageBlobs as $blob) {
                $b64 = base64_encode($blob);
                if ($type === 'image_url') {
                    $content[] = ['type'=>'image_url','image_url'=>['url'=>"data:image/png;base64,{$b64}"]];
                } else {
                    $content[] = ['type'=>'input_image','image_url'=>['url'=>"data:image/png;base64,{$b64}"]];
                }
            }
            return $content;
        };

        // 尝试 image_url
        $res = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$base}/chat/completions", [
            'model' => $model,
            'messages' => [[ 'role'=>'user', 'content'=>$makeContent('image_url') ]],
            'temperature' => 0.0,
            'response_format' => ['type'=>'json_object'],
        ]);

        // 回退 input_image
        if (!$res->successful()) {
            $res = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post("{$base}/chat/completions", [
                'model' => $model,
                'messages' => [[ 'role'=>'user', 'content'=>$makeContent('input_image') ]],
                'temperature' => 0.0,
                'response_format' => ['type'=>'json_object'],
            ]);
        }

        if (!$res->successful()) {
            $detail = $res->json() ?: ['status'=>$res->status()];
            throw new \RuntimeException('Vision extract+correct failed: '.json_encode($detail, JSON_UNESCAPED_UNICODE));
        }

        $content = $res->json('choices.0.message.content') ?? '{}';
        return json_decode($content, true) ?: [];
    }

    /**
     * 工具函数：构建 PhpWord 文档对象（不落地）
     */
    private function buildDocxPhpWord(string $title, string $extracted, string $corrected, array $explanations)
    {
        if (!class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
            throw new \RuntimeException('PhpOffice\\PhpWord not installed. Run: composer require phpoffice/phpword');
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $phpWord->addTitleStyle(1, ['size'=>18,'bold'=>true]);
        $phpWord->addTitleStyle(2, ['size'=>14,'bold'=>true]);

        $section = $phpWord->addSection();
        $section->addTitle('Essay Report', 1);
        if ($title) { $section->addText("Title: {$title}", ['bold'=>true]); }
        $section->addText(date('Y-m-d H:i:s'), ['color'=>'777777','size'=>10]);
        $section->addTextBreak(1);

        $section->addTitle('Original (Extracted)', 2);
        foreach (preg_split("/\r\n|\n|\r/", $extracted !== '' ? $extracted : '-') as $line) {
            $section->addText($line ?: ' ');
        }
        $section->addTextBreak(1);

        $section->addTitle('Corrected / Improved', 2);
        foreach (preg_split("/\r\n|\n|\r/", $corrected !== '' ? $corrected : '-') as $line) {
            $section->addText($line ?: ' ');
        }
        $section->addTextBreak(1);

        if (!empty($explanations)) {
            $section->addTitle('Rationales / Explanations', 2);
            foreach ($explanations as $ex) {
                $section->addListItem(is_string($ex) ? $ex : json_encode($ex, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
        }

        return $phpWord;
    }

    /* ====== （保留）生成 DOCX 文件到存储并返回 URL —— 兼容其它旧逻辑 ====== */
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

        // 保存到 public 磁盘（旧方式）
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
