<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/kyc', function () {
    return view('kyc');
})->name('kyc');

Route::get('/admin', function () {
    return view('admin');
})->name('admin');
