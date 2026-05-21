<?php

namespace App\Providers;

use App\Models\InsurerQualification;
use App\Models\InsurerSection;
use App\Models\InsurerStore;
use App\Models\InsurerSupply;
use App\Models\InsurerTitle;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
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
        Route::bind('title', fn (string $value) => InsurerTitle::query()->whereKey($value)->firstOrFail());
        Route::bind('qualification', fn (string $value) => InsurerQualification::query()->whereKey($value)->firstOrFail());
        Route::bind('section', fn (string $value) => InsurerSection::query()->whereKey($value)->firstOrFail());
        Route::bind('store', fn (string $value) => InsurerStore::query()->whereKey($value)->firstOrFail());
        Route::bind('supply', fn (string $value) => InsurerSupply::query()->whereKey($value)->firstOrFail());

        // Schedule payment status check every minute
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('payments:check-status')->everyMinute();
        });
    }
}
