<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Auth-event listeners (Login/Logout/Failed) are auto-discovered
        // from app/Listeners by Laravel 11+ via handle() type-hints.

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
            URL::forceRootUrl((string) config('app.url'));
        }

        Password::defaults(function (): Password {
            $rule = Password::min($this->app->isProduction() ? 12 : 8);

            return $this->app->isProduction()
                ? $rule->mixedCase()->numbers()->symbols()->uncompromised()
                : $rule;
        });
    }
}
