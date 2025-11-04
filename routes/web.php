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
use App\Http\Middleware\VerifyCsrfToken;

// ...existing GET routes...

// Mirror POST /solve (web) -> same controller, WITHOUT CSRF
Route::post('/solve', [SolveController::class, 'solve'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('solve.post');
Route::post('/essay/export-docx', [EssayApiController::class, 'exportDocx'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]); // 取消 CSRF

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
use App\Http\Controllers\EssayApiController;

/*
|----------------------------------------------------------------------
| ⛳ Web fallback for Railway rewrite
| - 同样指向 exportDocx()
| - 取消 CSRF，允许前端直接 POST
|----------------------------------------------------------------------
*/
Route::post('/essay/export-docx-direct', [EssayApiController::class, 'exportDocx'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('web.essay.exportDocxDirect');

/* 可选：探针路由，浏览器打开应显示 "pong" */
Route::get('/essay/export-docx/ping', fn() => response('pong', 200));


// 兜底（免 CSRF），避免被静态托管重写到首页
Route::post('/essay/export-docx-direct', [EssayApiController::class, 'exportDocx'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('web.essay.exportDocxDirect');

// 健康探针（可选）
Route::get('/essay/export-docx/ping', fn() => response('pong', 200));
