<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(12);

            if (! $this->app->environment('testing')) {
                $rule = $rule->uncompromised();
            }

            return $rule;
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\LaunchReadinessCommand::class,
            ]);
        }
    }
}
