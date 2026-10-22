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

        // Persist the explicit choice: Livewire's update requests are not
        // wrapped in the localized panel URL, so this session value is the
        // only way they (and the redirects generated during them) can keep
        // rendering the user's language.
        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        LaravelLocalization::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
