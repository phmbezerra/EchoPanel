<?php

use App\Http\Controllers\Api\EchoActivityController;
use App\Http\Controllers\Api\ServerCommandClaimController;
use App\Http\Controllers\Api\ServerCommandCompleteController;
use App\Http\Controllers\Api\ServerHeartbeatController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/servers/heartbeat',
    ServerHeartbeatController::class
)->name('api.servers.heartbeat');

Route::post(
    '/servers/activities',
    EchoActivityController::class
)->name('api.servers.activities');

Route::post(
    '/servers/commands/claim',
    ServerCommandClaimController::class
)->name('api.servers.commands.claim');

Route::post(
    '/servers/commands/{command}/complete',
    ServerCommandCompleteController::class
)->name('api.servers.commands.complete');
