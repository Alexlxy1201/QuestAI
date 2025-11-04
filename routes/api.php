<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\SolveController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\EssayApiController;

/*
|--------------------------------------------------------------------------
| API Routes  (prefix: /api, 无 CSRF)
|--------------------------------------------------------------------------
| 注意：
| 1) 这里不要重复注册同一路由（你之前贴的文件里同一行出现了两次）。
| 2) /essay/export-docx-test 是“烟囱测试”，先确保下载 DOCX 正常。
|--------------------------------------------------------------------------
*/

// ========= Essay Pro =========

// 直接「提取+润色」
Route::post('/essay/direct-correct', [EssayApiController::class, 'directCorrect'])
    ->name('api.essay.directCorrect');

// 导出 DOCX（正式）
Route::post('/essay/export-docx', [EssayApiController::class, 'exportDocx'])
    ->name('api.essay.exportDocx');

// 导出 DOCX（烟囱测试：仅返回“Hello”文档，排查路由/响应头/平台重写）
Route::post('/essay/export-docx-test', [EssayApiController::class, 'exportDocxSmoke'])
    ->name('api.essay.exportDocxSmoke');

// 传统 OCR / 打分
Route::post('/ocr',   [EssayApiController::class, 'ocr'])->name('api.ocr');
Route::post('/grade', [EssayApiController::class, 'grade'])->name('api.grade');

// 历史：仅本地存储（兼容旧前端）
Route::get('/essay/history', fn () => response()->json([
    'ok' => false,
    'error' => 'History is stored locally (browser localStorage).',
], 410))->name('api.essay.history');

Route::get('/essay/history/export', fn () => response()->json([
    'ok' => false,
    'error' => 'Server-side export disabled. Use client export instead.',
], 410))->name('api.essay.history.export');

// ========= 其它保留示例 =========

// 英语纠错（示例）
Route::post('/correct', function (Request $request) {
    $text = trim($request->input('text', ''));
    if (!$text) return response()->json(['ok' => false, 'error' => 'No text provided.']);

    $apiKey = env('OPENAI_API_KEY');
    $model  = env('OPENAI_MODEL', 'gpt-4o-mini');
    $base   = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');

    $prompt = <<<PROMPT
You are an English grammar and clarity corrector.
Please correct the following text, then explain the corrections.
Return a JSON with:
{
  "original": "...",
  "corrected": "...",
  "explanations": ["..."]
}
Text: "{$text}"
PROMPT;

    try {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$base}/chat/completions", [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an accurate English writing corrector.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object']
        ]);

        $content = $response->json()['choices'][0]['message']['content'] ?? '{}';
        $json    = json_decode($content, true);

        return response()->json(['ok' => true, 'data' => $json]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()]);
    }
});

// Quiz Solver
Route::get('/solve', fn () => response()->json(['ok' => true, 'hint' => 'Use POST /api/solve'], 200));
Route::post('/solve', [SolveController::class, 'solve']);

// 题目生成（示例）
Route::post('/generate-quiz', function (Request $request) {
    $text  = trim($request->input('text', ''));
    $count = intval($request->input('count', 5));
    if (!$text) return response()->json(['ok' => false, 'error' => 'No text provided']);

    $apiKey = env('OPENAI_API_KEY');
    $model  = env('OPENAI_MODEL', 'gpt-4o-mini');
    $base   = rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');

    $prompt = <<<PROMPT
Generate {$count} English reading comprehension questions from the text below.
Return a JSON in this exact format:
{
  "questions": [
    {"type": "multiple_choice", "question": "...", "options": ["A.","B.","C.","D."], "answer": "A"},
    {"type": "true_false", "question": "...", "answer": "True"}
  ]
}
Text:
\"\"\"{$text}\"\"\" 
PROMPT;

    try {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$base}/chat/completions", [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an English question generator.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'temperature' => 0.6,
            'max_tokens' => 800,
            'response_format' => ['type' => 'json_object']
        ]);

        $content = $response->json()['choices'][0]['message']['content'] ?? '{}';
        $json    = json_decode($content, true);

        return response()->json(['ok' => true, 'data' => $json]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()]);
    }
});

// Grader（如果用得到）
Route::post('/grader', [GradeController::class, 'evaluate']);
