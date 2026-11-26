<?php

namespace App\Providers;

use App\Gear\ForeverCatalog;
use App\Gear\GearCatalog;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GearCatalog::class, fn (): GearCatalog => new GearCatalog((string) config('gear.data_path')));
        $this->app->singleton(ForeverCatalog::class, fn (): ForeverCatalog => new ForeverCatalog((string) config('forever.data_path')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
