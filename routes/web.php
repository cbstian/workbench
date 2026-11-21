<?php

use App\Http\Controllers\PageFileController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Middleware\EnsureCurrentWorkspace;
use App\Http\Middleware\EnsureUserIsActive;
use App\Livewire\Clients;
use App\Livewire\Dashboard;
use App\Livewire\Projects;
use App\Livewire\Roles;
use App\Livewire\Settings;
use App\Livewire\Tasks;
use App\Livewire\Trash;
use App\Livewire\Users;
use App\Livewire\Wiki;
use App\Livewire\Workspaces;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Authentication routes (login, logout, password reset) are registered by
| Laravel Fortify; see config/fortify.php and FortifyServiceProvider.
|
| Pages inside a workspace use the current workspace (App\Support\CurrentWorkspace)
| and the EnsureCurrentWorkspace middleware.
|
*/

Route::view('/', 'landing')->name('home');

Route::middleware(['auth', EnsureUserIsActive::class])->group(function (): void {
    Route::livewire('/dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('/settings', Settings\Profile::class)->name('settings.profile');

    Route::livewire('/workspaces', Workspaces\Index::class)->name('workspaces.index');
    Route::livewire('/roles', Roles\Index::class)->name('roles.index');

    Route::middleware(EnsureCurrentWorkspace::class)->group(function (): void {
        Route::livewire('/projects', Projects\Index::class)->name('projects.index');
        Route::livewire('/projects/{project}', Projects\Show::class)->name('projects.show');
        Route::livewire('/projects/{project}/settings', Projects\Settings::class)->name('projects.settings');
        Route::livewire('/tasks/{task}', Tasks\Show::class)->name('tasks.show');
        Route::get('/attachments/{attachment}', [TaskAttachmentController::class, 'show'])->name('attachments.show');
        Route::livewire('/wiki', Wiki\Index::class)->name('wiki.index');
        Route::livewire('/projects/{project}/wiki', Wiki\Index::class)->name('projects.wiki');
        Route::livewire('/pages/{page}', Wiki\PageShow::class)->name('pages.show');
        Route::get('/page-files/{file}', [PageFileController::class, 'show'])->name('page-files.show');
        Route::livewire('/clients', Clients\Index::class)->name('clients.index');
        Route::livewire('/users', Users\Index::class)->name('users.index');
        Route::livewire('/trash', Trash\Index::class)->name('trash.index');
    });
});
