<?php

namespace App\Providers;

use App\Listeners\HTTPTrackingListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\Events\RequestHandled;

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
        /* Event::listen(
            RequestHandled::class,
            HTTPTrackingListener::class
        ); */
    }
}
