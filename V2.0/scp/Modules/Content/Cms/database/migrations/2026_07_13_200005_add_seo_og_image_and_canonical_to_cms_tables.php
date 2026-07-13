<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->string('seo_og_image_url', 2048)->nullable()->after('seo_description');
            $table->string('seo_canonical_url', 2048)->nullable()->after('seo_og_image_url');
        });

        Schema::table('cms_blog_posts', function (Blueprint $table): void {
            $table->string('seo_og_image_url', 2048)->nullable()->after('seo_description');
            $table->string('seo_canonical_url', 2048)->nullable()->after('seo_og_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->dropColumn(['seo_og_image_url', 'seo_canonical_url']);
        });

        Schema::table('cms_blog_posts', function (Blueprint $table): void {
            $table->dropColumn(['seo_og_image_url', 'seo_canonical_url']);
        });
    }
};
