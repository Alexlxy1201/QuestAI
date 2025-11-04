<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\SolveController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\EssayApiController;

/*
|--------------------------------------------------------------------------
| Essay Pro APIs
| 说明：
| - 仍保留老接口路径，避免前端 404。
| - 历史相关接口改为返回“本地存储策略，不再支持服务端历史”。
|--------------------------------------------------------------------------
*/
Route::post('/essay/direct-correct', [EssayApiController::class, 'directCorrect'])->name('api.essay.directCorrect');
Route::post('/essay/export-docx', [EssayApiController::class, 'exportDocx'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]); // 取消 CSRF

// 传统 OCR / 打分：依然可用（不强制落库）
Route::post('/ocr',   [EssayApiController::class, 'ocr'])->name('api.ocr');
Route::post('/grade', [EssayApiController::class, 'grade'])->name('api.grade');

Route::post('/essay/direct-correct', [EssayApiController::class, 'directCorrect'])->name('api.essay.directCorrect');
Route::post('/essay/export-docx',     [EssayApiController::class, 'exportDocx'])->name('api.essay.exportDocx');
Route::post('/ocr',   [EssayApiController::class, 'ocr'])->name('api.ocr');
Route::post('/grade', [EssayApiController::class, 'grade'])->name('api.grade');

// 历史：为了兼容老前端，但现在“仅本地存储”，服务端返回 410
Route::get('/essay/history', function () {
    return response()->json([
        'ok' => false,
        'error' => 'History is stored locally in the browser (localStorage) on this domain.',
    ], 410);
})->name('api.essay.history');

Route::get('/essay/history/export', function () {
    return response()->json([
        'ok' => false,
        'error' => 'Export from server is disabled. Use client export (localStorage) instead.',
    ], 410);
})->name('api.essay.export');

// ✍️ English Corrector（保留旧示例）
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

// Health：有人误用 GET /api/solve 就给提示
Route::get('/solve', fn () => response()->json(['ok' => true, 'hint' => 'Use POST /api/solve'], 200));

// Quiz Solver
Route::post('/solve', [SolveController::class, 'solve']);

// 🧠 Quiz Generator（保留旧示例）
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

// 🏫 AI Grader（如需单独接口）
Route::post('/grader', [GradeController::class, 'evaluate']);
