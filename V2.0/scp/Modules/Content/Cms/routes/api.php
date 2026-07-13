<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Content\Cms\Http\Controllers\BlogFeedController;
use Modules\Content\Cms\Http\Controllers\BlogPostController;
use Modules\Content\Cms\Http\Controllers\ContentVersionController;
use Modules\Content\Cms\Http\Controllers\HealthController;
use Modules\Content\Cms\Http\Controllers\NavigationMenuController;
use Modules\Content\Cms\Http\Controllers\PageController;

Route::get('/v1/content/cms/health', [HealthController::class, 'show'])
    ->name('cms.health.show');

Route::middleware('tenant.context')->group(function (): void {
    Route::get('/v1/content/cms/pages', [PageController::class, 'index'])
        ->name('cms.pages.index');

    Route::get('/v1/content/cms/pages/by-slug/{slug}', [PageController::class, 'showBySlug'])
        ->name('cms.pages.show-by-slug');

    Route::get('/v1/content/cms/pages/published', [PageController::class, 'indexPublished'])
        ->name('cms.pages.published');

    Route::get('/v1/content/cms/blog-posts', [BlogPostController::class, 'index'])
        ->name('cms.blog-posts.index');

    Route::get('/v1/content/cms/blog-posts/published', [BlogPostController::class, 'indexPublished'])
        ->name('cms.blog-posts.published');

    Route::get('/v1/content/cms/blog/feed.xml', [BlogFeedController::class, 'rss'])
        ->name('cms.blog.feed');

    Route::get('/v1/content/cms/blog-posts/{id}/related', [BlogPostController::class, 'related'])
        ->name('cms.blog-posts.related');

    Route::get('/v1/content/cms/blog-posts/by-slug/{slug}', [BlogPostController::class, 'showPublished'])
        ->name('cms.blog-posts.show-by-slug');

    Route::get('/v1/content/cms/navigation/{location}', [NavigationMenuController::class, 'show'])
        ->name('cms.navigation.show');

    Route::middleware(['auth:sanctum', 'merchant.tenant', 'permission.check:cms.write'])->group(function (): void {
        Route::post('/v1/content/cms/pages', [PageController::class, 'store'])
            ->name('cms.pages.store');

        Route::put('/v1/content/cms/pages/{id}', [PageController::class, 'update'])
            ->name('cms.pages.update');

        Route::delete('/v1/content/cms/pages/{id}', [PageController::class, 'destroy'])
            ->name('cms.pages.destroy');

        Route::get('/v1/content/cms/pages/{id}/versions', [ContentVersionController::class, 'indexPages'])
            ->name('cms.pages.versions.index');

        Route::post('/v1/content/cms/pages/{id}/versions/{versionId}/restore', [ContentVersionController::class, 'restorePage'])
            ->name('cms.pages.versions.restore');

        Route::post('/v1/content/cms/blog-posts', [BlogPostController::class, 'store'])
            ->name('cms.blog-posts.store');

        Route::put('/v1/content/cms/blog-posts/{id}', [BlogPostController::class, 'update'])
            ->name('cms.blog-posts.update');

        Route::delete('/v1/content/cms/blog-posts/{id}', [BlogPostController::class, 'destroy'])
            ->name('cms.blog-posts.destroy');

        Route::get('/v1/content/cms/blog-posts/{id}/versions', [ContentVersionController::class, 'indexBlogPosts'])
            ->name('cms.blog-posts.versions.index');

        Route::post('/v1/content/cms/blog-posts/{id}/versions/{versionId}/restore', [ContentVersionController::class, 'restoreBlogPost'])
            ->name('cms.blog-posts.versions.restore');

        Route::put('/v1/content/cms/navigation/{location}', [NavigationMenuController::class, 'upsert'])
            ->name('cms.navigation.upsert');
    });
});
