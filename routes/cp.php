<?php


use Illuminate\Support\Facades\Route;
use Kreatif\Translum\Http\Controllers\TranslumController;

Route::get('/translum', [TranslumController::class, 'index'])->name('translum.index');
Route::post('/translum', [TranslumController::class, 'update'])->name('translum.update');

