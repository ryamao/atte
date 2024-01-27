<?php

namespace App\Providers;

use DateTimeZone;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app
            ->when(DateTimeZone::class)
            ->needs('$timezone')
            ->giveConfig('app.timezone');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
