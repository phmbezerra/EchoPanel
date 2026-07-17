<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')
    ->name('dashboard');
});

require __DIR__.'/settings.php';

Route::livewire('/servers', 'pages::servers.index')
    ->middleware(['auth'])
    ->name('servers.index');

Route::livewire('/servers/{server}', 'pages::servers.show')
    ->middleware(['auth'])
    ->name('servers.show');