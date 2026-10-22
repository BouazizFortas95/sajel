<?php

namespace App\Providers;

use App\Policies\ExportPolicy;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Export::class, ExportPolicy::class);

        URL::defaults(['locale' => config('app.locale')]);
    }
}
