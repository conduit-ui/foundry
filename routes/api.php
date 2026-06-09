<?php

use App\Http\Controllers\ScaffoldController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/scaffold', [ScaffoldController::class, 'store'])->name('scaffold.store');
Route::get('/scaffold/{id}', [ScaffoldController::class, 'show'])->name('scaffold.show');
