<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SolveController;
use App\Http\Controllers\GradeController;

// === 🏠 Home Page ===
Route::get('/', function () {
    return view('home');
})->name('home');

// === 📘 Quiz Solver Page ===
Route::get('/solve', [SolveController::class, 'index'])->name('solve.index');

// === ✍️ English Corrector Page ===
Route::get('/corrector', fn() => view('corrector'))->name('corrector.index');

// === 🧠 Quiz Generator Page ===
Route::get('/generator', fn() => view('generator'))->name('generator.index');

// === 🏫 AI Grader Page ===
Route::get('/grader', [GradeController::class, 'index'])->name('grader');
Route::post('/grader', [GradeController::class, 'evaluate'])->name('grader.evaluate');

// === 🧾 Environment check ===
Route::get('/envcheck', function () {
    return response()->json([
        'app_url' => env('APP_URL'),
        'model' => env('OPENAI_MODEL'),
        'key_exists' => env('OPENAI_API_KEY') ? true : false,
        'key_preview' => substr(env('OPENAI_API_KEY') ?? '', 0, 8),
    ]);
});
