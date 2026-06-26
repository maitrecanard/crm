<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\MaintenanceApiController;
use App\Http\Controllers\Api\ProspectApiController;
use App\Http\Controllers\Api\SupportApiController;
use App\Http\Controllers\Api\TenderApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentification mobile : échange identifiants -> token.
Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:6,1');

// --- API d'assistance (site du client) : cloisonnée par token client (150 car.) ---
Route::prefix('support')
    ->middleware([\App\Http\Middleware\ResolveSupportClient::class, 'throttle:60,1'])
    ->group(function () {
        Route::get('/motifs', [SupportApiController::class, 'motifs']);
        Route::get('/tickets', [SupportApiController::class, 'index']);
        Route::post('/tickets', [SupportApiController::class, 'store']);
        Route::get('/tickets/{reference}', [SupportApiController::class, 'show']);
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/me', [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // --- App mobile : lecture / suivi ---
    Route::get('/stats', [ProspectApiController::class, 'stats']);
    Route::get('/prospects', [ProspectApiController::class, 'index']);
    Route::get('/prospects/{prospect}', [ProspectApiController::class, 'show']);
    // PATCH est bloqué par o2switch (LiteSpeed coupe la connexion -> "http2 protocol error"). On utilise PUT.
    Route::put('/prospects/{prospect}', [ProspectApiController::class, 'updateSuivi']);
    Route::post('/prospects/{prospect}/interactions', [ProspectApiController::class, 'addInteraction']);
    Route::get('/tenders', [TenderApiController::class, 'index']);
    Route::get('/tenders/{tender}', [TenderApiController::class, 'show']);

    // --- Moteur de prospection (SDK) : ingestion ---
    Route::post('/prospects', [ProspectApiController::class, 'store']);
    Route::post('/prospects/bulk', [ProspectApiController::class, 'bulk']);
    Route::post('/tenders/bulk', [TenderApiController::class, 'bulk']);
    Route::post('/tenders/dossier', [TenderApiController::class, 'attachDossier']);

    // --- Maintenance à distance (migrations) déclenchable par le SDK ---
    Route::get('/maintenance/migrate-status', [MaintenanceApiController::class, 'status']);
    Route::post('/maintenance/migrate', [MaintenanceApiController::class, 'migrate']);
    Route::post('/maintenance/rotate-token', [MaintenanceApiController::class, 'rotateToken']);
    Route::post('/maintenance/test-mail', [MaintenanceApiController::class, 'testMail']);
});
