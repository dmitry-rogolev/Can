<?php

use Illuminate\Support\Facades\Route;

Route::get('welcome', function () {

});

Route::middleware('auth')->get('profile', function () {

});

Route::middleware(['auth', 'permission:create.users'])->post('users/create', function () {

});

Route::middleware(['auth', 'can:view.users'])->post('users/view', function () {

});

Route::middleware(['auth', 'can:update.users'])->post('users/update', function () {

});

Route::middleware(['auth', 'permission:delete.users'])->post('users/delete', function () {

});

Route::middleware(['auth', 'permission:create.permissions'])->post('permissions/create', function () {

});

Route::middleware(['auth', 'can:view.permissions'])->post('permissions/view', function () {

});

Route::middleware(['auth', 'can:update.permissions'])->post('permissions/update', function () {

});

Route::middleware(['auth', 'permission:delete.permissions'])->post('permissions/delete', function () {

});

Route::middleware(['auth', 'permission:create.permissions,delete.permissions'])->post('permissions/create/delete', function () {

});

Route::middleware(['auth', 'permission:view.permissions|update.permissions'])->post('permissions/view/update', function () {

});
