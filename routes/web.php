<?php

use App\Http\Controllers\AnalysisStreamController;
use App\Livewire\MedicationReview;
use Illuminate\Support\Facades\Route;

Route::get('/', MedicationReview::class)->name('home');

// Streaming-endpoint voor de MBO-analyse. Gebruikt de web-middleware zodat
// session (demo-unlock) en CSRF beschikbaar zijn.
Route::post('/analyze-stream', [AnalysisStreamController::class, 'stream'])
    ->name('analyze-stream');
