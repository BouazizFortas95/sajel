<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the user's chosen locale alive on every request that is not a
 * localized panel route — most importantly Livewire's `/livewire/update`
 * endpoint, which is where Filament computes the redirect URLs after a save.
 *
 * Resolution order: locale in the URL → locale persisted in the session →
 * application default. Visiting a localized URL (e.g. /ar/auth/...) is what
 * persists the choice, so switching language makes it stick for every later
 * request, form submission, and redirect until the user switches again.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        LaravelLocalization::setLocale($locale);

        // Filament generates post-save redirect URLs from named routes that
        // require the {locale} parameter. Without this default, Livewire's
        // update request generates them with config('app.locale') (English).
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = array_keys(config('laravellocalization.supportedLocales', []));

        $fromUrl = $request->route('locale');

        // The URL wins whenever it carries an explicit, supported locale.
        if (is_string($fromUrl) && in_array($fromUrl, $supported, true)) {
            if ($request->hasSession()) {
                $request->session()->put('locale', $fromUrl);
            }

            return $fromUrl;
        }

        $fromSession = $request->hasSession() ? $request->session()->get('locale') : null;

        if (is_string($fromSession) && in_array($fromSession, $supported, true)) {
            return $fromSession;
        }

        return config('app.locale');
    }
}
