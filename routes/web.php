<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->file(public_path('app.html'));
});

Route::get('/{any}', function () {
    return response()->file(public_path('app.html'));
})->where('any', '^(?!api(?:/|$)).*');
