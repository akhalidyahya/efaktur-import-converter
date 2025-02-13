<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

Route::get('/', [PageController::class, 'index'])->name('index');
Route::post('/upload', [PageController::class, 'upload'])->name('upload');