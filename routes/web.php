<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SolveController;

// === 🏠 Home Page ===
Route::get('/home', fn() => view('home'))->name('home');
Route::redirect('/', '/home');

// === 📘 Quiz Solver Page ===
Route::get('/solve', [SolveController::class, 'index'])->name('solve.index');

// === ✍️ English Corrector Page ===
Route::get('/corrector', fn() => view('corrector'))->name('corrector.index');

// === 🧠 Quiz Generator Page ===
Route::get('/generator', fn() => view('generator'))->name('generator.index');

// === 🧾 Environment check ===
Route::get('/envcheck', function () {
    return response()->json([
        'app_url' => env('APP_URL'),
        'model' => env('OPENAI_MODEL'),
        'key_exists' => env('OPENAI_API_KEY') ? true : false,
        'key_preview' => substr(env('OPENAI_API_KEY') ?? '', 0, 8),
    ]);
});
