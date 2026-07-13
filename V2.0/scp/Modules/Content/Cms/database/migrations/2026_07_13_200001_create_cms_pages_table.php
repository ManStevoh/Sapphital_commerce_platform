<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('title');
            $table->string('slug');
            $table->string('content_type', 32)->default('page');
            $table->json('body_json')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 512)->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
