<?php

use App\Http\Controllers\Api\MatchController;
use Illuminate\Support\Facades\Route;

Route::get('/matches/{match}/odds', [MatchController::class, 'odds'])->name('api.matches.odds');
