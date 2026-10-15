<?php

namespace ManojKiran\FilamentQuickView;

use Illuminate\Support\ServiceProvider;

class FilamentQuickViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-quick-view');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/filament-quick-view'),
        ], 'filament-quick-view-translations');
    }
}
