<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SolveController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\EssayController;

/*
|--------------------------------------------------------------------------
| Web Routes  (页面 & 普通表单)
| - 不要在 web.php 里放 /essay/export-docx，这会被 CSRF/重写影响
|--------------------------------------------------------------------------
*/

// 🏠 Home
Route::get('/', fn () => view('home'))->name('home');

// 📘 Quiz Solver
Route::get('/solve', [SolveController::class, 'index'])->name('solve.index');

// ⚠️ 如果你确实需要一个“网页表单 POST /solve”且不想用 CSRF，才保留下面这行；否则删掉
// use App\Http\Middleware\VerifyCsrfToken;
// Route::post('/solve', [SolveController::class, 'solve'])
//     ->withoutMiddleware([VerifyCsrfToken::class])
//     ->name('solve.post');

// ✍️ English Corrector
Route::get('/corrector', fn () => view('corrector'))->name('corrector.index');

// 🧠 Quiz Generator
Route::get('/generator', fn () => view('generator'))->name('generator.index');

// 🏫 AI Grader（页面）
Route::get('/grader', [GradeController::class, 'index'])->name('grader');
Route::post('/grader', [GradeController::class, 'evaluate'])->name('grader.evaluate');

// 📝 Essay Pro（页面）
Route::get('/essay-pro', [EssayController::class, 'index'])->name('essay.pro');

// 🔍 环境检查（可选）
Route::get('/envcheck', function () {
    return response()->json([
        'app_url'     => env('APP_URL'),
        'model'       => env('OPENAI_MODEL'),
        'key_exists'  => (bool) env('OPENAI_API_KEY'),
        'key_preview' => substr(env('OPENAI_API_KEY') ?? '', 0, 8),
    ]);
});
