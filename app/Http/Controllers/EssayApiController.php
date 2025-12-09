<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpWord\PhpWord; // require phpoffice/phpword
use PhpOffice\PhpWord\Shared\Html as PhpWordHtml;

class EssayApiController extends Controller
{
    /**
     * Helper: call OpenAI ChatCompletions and return assistant content (string).
     * Tries default v1/chat/completions. Respects OPENAI_BASE_URL if set.
     */
    protected function callOpenAI(array $payload): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            return ['ok' => false, 'error' => 'OPENAI_API_KEY not configured'];
        }

        $base = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $model = env('OPENAI_MODEL', 'gpt-4o-mini'); // fallback

        // ensure model present
        $payload = array_merge(['model' => $model], $payload);

        try {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post("{$base}/chat/completions", $payload);

            $status = $resp->status();
            $body = $resp->body(); // raw text
            $json = $resp->json(null);

            // try to extract assistant content robustly
            $assistant = null;
            if (is_array($json) && isset($json['choices'][0]['message']['content'])) {
                $assistant = $json['choices'][0]['message']['content'];
            } else {
                // fallback: try to find "content" in text
                $assistant = $body;
            }

            return ['ok' => $resp->ok(), 'status' => $status, 'raw' => $body, 'assistant' => $assistant, 'response_json' => $json];
        } catch (\Throwable $e) {
            Log::error('OpenAI request failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Try to parse a JSON object out of a string returned by the model.
     * If parse fails, return null.
     */
    protected function extractJsonFromString(string $s)
    {
        $s = trim($s);
        if (!$s) return null;

        // If it already is JSON
        if (($d = json_decode($s, true)) && json_last_error() === JSON_ERROR_NONE) {
            return $d;
        }

        // Attempt to find first { ... } or [ ... ] block
        if (preg_match('/(\{(?:.*)\})/sU', $s, $m)) {
            $cand = $m[1];
            if (($d = json_decode($cand, true)) && json_last_error() === JSON_ERROR_NONE) {
                return $d;
            }
        }
        if (preg_match('/(\[(?:.*)\])/sU', $s, $m)) {
            $cand = $m[1];
            if (($d = json_decode($cand, true)) && json_last_error() === JSON_ERROR_NONE) {
                return $d;
            }
        }

        return null;
    }

    /**
     * Build a simple inline diff HTML between original and corrected.
     * Small token-based LCS diff to produce <ins> and <del> tags.
     */
    protected function makeInlineDiffHtml(string $orig, string $corr): string
    {
        // tokenize by words and punctuation (keeps whitespace tokens)
        $re = '/[A-Za-z0-9\p{L}’\'’-]+|\s+|[^\sA-Za-z0-9\p{L}]/u';
        preg_match_all($re, $orig, $ma);
        $a = $ma[0] ?: [];
        preg_match_all($re, $corr, $mb);
        $b = $mb[0] ?: [];

        $n = count($a);
        $m = count($b);
        $dp = array_fill(0, $n+1, array_fill(0, $m+1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $dp[$i][$j] = ($a[$i] === $b[$j]) ? $dp[$i+1][$j+1] + 1 : max($dp[$i+1][$j], $dp[$i][$j+1]);
            }
        }
        $i = $j = 0;
        $html = '';
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $html .= htmlentities($a[$i], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $i++; $j++;
            } else if ($dp[$i+1][$j] >= $dp[$i][$j+1]) {
                $html .= '<del>' . htmlentities($a[$i], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</del>';
                $i++;
            } else {
                $html .= '<ins>' . htmlentities($b[$j], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</ins>';
                $j++;
            }
        }
        while ($i < $n) {
            $html .= '<del>' . htmlentities($a[$i], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</del>';
            $i++;
        }
        while ($j < $m) {
            $html .= '<ins>' . htmlentities($b[$j], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</ins>';
            $j++;
        }
        return $html;
    }

    /**
     * POST /api/ocr
     * Accepts a file input 'file' (image or pdf)
     * - If OCR_SPACE_API_KEY set -> forward to ocr.space
     * - Else try tesseract CLI if available
     */
    public function ocr(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['ok' => false, 'error' => 'No file provided.'], 400);
        }
        $file = $request->file('file');

        // try OCR.Space if key provided
        $ocrKey = env('OCR_SPACE_API_KEY', '');
        if ($ocrKey) {
            try {
                $response = Http::asMultipart()->post('https://api.ocr.space/parse/image', [
                    ['name' => 'file', 'contents' => fopen($file->getPathname(), 'r'), 'filename' => $file->getClientOriginalName()],
                    ['name' => 'language', 'contents' => 'eng'],
                    ['name' => 'OCREngine', 'contents' => '2'],
                    ['name' => 'isOverlayRequired', 'contents' => 'false'],
                    ['name' => 'apikey', 'contents' => $ocrKey],
                ]);
                if (!$response->ok()) {
                    return response()->json(['ok' => false, 'error' => 'OCR provider error', 'status' => $response->status(), 'body' => $response->body()], 500);
                }
                $j = $response->json();
                $text = $j['ParsedResults'][0]['ParsedText'] ?? '';
                return response()->json(['ok' => true, 'text' => (string) $text]);
            } catch (\Throwable $e) {
                return response()->json(['ok' => false, 'error' => 'OCR (ocr.space) failed: ' . $e->getMessage()], 500);
            }
        }

        // fallback: tesseract CLI (if installed)
        $tmp = $file->getPathname();
        $outFile = sys_get_temp_dir() . '/ocr_' . Str::random(8) . '.txt';
        try {
            $cmd = "tesseract " . escapeshellarg($tmp) . " " . escapeshellarg(str_replace('.txt', '', $outFile)) . " -l eng 2>&1";
            exec($cmd, $out, $ret);
            $text = @file_get_contents($outFile) ?: '';
            @unlink($outFile);
            return response()->json(['ok' => true, 'text' => (string) $text]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'No OCR provider configured (set OCR_SPACE_API_KEY or install tesseract). ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/grade
     * Body: { title, rubric_code, rubric_text, text, prompt_instructions? }
     * Guarantees a JSON response. If model returns non-JSON, returns fallback JSON with raw_text.
     */
    public function grade(Request $request)
    {
        $text = (string) $request->input('text', '');
        if (trim($text) === '') {
            return response()->json(['ok' => false, 'error' => 'No text provided.'], 400);
        }

        $title = (string) $request->input('title', '');
        $rubric_code = (string) $request->input('rubric_code', $request->input('rubric'));
        $rubric_text = (string) $request->input('rubric_text', '');
        $userPrompt = (string) $request->input('prompt_instructions', '');

        // Build a robust system + user prompt to force JSON output
        $system = "You are a strict exam rater. Use the rubric_text verbatim when scoring.";
        $user = <<<PROMPT
Rate the following essay according to the rubric_text exactly. ALWAYS RETURN A SINGLE JSON OBJECT and nothing else.
Fields required in the JSON:
{
  "scores": { "content": 0-5, "communicative": 0-5, "organisation": 0-5, "language": 0-5, "total": 0-20 },
  "rationales": ["...explain criterion by criterion..."],
  "suggestions": ["...revision suggestions..."],
  "original_text": "...",
  "corrected_text": "..." (optional, if you can provide corrected text),
  "inline_diff_html": "<ins>...</ins><del>...</del>" (optional)
}
rubric_text:
\"\"\"{$rubric_text}\"\"\"

Essay title: "{$title}"

Essay:
\"\"\"{$text}\"\"\"

Extra instructions (if any): {$userPrompt}

If you cannot return proper JSON, return a JSON with keys: ok:false and error explaining why.
PROMPT;

        $payload = [
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.0,
            'max_tokens' => 800,
        ];

        $res = $this->callOpenAI($payload);
        if (!$res['ok']) {
            return response()->json(['ok' => false, 'error' => $res['error'] ?? 'OpenAI error']);
        }

        $assistant = (string) ($res['assistant'] ?? '');
        // Try parse JSON out of assistant response
        $parsed = $this->extractJsonFromString($assistant);
        if ($parsed !== null) {
            // ensure consistent shape
            $parsed['ok'] = true;
            return response()->json($parsed);
        }

        // fallback: return raw_text inside JSON so frontend won't break
        return response()->json([
            'ok' => true,
            'raw_text' => $res['raw'] ?? $assistant,
            'message' => 'Model did not return parseable JSON. See raw_text.',
        ]);
    }

    /**
     * POST /api/essay/direct-correct
     * Body: text, title?
     * Returns JSON: { ok:true, original: "...", corrected: "...", explanations: [...] }
     */
    public function directCorrect(Request $request)
    {
        $text = (string) $request->input('text', '');
        if (trim($text) === '') {
            return response()->json(['ok' => false, 'error' => 'No text provided.'], 400);
        }
        $title = (string) $request->input('title', '');

        $system = "You are an English writing corrector. Produce a corrected version and short explanations for edits.";
        $user = <<<PROMPT
Please correct the following essay for grammar, clarity, and coherence. Return JSON only with keys:
{
  "original": "...",
  "corrected": "...",
  "explanations": ["...short reasons for major changes..."]
}
Essay title: "{$title}"
Essay:
\"\"\"{$text}\"\"\"
PROMPT;

        $payload = [
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.0,
            'max_tokens' => 800,
        ];

        $res = $this->callOpenAI($payload);
        if (!$res['ok']) {
            return response()->json(['ok' => false, 'error' => $res['error'] ?? 'OpenAI error']);
        }
        $assistant = (string) ($res['assistant'] ?? '');
        $parsed = $this->extractJsonFromString($assistant);

        if ($parsed) {
            $parsed['ok'] = true;
            return response()->json($parsed);
        }

        // If not JSON, put assistant text in 'corrected' and return
        return response()->json([
            'ok' => true,
            'original' => $text,
            'corrected' => $assistant ?: $text,
            'explanations' => ['No structured explanations returned. See corrected text.'],
            'raw_text' => $res['raw'] ?? $assistant,
        ]);
    }

    /**
     * POST /api/essay/export-docx
     * Accepts JSON payload from frontend (title, extracted, corrected, scores, criterion_explanations, revision_suggestions, inline_diff_html, raw_grade_payload)
     * Returns a streamed docx download.
     */
    public function exportDocx(Request $request)
    {
        $payload = $request->all();

        // Use PhpWord to build docx
        try {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection(['marginTop' => 600, 'marginBottom' => 600]);

            $title = $payload['title'] ?? 'Essay Report';
            $section->addTitle(htmlentities($title), 1);

            // Metadata / Scores (only include if present)
            $scores = $payload['scores'] ?? ($payload['raw_grade_payload']['scores'] ?? null);
            if ($scores && is_array($scores)) {
                $section->addTextBreak(1);
                $section->addText('Scores:', ['bold' => true]);
                $table = $section->addTable();
                $table->addRow();
                $table->addCell(4000)->addText('Criterion', ['bold' => true]);
                $table->addCell(2000)->addText('Score', ['bold' => true]);
                foreach (['content','communicative','organisation','language','total'] as $k) {
                    if (isset($scores[$k])) {
                        $table->addRow();
                        $table->addCell(4000)->addText(ucfirst($k));
                        $table->addCell(2000)->addText((string)$scores[$k]);
                    }
                }
            }

            // Criterion Explanations
            $explanations = $payload['criterion_explanations'] ?? $payload['explanations'] ?? [];
            if ($explanations && is_array($explanations) && count($explanations)) {
                $section->addTextBreak(1);
                $section->addText('Criterion Explanations:', ['bold' => true]);
                foreach ($explanations as $ex) {
                    $section->addListItem(htmlentities((string)$ex), 0);
                }
            }

            // Revision suggestions
            $revs = $payload['revision_suggestions'] ?? $payload['revision_suggestions'] ?? $payload['revisionSuggestions'] ?? $payload['revision_suggestions'] ?? ($payload['raw_grade_payload']['suggestions'] ?? []);
            if ($revs && is_array($revs) && count($revs)) {
                $section->addTextBreak(1);
                $section->addText('Revision Suggestions:', ['bold' => true]);
                foreach ($revs as $r) {
                    $section->addListItem(htmlentities((string)$r), 0);
                }
            }

            // Inline diff (HTML) — many teachers want this; insert as HTML if available
            $inline = $payload['inline_diff_html'] ?? ($payload['raw_grade_payload']['inline_diff_html'] ?? '');
            if ($inline) {
                $section->addTextBreak(1);
                $section->addText('Inline Diff:', ['bold' => true]);
                // PhpWord supports basic HTML via Html::addHtml
                try {
                    PhpWordHtml::addHtml($section, $inline, false, false);
                } catch (\Throwable $e) {
                    // fallback: strip tags and show text
                    $section->addText(strip_tags($inline));
                }
            }

            // Original and Corrected
            $original = $payload['original_text'] ?? $payload['extracted'] ?? '';
            $corrected = $payload['corrected'] ?? '';

            if ($original) {
                $section->addTextBreak(1);
                $section->addText('Original Essay:', ['bold' => true]);
                $section->addText(htmlentities($original));
            }
            if ($corrected) {
                $section->addTextBreak(1);
                $section->addText('Corrected Essay:', ['bold' => true]);
                $section->addText(htmlentities($corrected));
            }

            // Stream download
            $filename = Str::slug(substr($title, 0, 60)) ?: 'essay-report';
            $filename .= '-' . date('Ymd-His') . '.docx';

            $tempFile = sys_get_temp_dir() . '/' . $filename;
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);

            // Return streamed response with proper headers
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            Log::error('DOCX export failed: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'DOCX export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/essay/export-docx-test
     * Simple smoke test docx to verify route and downloads
     */
    public function exportDocxSmoke(Request $request)
    {
        try {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();
            $section->addTitle('Hello — DOCX Smoke Test', 1);
            $section->addText('This is a smoke test document to verify .docx download route.');

            $filename = 'smoke-essay-test-' . date('Ymd-His') . '.docx';
            $tempFile = sys_get_temp_dir() . '/' . $filename;
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            Log::error('DOCX smoke failed: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'DOCX smoke failed: ' . $e->getMessage()], 500);
        }
    }
}
