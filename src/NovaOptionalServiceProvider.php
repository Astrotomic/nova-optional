<?php

namespace Astrotromic\NovaOptional;

use Astrotromic\NovaOptional\Commands\OptionalNova;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\NovaCoreServiceProvider;
use App\Providers\NovaServiceProvider;

class NovaOptionalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (self::hasNovaInstalled()) {
            $this->app->register(NovaCoreServiceProvider::class);
            $this->app->register(NovaServiceProvider::class);
        }

        $this->commands(
            OptionalNova::class,
        );
    }

    public function boot(): void
    {
    }

    public static function hasNovaInstalled(): bool
    {
        return class_exists(NovaCoreServiceProvider::class);
    }
}
