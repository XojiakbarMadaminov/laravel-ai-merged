<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\IngestController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DebugController;

Route::get('/health', [HealthController::class, 'show']);
Route::post('/ingest', [IngestController::class, 'store']);
Route::post('/chat', [ChatController::class, 'chat']);
Route::post('/_debug/search', [DebugController::class, 'search']);
