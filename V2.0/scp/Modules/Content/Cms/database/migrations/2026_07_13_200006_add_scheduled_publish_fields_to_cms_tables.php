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
            $table->timestamp('scheduled_publish_at')->nullable()->after('published_at');
            $table->timestamp('scheduled_unpublish_at')->nullable()->after('scheduled_publish_at');
            $table->index(['tenant_id', 'status', 'scheduled_publish_at'], 'cms_pages_tenant_status_scheduled_publish_idx');
        });

        Schema::table('cms_blog_posts', function (Blueprint $table): void {
            $table->timestamp('scheduled_publish_at')->nullable()->after('published_at');
            $table->timestamp('scheduled_unpublish_at')->nullable()->after('scheduled_publish_at');
            $table->index(['tenant_id', 'status', 'scheduled_publish_at'], 'cms_blog_posts_tenant_status_scheduled_publish_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->dropIndex('cms_pages_tenant_status_scheduled_publish_idx');
            $table->dropColumn(['scheduled_publish_at', 'scheduled_unpublish_at']);
        });

        Schema::table('cms_blog_posts', function (Blueprint $table): void {
            $table->dropIndex('cms_blog_posts_tenant_status_scheduled_publish_idx');
            $table->dropColumn(['scheduled_publish_at', 'scheduled_unpublish_at']);
        });
    }
};
