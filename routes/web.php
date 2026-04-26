<?php

use App\Http\Controllers\AnalysisStreamController;
use App\Livewire\MedicationReview;
use Illuminate\Support\Facades\Route;

Route::get('/', MedicationReview::class)->name('home');

// Demo-pagina: rendert het review- of rapport-scherm met mock-data uit
// App\Demo\MockAnalysis zodat UI-wijzigingen zonder analyse-call zichtbaar
// zijn. Gebruik /demo (default review) of /demo/rapport.
Route::get('/demo/{demo?}', MedicationReview::class)
    ->where('demo', 'review|rapport')
    ->defaults('demo', 'review')
    ->name('demo');

// Streaming-endpoint voor de MBO-analyse. Gebruikt de web-middleware zodat
// session (demo-unlock) en CSRF beschikbaar zijn.
Route::post('/analyze-stream', [AnalysisStreamController::class, 'stream'])
    ->name('analyze-stream');
