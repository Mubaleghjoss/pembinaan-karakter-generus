<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-error-popup', function () {
    return view('auth.login')->withErrors(['login' => 'Ini adalah pesan error testing popup!']);
});

Route::get('/test-session-error', function () {
    return redirect()->route('login')->with('error', 'Ini adalah pesan error session testing!');
});
