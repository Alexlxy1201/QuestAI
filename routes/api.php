<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\SolveController;
use App\Http\Controllers\GradeController;

/*
|--------------------------------------------------------------------------
| ✅ Real OpenAI-powered APIs
|--------------------------------------------------------------------------
*/

// === ✍️ English Corrector ===
Route::post('/correct', function (Request $request) {
    $text = trim($request->input('text', ''));
    if (!$text) {
        return response()->json(['ok' => false, 'error' => 'No text provided.']);
    }

    $apiKey = env('OPENAI_API_KEY');
    $model = env('OPENAI_MODEL', 'gpt-4o-mini');
    $base = env('OPENAI_BASE_URL', 'https://api.openai.com/v1');

    try {
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

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$base}/chat/completions", [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an accurate English writing corrector.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object']
        ]);

        $content = $response->json()['choices'][0]['message']['content'] ?? '{}';
        $json = json_decode($content, true);

        return response()->json(['ok' => true, 'data' => $json]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()]);
    }
});

// === 🧠 Quiz Generator ===
Route::post('/generate-quiz', function (Request $request) {
    $text = trim($request->input('text', ''));
    $count = intval($request->input('count', 5));
    if (!$text) {
        return response()->json(['ok' => false, 'error' => 'No text provided']);
    }

    $apiKey = env('OPENAI_API_KEY');
    $model = env('OPENAI_MODEL', 'gpt-4o-mini');
    $base = env('OPENAI_BASE_URL', 'https://api.openai.com/v1');

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
            'Content-Type' => 'application/json',
        ])->post("{$base}/chat/completions", [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an English question generator.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.6,
            'max_tokens' => 800,
            'response_format' => ['type' => 'json_object']
        ]);

        $content = $response->json()['choices'][0]['message']['content'] ?? '{}';
        $json = json_decode($content, true);

        return response()->json(['ok' => true, 'data' => $json]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()]);
    }
});

// === 📘 Quiz Solver ===
Route::post('/solve', [SolveController::class, 'solve']);

// === 🏫 AI Grader ===
Route::post('/grader', [GradeController::class, 'evaluate']);
