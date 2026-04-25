<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Users\UserList;
use App\Livewire\Admin\Users\UserDetail;
use Illuminate\Support\Facades\Route;

Route::prefix(config('admin.route_prefix'))
    ->middleware(config('admin.middleware'))
    ->name(config('admin.route_name_prefix'))
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/users', UserList::class)->name('users.index');
        Route::get('/users/{user}', UserDetail::class)->name('users.show');
        /* ui-kit:admin-routes-start */
        /* ui-kit:admin-routes-end */
    });
