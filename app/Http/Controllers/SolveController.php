<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class SolveController extends Controller
{
    public function index()
    {
        return view('solve');
    }

    public function solve(Request $request): JsonResponse
    {
        Log::info('📥 Incoming /api/solve', [
            'mode' => $request->input('mode'),
            'has_pdf' => $request->hasFile('pdf'),
            'has_image_b64' => $request->has('image'),
            'has_image_file' => $request->hasFile('image'),
        ]);

        // Quick MOCK
        if (env('MOCK', false)) {
            return response()->json([
                'ok' => true,
                'data' => [
                    'question' => 'If 3x + 5 = 20, what is x?',
                    'answer' => 'x = 5',
                    'reasoning' => [
                        'Subtract 5 from both sides: 3x = 15',
                        'Divide both sides by 3: x = 5'
                    ],
                    'knowledge_points' => ['Linear equation', 'Inverse operations', 'Basic algebra'],
                    'confidence' => 98
                ],
                'mock' => true,
            ]);
        }

        $apiKey = env('OPENAI_API_KEY');
        $base   = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $chatModel = env('OPENAI_MODEL', 'gpt-4o-mini'); // for image
        $fileModel = env('OPENAI_FILE_MODEL', $chatModel); // for PDF; 可与上面相同

        if (!$apiKey) {
            return response()->json(['ok' => false, 'error' => 'OPENAI_API_KEY missing'], 500);
        }

        // ---------- 分支 A：PDF 直传（准确度最佳） ----------
        if ($request->input('mode') === 'direct_pdf' && $request->hasFile('pdf')) {
            $pdf = $request->file('pdf');
            if (!$pdf->isValid()) {
                return response()->json(['ok'=>false, 'error'=>'Invalid PDF'], 422);
            }
            if ($pdf->getSize() > 20 * 1024 * 1024) {
                return response()->json(['ok'=>false, 'error'=>'PDF too large (max 20MB)'], 413);
            }

            try {
                // 1) 上传 PDF 至 Files
                $upload = Http::withToken($apiKey)
                    ->asMultipart()
                    ->post($base.'/files', [
                        ['name' => 'purpose', 'contents' => 'user_data'],
                        [
                            'name'     => 'file',
                            'contents' => fopen($pdf->getRealPath(), 'r'),
                            'filename' => $pdf->getClientOriginalName(),
                        ],
                    ])
                    ->json();

                if (empty($upload['id'])) {
                    return response()->json(['ok'=>false, 'error'=>'Upload to OpenAI failed', 'debug'=>$upload], 502);
                }
                $fileId = $upload['id'];

                // 2) 让模型读取该 PDF（Responses API）
                $prompt = <<<PROMPT
请阅读这个 PDF 中的题目（若为选择题请列出选项），给出清晰的解题过程与最终答案。
严格返回 JSON（不要额外说明）：
{
  "question": "...",
  "options": ["..."],
  "answer": "...",
  "reasoning": ["...", "..."],
  "knowledge_points": ["...", "..."],
  "confidence": 0-100
}
PROMPT;

                $payload = [
                    'model' => $fileModel,
                    'input' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => $prompt],
                            ['type' => 'input_file', 'file_id' => $fileId],
                        ],
                    ]],
                ];

                $resp = Http::withToken($apiKey)
                    ->post($base.'/responses', $payload)
                    ->json();

                $text = $resp['output_text'] ?? null;
                $parsed = $this->tryParseJson($text);

                return response()->json([
                    'ok' => true,
                    'mode' => 'pdf',
                    'data' => $parsed ?: ['raw' => $text],
                ]);
            } catch (\Throwable $e) {
                Log::error('PDF pipeline failed', ['e'=>$e->getMessage()]);
                return response()->json(['ok'=>false, 'error'=>'PDF processing failed: '.$e->getMessage()], 500);
            }
        }

        // ---------- 分支 B：图片（保持你的 Chat Completions 路线） ----------
        $base64 = $request->input('image');
        $imageFile = $request->file('image');

        if (!$base64 && !$imageFile) {
            return response()->json([
                'ok' => false,
                'error' => 'No image provided. Please upload or take a photo (or send a PDF).'
            ], 400);
        }

        // 生成 data URL
        if ($base64 && str_starts_with($base64, 'data:image/')) {
            $imageUrl = $base64;
        } elseif ($imageFile) {
            $mime = $imageFile->getMimeType() ?: 'image/png';
            $imageUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($imageFile->getRealPath()));
        } else {
            return response()->json(['ok' => false, 'error' => 'Invalid image format.'], 400);
        }

        $system = <<<SYS
You are a precise question-solving tutor. Given a photo of a question (math/science/general), do the following:
1) Extract the question clearly (and options if any).
2) Solve it and give the concise answer.
3) Provide 3–7 reasoning steps as an array of strings.
4) List 3–6 related knowledge points.
5) Provide a 0–100 confidence score (integer).
Return pure JSON with this exact structure:
{"question":"...","options":["..."],"answer":"...","reasoning":["..."],"knowledge_points":["..."],"confidence": 0-100}
SYS;

        try {
            $payload = [
                'model' => $chatModel,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Solve this question and return JSON with the exact structure.'],
                            ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                        ]
                    ],
                ],
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
            ];

            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->withOptions([
                'verify'  => true,
                'timeout' => 60,
            ])->post($base . '/chat/completions', $payload);

            if (!$resp->ok()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Upstream error from OpenAI',
                    'status' => $resp->status(),
                    'details' => $resp->json() ?? $resp->body(),
                ], 502);
            }

            $json = $resp->json();
            $content = $json['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $parsed = [
                    'question' => '(Parse failed)',
                    'answer' => $content,
                    'reasoning' => ['Model returned non-JSON response.'],
                    'knowledge_points' => [],
                    'confidence' => null,
                ];
            }

            // 兜底字段
            $parsed = [
                'question' => $parsed['question'] ?? '(No question extracted)',
                'options' => $parsed['options'] ?? [],
                'answer' => $parsed['answer'] ?? '(No answer provided)',
                'reasoning' => $parsed['reasoning'] ?? [],
                'knowledge_points' => $parsed['knowledge_points'] ?? [],
                'confidence' => $parsed['confidence'] ?? null,
            ];

            return response()->json(['ok' => true, 'mode'=>'image', 'data' => $parsed]);
        } catch (\Throwable $e) {
            Log::error('Image pipeline failed', ['e'=>$e->getMessage()]);
            return response()->json(['ok'=>false, 'error'=>'Server error: '.$e->getMessage()], 500);
        }
    }

    private function tryParseJson(?string $text): ?array
    {
        if (!$text) return null;
        $trim = ltrim($text);
        // 提取首个 JSON 对象
        if ($trim[0] !== '{') {
            $pos = strpos($trim, '{');
            if ($pos !== false) $trim = substr($trim, $pos);
        }
        $trim = rtrim($trim);
        // 简单截断到最后一个 }（以防有结尾噪音）
        $last = strrpos($trim, '}');
        if ($last !== false) $trim = substr($trim, 0, $last + 1);

        $decoded = json_decode($trim, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
    }
}
