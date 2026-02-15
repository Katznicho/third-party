<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

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
        // Schedule payment status check every minute
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('payments:check-status')->everyMinute();
        });
    }
}
