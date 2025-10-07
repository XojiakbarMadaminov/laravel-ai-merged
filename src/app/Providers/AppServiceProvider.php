<?php

namespace App\Providers;

use App\Services\QdrantService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(QdrantService::class, function () {
            return new QdrantService(
                config('services.qdrant.host'),
                config('services.qdrant.collection'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
