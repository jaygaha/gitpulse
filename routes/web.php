<?php

use App\Livewire\Dashboard;
use App\Livewire\RepositoryDetail;
use App\Livewire\SettingsPanel;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/repo/{slug}', RepositoryDetail::class)->name('repo.detail');
Route::get('/settings', SettingsPanel::class)->name('settings');
