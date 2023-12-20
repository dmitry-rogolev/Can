<?php

use Illuminate\Support\Facades\Route;

Route::get('permission/view.users', fn () => true)->middleware('permission:view.users');
Route::get('permission/create.users', fn () => true)->middleware('permission:create.users');
Route::get('permission/edit.users', fn () => true)->middleware('permission:edit.users');
Route::get('permission/delete.users', fn () => true)->middleware('permission:delete.users');
Route::get('permission/restore.users', fn () => true)->middleware('permission:restore.users');
Route::get('permission/destroy.users', fn () => true)->middleware('permission:destroy.users');

Route::post('permission/view.users', fn () => true)->middleware('permission:view.users');
Route::post('permission/create.users', fn () => true)->middleware('permission:create.users');
Route::post('permission/edit.users', fn () => true)->middleware('permission:edit.users');
Route::post('permission/delete.users', fn () => true)->middleware('permission:delete.users');
Route::post('permission/restore.users', fn () => true)->middleware('permission:restore.users');
Route::post('permission/destroy.users', fn () => true)->middleware('permission:destroy.users');

Route::post('permission/view.users/create.users/edit.users', fn () => true)->middleware('permission:view.users,create.users,edit.users');
