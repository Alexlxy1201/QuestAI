<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SolveController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// === 🏠 Default Home Page ===
Route::get('/', function () {
    return view('home');
})->name('home');

// === 📘 AI Quiz Solver ===
Route::get('/solve', [SolveController::class, 'index'])->name('solve.index');
Route::post('/api/solve', [SolveController::class, 'solve'])
    ->name('solve.api');

// === ✍️ AI English Corrector ===
Route::get('/corrector', function () {
    return view('corrector');
})->name('corrector.index');
Route::post('/api/correct', function (Request $request) {
    $text = $request->input('text');
    if (!$text) {
        return response()->json(['ok' => false, 'error' => 'No text provided']);
    }
    $mock = [
        'original' => $text,
        'corrected' => 'He goes to school every day.',
        'explanations' => [
            "Verb agreement: 'go' → 'goes' for third-person singular.",
            "Plural form: 'days' → 'day' (every day = each day)."
        ]
    ];
    return response()->json(['ok' => true, 'data' => $mock]);
});

// === 🧠 AI Quiz Generator ===
Route::get('/generator', function () {
    return view('generator');
})->name('generator.index');
Route::post('/api/generate-quiz', function (Request $request) {
    $text = $request->input('text');
    $count = $request->input('count', 5);
    if (!$text) {
        return response()->json(['ok' => false, 'error' => 'No text provided']);
    }
    return response()->json([
        'ok' => true,
        'data' => [
            'questions' => [
                ['type' => 'true_false', 'question' => 'The sun rises in the west.', 'answer' => 'False']
            ]
        ]
    ]);
});
