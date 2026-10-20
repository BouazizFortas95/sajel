@php
    $languages = [
        'ar' => 'العربية',
        'en' => 'English',
        'fr' => 'Français',
    ];
    $currentLocale = app()->getLocale();
@endphp

<label class="sr-only" for="filament-language-switcher">{{ __('Language') }}</label>
<select
    id="filament-language-switcher"
    aria-label="{{ __('Language') }}"
    class="fi-input block w-auto rounded-xl border-gray-300 bg-white py-2 ps-3 pe-8 text-sm font-semibold text-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-200"
    onchange="if (this.value) window.location.assign(this.value)"
>
    @foreach ($languages as $locale => $name)
        <option
            value="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($locale, url()->current()) }}"
            lang="{{ $locale }}"
            @selected($currentLocale === $locale)
        >
            {{ $name }}
        </option>
    @endforeach
</select>
