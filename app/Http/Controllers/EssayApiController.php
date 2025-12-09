<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EssayApiController extends Controller
{
    protected $openaiKey;
    protected $openaiBase;
    protected $openaiModel;

    public function __construct()
    {
        $this->openaiKey   = env('OPENAI_API_KEY', null);
        $this->openaiBase  = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $this->openaiModel = env('OPENAI_MODEL', 'gpt-4o-mini');
    }

    /**
     * OCR endpoint - tries tesseract if available, otherwise returns guidance.
     */
    public function ocr(Request $req)
    {
        if (!$req->hasFile('file')) {
            return response()->json(['ok' => false, 'error' => 'No file uploaded.'], 400);
        }

        $file = $req->file('file');
        $tmpPath = $file->getRealPath();

        // Try using tesseract CLI if available.
        // This is a pragmatic choice: many servers can apt-get install tesseract-ocr.
        // If not available, return an actionable error for integrator to connect an OCR provider.
        $tesseract = trim(shell_exec('which tesseract 2>/dev/null'));
        if ($tesseract) {
            try {
                $outFile = sys_get_temp_dir() . '/ocr_' . Str::random(8);
                // tesseract <input> <output basename> -l eng
                $cmd = escapeshellcmd($tesseract) . ' ' . escapeshellarg($tmpPath) . ' ' . escapeshellarg($outFile) . ' 2>&1';
                exec($cmd, $o, $ret);
                $txtFile = $outFile . '.txt';
                if (file_exists($txtFile)) {
                    $text = file_get_contents($txtFile);
                    @unlink($txtFile);
                    return response()->json(['ok' => true, 'text' => $text]);
                } else {
                    return response()->json([
                        'ok' => false,
                        'error' => 'Tesseract ran but output not found. Output: ' . implode("\n", $o)
                    ], 500);
                }
            } catch (\Throwable $e) {
                Log::error('OCR (tesseract) failed: ' . $e->getMessage());
                return response()->json(['ok' => false, 'error' => 'OCR failed: ' . $e->getMessage()], 500);
            }
        }

        // No tesseract: return clear guidance
        return response()->json([
            'ok' => false,
            'error' => 'Server-side OCR not available. Install tesseract or integrate an OCR API (e.g., OCR.space or Google Vision).'
        ], 501);
    }

    /**
     * Grade endpoint: sends text + rubric to OpenAI and expects a JSON structured response.
     * If the model returns plain text/HTML, the controller returns that raw text (so the frontend parser can parse it).
     */
    public function grade(Request $req)
    {
        $text = trim($req->input('text', ''));
        $rubricText = $req->input('rubric_text', $req->input('rubric', ''));
        $rubricCode = $req->input('rubric_code', $req->input('rubric', ''));

        if (!$text) {
            return response()->json(['ok' => false, 'error' => 'No text provided.'], 400);
        }

        if (!$this->openaiKey) {
            return response()->json(['ok' => false, 'error' => 'OPENAI_API_KEY not configured on server.'], 500);
        }

        // Build a strong system/user prompt that asks for strict JSON
        $system = "You are an objective essay grader. Use the rubric provided verbatim. Return a single JSON object ONLY (no surrounding text). The JSON must include fields: scores (content, communicative, organisation, language, total), rationales (array), suggestions (array), inline_diff_html (optional string), original_text (optional), corrected_text (optional). Scores in 0-5 for each criterion; total is 0-20. If you cannot compute something, set null.";

        $user = "RUBRIC:\n" . ($rubricText ?: "(no rubric provided)") . "\n\nESSAY:\n" . $text . "\n\nINSTRUCTIONS:\nGrade the essay according to the rubric. Return JSON only. Example:\n{\n  \"scores\": {\"content\":4,\"communicative\":3,\"organisation\":3,\"language\":3,\"total\":13},\n  \"rationales\": [\"...\"],\n  \"suggestions\": [\"...\"],\n  \"inline_diff_html\": \"<ins>...</ins><del>...</del>\",\n  \"original_text\": \"...\",\n  \"corrected_text\": \"...\"\n}";

        try {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->openaiBase}/chat/completions", [
                'model' => $this->openaiModel,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user]
                ],
                'temperature' => 0.0,
                'max_tokens' => 1200,
            ]);

            if (!$resp->ok()) {
                Log::warning('OpenAI grade non-ok: ' . $resp->body());
                return response()->json(['ok' => false, 'error' => 'OpenAI returned non-OK status', 'body' => $resp->body()], 502);
            }

            $jsonResp = $resp->json();
            // try to retrieve assistant text (content)
            $content = null;
            if (isset($jsonResp['choices'][0]['message']['content'])) {
                $content = $jsonResp['choices'][0]['message']['content'];
            } elseif (isset($jsonResp['choices'][0]['text'])) {
                $content = $jsonResp['choices'][0]['text'];
            } else {
                $content = json_encode($jsonResp);
            }

            // attempt parse JSON out of content. the model SHOULD return JSON only.
            $parsed = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                // Ensure required fields exist
                if (!isset($parsed['scores'])) $parsed['scores'] = ['content'=>null,'communicative'=>null,'organisation'=>null,'language'=>null,'total'=>null];
                if (!isset($parsed['rationales'])) $parsed['rationales'] = [];
                if (!isset($parsed['suggestions'])) $parsed['suggestions'] = [];
                // respond structured
                return response()->json(['ok' => true, 'report' => $parsed]);
            } else {
                // Not valid JSON: return raw content so frontend can parse
                return response()->json([
                    'ok' => true,
                    'report' => [
                        'scores' => ['content'=>null,'communicative'=>null,'organisation'=>null,'language'=>null,'total'=>null],
                        'rationales' => [],
                        'suggestions' => [],
                        'inline_diff_html' => null,
                        'raw_text' => $content,
                        'raw_text_stripped' => strip_tags($content),
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Grade error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'Grade error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * directCorrect: used by "Suggest Corrections" button.
     * Expects 'text' form field. Returns JSON { ok, original, corrected, explanations: [] }
     */
    public function directCorrect(Request $req)
    {
        $text = trim($req->input('text', ''));
        if (!$text) {
            return response()->json(['ok' => false, 'error' => 'No text provided.'], 400);
        }
        if (!$this->openaiKey) {
            return response()->json(['ok' => false, 'error' => 'OPENAI_API_KEY not configured on server.'], 500);
        }

        $system = "You are an English writing corrector. Provide corrected version and short explanations of major corrections. Return JSON ONLY with keys: original, corrected, explanations (array of strings).";

        $user = "Original text:\n" . $text . "\n\nReturn JSON only, example:\n{ \"original\": \"...\", \"corrected\": \"...\", \"explanations\": [\"...\"] }";

        try {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$this->openaiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->openaiBase}/chat/completions", [
                'model' => $this->openaiModel,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user]
                ],
                'temperature' => 0.0,
                'max_tokens' => 800,
            ]);

            if (!$resp->ok()) {
                return response()->json(['ok' => false, 'error' => 'OpenAI returned non-OK status', 'body' => $resp->body()], 502);
            }

            $jsonResp = $resp->json();
            $content = $jsonResp['choices'][0]['message']['content'] ?? ($jsonResp['choices'][0]['text'] ?? json_encode($jsonResp));

            $parsed = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return response()->json(array_merge(['ok' => true], $parsed));
            }

            // Fallback: return content as corrected_text
            return response()->json(['ok' => true, 'original' => $text, 'corrected' => $content, 'explanations' => []]);
        } catch (\Throwable $e) {
            Log::error('directCorrect error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'directCorrect error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * exportDocx: changed to return JSON report object (not binary DOCX).
     * Frontend will receive the structured report and generate DOCX locally.
     * If you still need server-generated DOCX, call exportDocxSmoke or implement phpword generation here.
     */
    public function exportDocx(Request $req)
    {
        // Accept either full payload or fallback to window.__lastGrade style payload from frontend
        $payload = $req->json()->all() ?: $req->all();

        if (empty($payload)) {
            return response()->json(['ok' => false, 'error' => 'No payload provided.'], 400);
        }

        // Build a normalized report
        $report = [
            'title' => $payload['title'] ?? 'Essay Report',
            'rubric_code' => $payload['rubric_code'] ?? ($payload['rubric'] ?? null),
            'rubric_text' => $payload['rubric_text'] ?? null,
            'extracted' => $payload['extracted'] ?? null,
            'original_text' => $payload['original_text'] ?? $payload['extracted'] ?? null,
            'corrected_text' => $payload['corrected'] ?? ($payload['corrected_text'] ?? null),
            'scores' => $payload['scores'] ?? ($payload['score_map'] ?? ($payload['scores'] ?? null)),
            'criterion_explanations' => $payload['criterion_explanations'] ?? ($payload['rationales'] ?? []),
            'revision_suggestions' => $payload['revision_suggestions'] ?? ($payload['suggestions'] ?? []),
            'inline_diff_html' => $payload['inline_diff_html'] ?? ($payload['inline_diff'] ?? null),
            'raw_grade_payload' => $payload['raw_grade_payload'] ?? ($payload['report'] ?? null),
        ];

        // Always return JSON report. Frontend will consume this and create DOCX.
        return response()->json(['ok' => true, 'report' => $report]);
    }

    /**
     * exportDocxSmoke: returns a tiny DOCX binary for testing route/headers.
     * Requires phpoffice/phpword (optional). If not installed, return plaintext fallback.
     */
    public function exportDocxSmoke(Request $req)
    {
        $text = 'Hello — essay export smoke test.';

        // Try to use PhpWord if installed
        if (class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
            try {
                $phpWord = new \PhpOffice\PhpWord\PhpWord();
                $section = $phpWord->addSection();
                $section->addText($text);
                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

                $temp = tmpfile();
                $meta = stream_get_meta_data($temp);
                $tmpFilename = $meta['uri'];
                $objWriter->save($tmpFilename);
                $content = stream_get_contents($temp, -1, 0);
                fclose($temp);

                return response($content, 200, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'Content-Disposition' => 'attachment; filename="smoke.docx"',
                ]);
            } catch (\Throwable $e) {
                Log::error('exportDocxSmoke phpword error: ' . $e->getMessage());
                // fallback to plain text
            }
        }

        // Fallback: return a simple text file so route+download headers can be tested
        return response('Hello (smoke). PhpWord not available on server.', 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="smoke.txt"',
        ]);
    }
}
