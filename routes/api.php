<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\EssayApiController;

/*
|--------------------------------------------------------------------------
| Essay Pro APIs (no database writes)
|--------------------------------------------------------------------------
*/
Route::post('/essay/direct-correct', [EssayApiController::class, 'directCorrect'])->name('api.essay.directCorrect');
Route::post('/essay/export-docx',     [EssayApiController::class, 'exportDocx'])->name('api.essay.exportDocx');

Route::post('/ocr',   [EssayApiController::class, 'ocr'])->name('api.ocr');
Route::post('/grade', [EssayApiController::class, 'grade'])->name('api.grade');

/* 仅为兼容旧前端的占位接口；由于“仅本地存储”策略，这里返回 not supported */
Route::get('/essay/history',        [EssayApiController::class, 'history'])->name('api.essay.history');
Route::get('/essay/history/export', [EssayApiController::class, 'exportHistory'])->name('api.essay.export');
