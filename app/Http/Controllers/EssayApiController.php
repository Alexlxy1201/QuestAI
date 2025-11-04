<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\EssayRecord;

class EssayApiController extends Controller
{
    /** =========================
     * POST /api/ocr  (image/* | application/pdf)
     * 传统 OCR 流程（保留）
     * ========================= */
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
            if (class_exists(\Imagick::class)) {
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
            } elseif (class_exists(\Smalot\PdfParser\Parser::class)) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($abs);
                $text   = trim($pdf->getText());
                $pages  = count($pdf->getPages());
                if ($text === '') {
                    return response()->json([
                        'ok'=>false,
                        'error'=>'PDF looks scanned (no text layer). Enable Imagick or upload images.',
                    ], 400);
                }
            } else {
                return response()->json([
                    'ok'=>false,
                    'error'=>'PDF OCR requires Imagick or smalot/pdfparser.',
                ], 500);
            }
        } else {
            $blob = file_get_contents($abs);
            $text = $this->visionOCR($blob, $mime);
            $pages = 1;
        }

        $rec = EssayRecord::create([
            'origin'           => 'ocr',
            'title'            => null,
            'rubric'           => null,
            'text'             => null,
            'ocr_text'         => $text,
            'scores_json'      => null,
            'suggestions_json' => null,
            'file_path'        => $path,
            'file_mime'        => $mime,
            'file_pages'       => $pages,
            'parent_id'        => null,
        ]);

        return response()->json(['ok'=>true,'text'=>$text,'pages'=>$pages,'record_id'=>$rec->id]);
    }

    /** =========================
     * POST /api/grade  {title?, rubric, text, record_id?}
     * 按 Rubric 打分（保留）
     * ========================= */
    public function grade(Request $request)
    {
        $payload = $request->validate([
            'title'     => ['nullable','string'],
            'rubric'    => ['required','string'],
            'text'      => ['required','string'],
            'record_id' => ['nullable','integer'],
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
            return response()->json([
                'ok'=>false,
                'error'=>'OpenAI grade request failed',
                'details'=>$res->json() ?: ['status'=>$res->status()],
            ], 500);
        }

        $content = $res->json('choices.0.message.content') ?? '{}';
        $parsed  = json_decode($content, true) ?: [];

        $scores = $parsed['scores'] ?? [
            'content'=>null,'communicative'=>null,'organisation'=>null,'language'=>null,'total'=>null
        ];
        $sugs   = $parsed['suggestions'] ?? [];

        $rec = EssayRecord::create([
            'origin'           => 'grade',
            'title'            => $payload['title'] ?? null,
            'rubric'           => $payload['rubric'],
            'text'             => $payload['text'],
            'ocr_text'         => null,
            'scores_json'      => $scores,
            'suggestions_json' => $sugs,
            'file_path'        => null,
            'file_mime'        => null,
            'file_pages'       => null,
            'parent_id'        => $payload['record_id'] ?? null,
        ]);

        return response()->json(['ok'=>true,'scores'=>$scores,'suggestions'=>$sugs,'record_id'=>$rec->id]);
    }

    /** =========================
     * GET /api/essay/history
     * ========================= */
    public function history(Request $request)
    {
        $per  = min(max((int)$request->query('per_page', 20), 1), 100);
        $list = EssayRecord::orderByDesc('id')->paginate($per);
        return response()->json([
            'ok'=>true,
            'data'=>$list->items(),
            'pagination'=>[
                'current_page'=>$list->currentPage(),
                'per_page'=>$list->perPage(),
                'total'=>$list->total(),
                'last_page'=>$list->lastPage(),
            ],
        ]);
    }

    /** =========================
     * GET /api/essay/history/export?format=csv|json
     * ========================= */
    public function exportHistory(Request $request)
    {
        $format = strtolower($request->query('format', 'json'));
        $rows   = EssayRecord::orderBy('id')->get();

        if ($format === 'csv') {
            $tmp  = tmpfile();
            $meta = stream_get_meta_data($tmp);
            $p    = $meta['uri'];
            fputcsv($tmp, [
                'id','origin','title','rubric','text','ocr_text','scores_json','suggestions_json',
                'file_path','file_mime','file_pages','parent_id','created_at'
            ]);
            foreach ($rows as $r) {
                fputcsv($tmp, [
                    $r->id, $r->origin, $r->title, $r->rubric,
                    $this->short($r->text), $this->short($r->ocr_text),
                    json_encode($r->scores_json, JSON_UNESCAPED_UNICODE),
                    json_encode($r->suggestions_json, JSON_UNESCAPED_UNICODE),
                    $r->file_path, $r->file_mime, $r->file_pages, $r->parent_id, $r->created_at
                ]);
            }
            $csv = file_get_contents($p);
            fclose($tmp);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="essay_history.csv"',
            ]);
        }

        return response()->json(['ok'=>true, 'exported'=>$rows->count(), 'data'=>$rows]);
    }

    /** ==============================================================
     * POST /api/essay/direct-correct
     * file(image|pdf) 或 text（二者至少一个）
     * 返回：{ ok, extracted, corrected, explanations[], record_id, docx_url? }
     * ============================================================== */
    public function directCorrect(Request $request)
    {
        try {
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

            // A) 有文件
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
                    // 图片：一次性“提取+润色”
                    $blob = file_get_contents($abs);
                    $result = $this->imageExtractAndCorrect([$blob], $apiKey, $model, $base);
                    $extracted  = $result['extracted'] ?? '';
                    $corrected  = $result['corrected'] ?? '';
                    $explainArr = $result['explanations'] ?? [];
                }
            }

            // B) 文本输入或 PDF 文本层
            if ($extracted === null) {
                $extracted = $data['text'] ?? '';
            }

            // 如果还未得到 corrected，则用文本再纠一次
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

            // 记录
            $rec = EssayRecord::create([
                'origin'           => 'direct',
                'title'            => $data['title'] ?? null,
                'rubric'           => null,
                'text'             => $corrected,
                'ocr_text'         => $extracted,
                'scores_json'      => null,
                'suggestions_json' => $explainArr,
                'file_path'        => null,
                'file_mime'        => null,
                'file_pages'       => null,
                'parent_id'        => null,
            ]);

            $resp = [
                'ok'           => true,
                'extracted'    => $extracted,
                'corrected'    => $corrected,
                'explanations' => $explainArr,
                'record_id'    => $rec->id,
            ];

            if (!empty($data['make_docx'])) {
                $resp['docx_url'] = $this->buildDocxAndGetUrl($data['title'] ?? 'Essay Report', $extracted, $corrected, $explainArr);
            }

            return response()->json($resp);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'=>false,
                'error'=>$e->getMessage(),
            ], 500);
        }
    }

    /** ==============================================================
     * POST /api/essay/export-docx
     * 输入：{ title?, extracted, corrected, explanations[] }
     * 返回：{ ok, url }
     * ============================================================== */
    public function exportDocx(Request $request)
    {
        $payload = $request->validate([
            'title'        => ['nullable','string'],
            'extracted'    => ['required','string'],
            'corrected'    => ['required','string'],
            'explanations' => ['nullable','array'],
        ]);

        $url = $this->buildDocxAndGetUrl(
            $payload['title'] ?? 'Essay Report',
            $payload['extracted'],
            $payload['corrected'],
            $payload['explanations'] ?? []
        );

        return response()->json(['ok'=>true, 'url'=>$url]);
    }

    /** =========================
     * OpenAI Vision OCR（单图像 → 文本）
     * ========================= */
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

    /** =========================
     * 多图像 → 一次性“提取+润色”
     * 返回：['extracted'=>..., 'corrected'=>..., 'explanations'=>[]]
     * （含 image_url → input_image 双格式回退）
     * ========================= */
    private function imageExtractAndCorrect(array $imageBlobs, string $apiKey, string $model, string $base): array
    {
        $makeContent = function(string $type) use ($imageBlobs) {
            $content = [[
                'type' => 'text',
                'text' => "Extract all readable English text from the images, then correct/improve it. Return JSON with keys: {extracted, corrected, explanations[]}."
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

        // try image_url first
        $res = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$base}/chat/completions", [
            'model' => $model,
            'messages' => [[ 'role'=>'user', 'content'=>$makeContent('image_url') ]],
            'temperature' => 0.0,
            'response_format' => ['type'=>'json_object'],
        ]);

        // fallback: input_image
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

    /** =========================
     * 生成 DOCX 并返回可访问 URL
     * 需要：composer require phpoffice/phpword
     *       php artisan storage:link
     * ========================= */
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

        $dir = 'exports';
        if (!Storage::exists($dir)) Storage::makeDirectory($dir);
        $file = $dir.'/essay_report_'.date('Ymd_His').'.docx';

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save(Storage::path($file));

        // 输出到 public 磁盘
        if (config('filesystems.disks.public')) {
            $publicFile = 'essay_reports/'.basename($file);
            if (!Storage::disk('public')->exists('essay_reports')) {
                Storage::disk('public')->makeDirectory('essay_reports');
            }
            Storage::disk('public')->put($publicFile, file_get_contents(Storage::path($file)));
            return asset('storage/'.$publicFile);
        }

        return url('/');
    }

    private function short(?string $s, int $len = 200): ?string
    {
        if ($s === null) return null;
        $s = trim($s);
        return mb_strlen($s) > $len ? (mb_substr($s, 0, $len).'…') : $s;
    }
}
