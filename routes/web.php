<?php

use App\Livewire\Dashboard;
use App\Livewire\Projects;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Authentication routes (login, logout, password reset) are registered by
| Laravel Fortify; see config/fortify.php and FortifyServiceProvider.
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function (): void {
    Route::livewire('/dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('/projects', Projects\Index::class)->name('projects.index');
});
