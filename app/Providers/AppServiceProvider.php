<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook;

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
        $this->configureDefaults();
        $this->registerScanRenderHooks();

        if (class_exists(DevCommands::class)) {
            DevCommands::artisan('serve --port=8081', 'server');
        }
    }

    /**
     * Package render hooks, scoped to one field by its state path.
     */
    protected function registerScanRenderHooks(): void
    {
        FilamentView::registerRenderHook(
            QrCodeRenderHook::SEQUENCE_STEPS_AFTER,
            fn (): View => view('filament.hooks.mfg-legend'),
            scopes: 'data.station',
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
