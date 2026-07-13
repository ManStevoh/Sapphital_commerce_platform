<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_blog_posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->json('body_json')->nullable();
            $table->string('author_name');
            $table->json('tags')->nullable();
            $table->string('featured_image_url', 2048)->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 512)->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_blog_posts');
    }
};
