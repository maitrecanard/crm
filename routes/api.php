<?php

use App\Http\Controllers\Api\ProspectApiController;
use App\Http\Controllers\Api\TenderApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    // Réception des prospects depuis le moteur de prospection (SDK).
    Route::post('/prospects', [ProspectApiController::class, 'store']);
    Route::post('/prospects/bulk', [ProspectApiController::class, 'bulk']);

    // Réception des appels d'offres.
    Route::post('/tenders/bulk', [TenderApiController::class, 'bulk']);
});
