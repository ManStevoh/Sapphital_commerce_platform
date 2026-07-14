<?php

declare(strict_types=1);

namespace Platform\Ai;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\Ai\Services\AiAccessPolicy;
use Platform\Ai\Services\CollectionDescriptionGenerator;
use Platform\Ai\Services\ModelGateway;
use Platform\Ai\Services\PiiScrubber;
use Platform\Ai\Services\ProductDescriptionGenerator;
use Platform\Ai\Services\SeoMetaGenerator;
use Platform\Ai\Services\SupportReplyGenerator;
use Platform\Ai\Services\ZeroResultSuggestGenerator;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai.php', 'ai');

        $this->app->singleton(PiiScrubber::class);
        $this->app->singleton(ModelGateway::class);
        $this->app->singleton(AiAccessPolicy::class);
        $this->app->singleton(ProductDescriptionGenerator::class);
        $this->app->singleton(SeoMetaGenerator::class);
        $this->app->singleton(CollectionDescriptionGenerator::class);
        $this->app->singleton(SupportReplyGenerator::class);
        $this->app->singleton(ZeroResultSuggestGenerator::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
