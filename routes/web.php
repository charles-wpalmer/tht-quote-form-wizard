<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('wizards', 'pages::wizards.index')->name('wizards.index');
    Route::livewire('wizards/{wizard}', 'pages::wizards.show')->name('wizards.show');
});

require __DIR__.'/settings.php';
