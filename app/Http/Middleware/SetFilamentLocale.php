<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

class SetFilamentLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        abort_unless(
            is_string($locale) && array_key_exists($locale, config('laravellocalization.supportedLocales', [])),
            404,
        );

        LaravelLocalization::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
