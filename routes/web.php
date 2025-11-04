<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SolveController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\EssayController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 🏠 Home
Route::get('/', fn() => view('home'))->name('home');

// 📘 Quiz Solver
Route::get('/solve', [SolveController::class, 'index'])->name('solve.index');

// ✍️ English Corrector
Route::get('/corrector', fn() => view('corrector'))->name('corrector.index');

// 🧠 Quiz Generator
Route::get('/generator', fn() => view('generator'))->name('generator.index');

// 🏫 AI Grader（页面）
Route::get('/grader', [GradeController::class, 'index'])->name('grader');
Route::post('/grader', [GradeController::class, 'evaluate'])->name('grader.evaluate');

// 📝 Essay Pro（新页面）
Route::get('/essay-pro', [EssayController::class, 'index'])->name('essay.pro');

// 🧾 Env check（可选）
Route::get('/envcheck', function () {
    return response()->json([
        'app_url'     => env('APP_URL'),
        'model'       => env('OPENAI_MODEL'),
        'key_exists'  => (bool) env('OPENAI_API_KEY'),
        'key_preview' => substr(env('OPENAI_API_KEY') ?? '', 0, 8),
    ]);
});
