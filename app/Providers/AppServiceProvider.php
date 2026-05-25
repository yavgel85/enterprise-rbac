<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\RecordFailedLogin;
use App\Listeners\RecordLogout;
use App\Listeners\RecordSuccessfulLogin;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(Login::class, RecordSuccessfulLogin::class);
        Event::listen(Logout::class, RecordLogout::class);
        Event::listen(Failed::class, RecordFailedLogin::class);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
            URL::forceRootUrl((string) config('app.url'));
        }
    }
}
