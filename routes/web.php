<?php

use App\Livewire\MedicationReview;
use Illuminate\Support\Facades\Route;

Route::get('/', MedicationReview::class)->name('home');
