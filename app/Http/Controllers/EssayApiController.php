<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EssayApiController extends Controller
{
    /**
     * OCR proxy endpoint.
     * Requires OCR_API_URL in .env (and optional OCR_API_KEY).
     */
    public function ocr(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['ok' => false, 'error' => 'No file uploaded.'], 400);
        }

        $file = $request->file('file');
        $ocrUrl = rtrim(env('OCR_API_URL', ''), '/');
        $ocrKey = env('OCR_API_KEY', '');

        if (!$ocrUrl) {
            return response()->json([
                'ok' => false,
                'error' => 'OCR not configured. Set OCR_API_URL in .env or implement OCR server-side.'
            ], 422);
        }

        try {
            $response = Http::withHeaders($ocrKey ? ['Authorization' => "Bearer {$ocrKey}"] : [])
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($ocrUrl);

            if (!$response->ok()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'OCR server error',
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200)
                ], 502);
            }

            $json = $response->json();
            if (is_array($json)) {
                $text = $json['text'] ?? $json['extracted'] ?? $json['ocr'] ?? $json['result'] ?? null;
                if (is_array($text)) {
                    $text = implode("\n\n", $text);
                }
                return response()->json(['ok' => true, 'text' => (string)($text ?? $response->body())]);
            }

            return response()->json(['ok' => true, 'text' => (string)$response->body()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'OCR request failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Grade endpoint: ask LLM to return structured JSON when possible.
     * Accepts JSON payload: { title, rubric_code, rubric_text, text, prompt_instructions }
     */
    public function grade(Request $request)
    {
        $body = $request->json()->all();
        $text = trim($body['text'] ?? '');
        $title = trim($body['title'] ?? 'Essay');
        $rubricCode = trim($body['rubric_code'] ?? '');
        $rubricText = trim($body['rubric_text'] ?? '');
        $instructions = trim($body['prompt_instructions'] ?? '');

        if (!$text) {
            return response()->json(['ok' => false, 'error' => 'No text provided.'], 400);
        }

        $openaiKey = env('OPENAI_API_KEY', '');
        $base = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $model = env('OPENAI_MODEL', 'gpt-4o-mini');

        if (!$openaiKey) {
            return response()->json(['ok' => false, 'error' => 'OPENAI_API_KEY not configured'], 500);
        }

        $system = "You are an accurate automated essay assessor. When possible return only JSON. If you have to provide additional commentary, still include a JSON block with the keys requested.";

        // Build user prompt parts
        $userParts = [];
        if ($rubricText) {
            $userParts[] = "Rubric (verbatim):\n" . $rubricText;
        } elseif ($rubricCode) {
            $userParts[] = "Rubric code: " . $rubricCode;
        }
        $userParts[] = "Student essay text:\n" . $text;

        // Request an explicit JSON structure
        $userParts[] = "Please return a JSON object with these fields:\n" .
            "{\n" .
            "  \"scores\": {\"content\": number(0-5), \"communicative\": number(0-5), \"organisation\": number(0-5), \"language\": number(0-5), \"total\": number(0-20)},\n" .
            "  \"rationales\": [\"explanations per criterion or general comments\"],\n" .
            "  \"suggestions\": [\"revision suggestion strings\"],\n" .
            "  \"inline_diff_html\": \"(optional) HTML snippet with <ins>/<del>\",\n" .
            "  \"original_text\": \"(original essay)\",\n" .
            "  \"corrected_text\": \"(optional corrected essay)\",\n" .
            "  \"raw_text\": \"(model full output for debugging)\"\n" .
            "}\n\nIf you cannot return strict JSON, still include your textual analysis; the API will return that as raw_text.";

        if ($instructions) $userParts[] = "Extra instructions:\n" . $instructions;

        $user = implode("\n\n---\n\n", $userParts);

        try {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$openaiKey}",
                'Content-Type' => 'application/json'
            ])->post("{$base}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => 0.0,
                'max_tokens' => 1400,
            ]);

            if (!$resp->ok()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'LLM returned non-OK status',
                    'status' => $resp->status(),
                    'body' => substr($resp->body(), 0, 1000),
                ], 502);
            }

            $rjson = $resp->json();
            $choiceContent = $rjson['choices'][0]['message']['content']
                ?? $rjson['choices'][0]['text'] ?? $resp->body();

            // Try to extract JSON block from the model output
            $parsed = $this->tryExtractJson($choiceContent);

            if (is_array($parsed)) {
                // Ensure expected keys exist (normalize)
                $out = [
                    'ok' => true,
                    'scores' => $parsed['scores'] ?? ($parsed['score'] ?? $parsed['score_map'] ?? (object)[]),
                    'rationales' => $parsed['rationales'] ?? $parsed['explanations'] ?? $parsed['criteria_explanations'] ?? $parsed['rubric_breakdown'] ?? [],
                    'suggestions' => $parsed['suggestions'] ?? $parsed['revision_suggestions'] ?? [],
                    'inline_diff_html' => $parsed['inline_diff_html'] ?? $parsed['inline_diff'] ?? '',
                    'original_text' => $parsed['original_text'] ?? $parsed['extracted'] ?? $text,
                    'corrected_text' => $parsed['corrected_text'] ?? $parsed['corrected'] ?? null,
                    'raw_text' => $choiceContent,
                ];
                return response()->json($out);
            }

            // Fallback: return wrapper with raw_text
            return response()->json([
                'ok' => true,
                'scores' => (object)[],
                'rationales' => [],
                'suggestions' => [],
                'inline_diff_html' => '',
                'original_text' => $text,
                'corrected_text' => null,
                'raw_text' => $choiceContent,
                '_meta' => [
                    'notice' => 'model did not return parseable JSON; raw_text contains model output'
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'LLM request failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * directCorrect: correct and explain grammar/clarity for given text.
     * Returns: { ok, extracted, corrected, explanations[], raw }
     */
    public function directCorrect(Request $request)
    {
        $text = trim($request->input('text', $request->json('text', '')));
        if (!$text) {
            return response()->json(['ok' => false, 'error' => 'No text provided.'], 400);
        }

        $openaiKey = env('OPENAI_API_KEY', '');
        $base = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $model = env('OPENAI_MODEL', 'gpt-4o-mini');

        if (!$openaiKey) {
            return response()->json(['ok' => false, 'error' => 'OPENAI_API_KEY not configured'], 500);
        }

        $system = "You are an English writing corrector. Correct grammar, punctuation and clarity while preserving meaning. Provide a short explanation list of major edits.";
        $user = "Return a JSON object: { original: \"...\", corrected: \"...\", explanations: [\"...\"] }\n\nText:\n\"\"\"\n{$text}\n\"\"\"\n";

        try {
            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$openaiKey}",
                'Content-Type' => 'application/json'
            ])->post("{$base}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => 0.0,
                'max_tokens' => 800,
            ]);

            if (!$resp->ok()) {
                return response()->json(['ok' => false, 'error' => 'LLM error', 'status' => $resp->status(), 'body' => substr($resp->body(), 0, 500)], 502);
            }

            $rjson = $resp->json();
            $choiceContent = $rjson['choices'][0]['message']['content'] ?? $rjson['choices'][0]['text'] ?? $resp->body();
            $parsed = $this->tryExtractJson($choiceContent);

            if (is_array($parsed)) {
                $original = $parsed['original'] ?? $parsed['input'] ?? $text;
                $corrected = $parsed['corrected'] ?? $parsed['output'] ?? $parsed['corrections'] ?? $choiceContent;
                $explanations = $parsed['explanations'] ?? $parsed['notes'] ?? [];
                return response()->json([
                    'ok' => true,
                    'extracted' => $original,
                    'corrected' => $corrected,
                    'explanations' => $explanations,
                    'raw' => $choiceContent
                ]);
            }

            // fallback
            return response()->json([
                'ok' => true,
                'extracted' => $text,
                'corrected' => $choiceContent,
                'explanations' => [],
                'raw' => $choiceContent,
                'notice' => 'Model did not return strict JSON; returned raw content in corrected.'
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'LLM request failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * exportDocx: accept JSON report payload and return normalized JSON report.
     * Frontend will generate .docx from this report.
     */
    public function exportDocx(Request $request)
    {
        $payload = $request->json()->all() ?: $request->all();
        if (empty($payload)) {
            return response()->json(['ok' => false, 'error' => 'No payload provided. POST JSON with report fields.'], 400);
        }

        $report = [
            'title' => $payload['title'] ?? 'Essay Report',
            'rubric_code' => $payload['rubric_code'] ?? ($payload['rubric'] ?? null),
            'rubric_text' => $payload['rubric_text'] ?? null,
            'extracted' => $payload['extracted'] ?? $payload['original_text'] ?? '',
            'corrected' => $payload['corrected'] ?? $payload['corrected_text'] ?? '',
            'scores' => $payload['scores'] ?? $payload['score'] ?? (object)[],
            'criterion_explanations' => $payload['criterion_explanations'] ?? $payload['explanations'] ?? ($payload['rationales'] ?? []),
            'revision_suggestions' => $payload['revision_suggestions'] ?? $payload['suggestions'] ?? [],
            'inline_diff_html' => $payload['inline_diff_html'] ?? '',
            'raw_grade_payload' => $payload['raw_grade_payload'] ?? ($payload['raw'] ?? $payload['raw_text'] ?? null),
        ];

        return response()->json(['ok' => true, 'report' => $report]);
    }

    /**
     * Smoke test: return a simple HTML file as MS Word attachment for debugging hosting behavior.
     */
    public function exportDocxSmoke(Request $request)
    {
        $html = <<<HTML
<!doctype html>
<html>
<head><meta charset="utf-8"><title>smoke.doc</title></head>
<body>
<h1>Essay Export - Smoke Test</h1>
<p>If you downloaded this file and Word can open it, the hosting route/headers are working.</p>
</body>
</html>
HTML;

        return response($html, 200)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', 'attachment; filename="smoke-test.doc"');
    }

    /**
     * Try to extract a JSON object from a model string.
     * - strips code fences, then searches for {...} or [...] and json_decodes.
     */
    protected function tryExtractJson($content)
    {
        if (!is_string($content)) return null;

        // Remove triple-backtick blocks but keep inner content candidate
        $clean = preg_replace('/```(?:json)?\r?\n(.*?)\r?\n```/s', '$1', $content);

        // Try direct decode
        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Find first {...} block (balanced) using recursive regex
        if (preg_match('/(\{(?:[^{}]|(?R))*\})/s', $clean, $m)) {
            $candidate = $m[1];
            $d = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE) return $d;
        }

        // Find first [...] block
        if (preg_match('/(\[(?:[^\[\]]|(?R))*\])/s', $clean, $m2)) {
            $candidate = $m2[1];
            $d = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE) return $d;
        }

        return null;
    }
}
