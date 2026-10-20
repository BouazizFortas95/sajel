<?php

use App\Http\Middleware\SetFilamentLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/{locale}', function () {
    return view('welcome');
})
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales', [])))
    ->middleware(SetFilamentLocale::class);
