<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\SetFilamentLocale;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AuthPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('auth')
            ->path('{locale}/auth')
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(fn () => __('التعليم والتدريس اليومي'))
                    ->icon('heroicon-o-academic-cap'),
                NavigationGroup::make()
                    ->label(fn () => __('التقويمات والامتحانات والمجالس'))
                    ->icon('heroicon-o-clipboard-document-check'),
                NavigationGroup::make()
                    ->label(fn () => __('المتابعة البيداغوجية والإرشاد'))
                    ->icon('heroicon-o-sparkles'),
                NavigationGroup::make()
                    ->label(fn () => __('الحياة المدرسية وشؤون التلاميذ'))
                    ->icon('heroicon-o-user-group'),
                NavigationGroup::make()
                    ->label(fn () => __('التخطيط والمناهج والتفتيش'))
                    ->icon('heroicon-o-document-chart-bar'),
                NavigationGroup::make()
                    ->label(fn () => __('الإدارة المدرسية والرقمنة والأرشيف'))
                    ->icon('heroicon-o-building-office-2'),
                NavigationGroup::make()
                    ->label(fn () => __('الهياكل والتنظيم التربوي'))
                    ->icon('heroicon-o-building-library')
                    ->collapsed(),
            ])
            ->brandName('سجِّل')
            ->font('Cairo', provider: GoogleFontProvider::class)
            ->login()
            ->registration()
            ->passwordReset()
            ->databaseNotifications(isLazy: false)
            ->databaseNotificationsPolling('10s')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.partials.pwa-head'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.theme'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => view('filament.partials.language-switcher'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetFilamentLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
