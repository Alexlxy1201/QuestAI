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
        Log::info('📥 Incoming /api/solve request', [
            'has_base64' => $request->has('image'),
            'has_file' => $request->hasFile('image'),
        ]);

        $base64 = $request->input('image');
        $imageFile = $request->file('image');

        if (!$base64 && !$imageFile) {
            return response()->json([
                'ok' => false,
                'error' => 'No image provided. Please upload or take a photo.'
            ], 400);
        }

        // ✅ 生成完整的 data URL
        if ($base64 && str_starts_with($base64, 'data:image/')) {
            $imageUrl = $base64;
        } elseif ($imageFile) {
            $mime = $imageFile->getMimeType() ?: 'image/png';
            $imageUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($imageFile->getRealPath()));
        } else {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid image format.'
            ], 400);
        }

        // ✅ MOCK 模式（测试）
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
                    'knowledge_points' => ['Linear equation', 'Inverse operations', 'Basic algebra']
                ],
                'mock' => true
            ]);
        }

        $apiKey = env('OPENAI_API_KEY');
        $model  = env('OPENAI_MODEL', 'gpt-4o-mini');
        $base   = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');

        if (!$apiKey) {
            Log::error('❌ Missing OPENAI_API_KEY');
            return response()->json([
                'ok' => false,
                'error' => 'OPENAI_API_KEY missing. Set it in Railway Variables.'
            ], 500);
        }

        $system = <<<SYS
You are a precise question-solving tutor. Given a photo of a question (math/science/general), do the following:
1) Extract the question clearly.
2) Solve it and give the concise answer.
3) Provide 3–7 reasoning steps as a string array.
4) List 3–6 related knowledge points.
Return pure JSON with this exact structure: {"question": "...", "answer": "...", "reasoning": ["step1", "step2", ...], "knowledge_points": ["point1", "point2", ...]}
SYS;

        try {
            $start = microtime(true);

            // ✅ 修复后的 payload：使用标准 Chat Completions API 格式
            $payload = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $system
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Solve this question and return JSON with the exact structure specified in the system prompt.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageUrl
                                ]
                            ]
                        ]
                    ]
                ],
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object']
            ];

            Log::info('🚀 Sending request to OpenAI', [
                'endpoint' => $base . '/chat/completions',
                'model' => $model,
                'image_size' => strlen($imageUrl),
            ]);

            $resp = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->withOptions([
                'verify' => true,
                'timeout' => 45,
            ])->post($base . '/chat/completions', $payload);

            $elapsed = round(microtime(true) - $start, 2);
            Log::info('📤 OpenAI response metadata', [
                'status' => $resp->status(),
                'ok' => $resp->ok(),
                'elapsed_seconds' => $elapsed,
            ]);

            if (!$resp->ok()) {
                Log::error('❌ Upstream error from OpenAI', [
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);

                return response()->json([
                    'ok' => false,
                    'error' => 'Upstream error from OpenAI',
                    'status' => $resp->status(),
                    'details' => $resp->json() ?? $resp->body(),
                ], 502);
            }

            $json = $resp->json();
            
            // ✅ 修复后的响应解析：使用标准 Chat Completions 格式
            $content = $json['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('⚠️ JSON parse failed', ['raw_content' => $content]);
                $parsed = [
                    'question' => '(Parse failed)',
                    'answer' => $content,
                    'reasoning' => ['Model returned non-JSON response.'],
                    'knowledge_points' => []
                ];
            }

            
            $parsed = [
                'question' => $parsed['question'] ?? '(No question extracted)',
                'answer' => $parsed['answer'] ?? '(No answer provided)',
                'reasoning' => $parsed['reasoning'] ?? [],
                'knowledge_points' => $parsed['knowledge_points'] ?? []
            ];

            Log::info('✅ Parsed JSON successfully', [
                'question' => $parsed['question'],
                'answer' => $parsed['answer'],
                'elapsed_seconds' => $elapsed
            ]);

            return response()->json([
                'ok' => true,
                'data' => $parsed
            ]);

        } catch (\Throwable $e) {
            Log::error('💥 Exception during processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }
    
}