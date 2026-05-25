<?php

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use App\Encryption\Encrypter;

class EncryptServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Registra el servicio como Singleton en el contenedor de Laravel.
     */
    public function register(): void
    {
        $this->app->singleton(Encrypter::class, function($app) {
            return new Encrypter(config('app.key'));
        });

        // Enlazamos tu clase al contrato oficial de Laravel
        $this->app->alias(Encrypter::class, \Illuminate\Contracts\Encryption\Encrypter::class);
    }

    public function provides(): array
    {
        return [Encrypter::class, \Illuminate\Contracts\Encryption\Encrypter::class];
    }
}
