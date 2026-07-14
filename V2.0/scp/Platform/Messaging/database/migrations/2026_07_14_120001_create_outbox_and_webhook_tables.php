<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_outbox', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('aggregate_type', 64);
            $table->uuid('aggregate_id')->index();
            $table->string('event_type', 128);
            $table->json('payload');
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index(['published_at', 'next_attempt_at', 'created_at'], 'idx_outbox_unpublished');
        });

        Schema::create('platform_outbox_dead', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('aggregate_type', 64);
            $table->uuid('aggregate_id');
            $table->string('event_type', 128);
            $table->json('payload');
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('failed_at');
            $table->timestamps();
        });

        Schema::create('webhook_endpoints', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('url', 2048);
            $table->json('topics');
            $table->string('description', 255)->nullable();
            $table->string('status', 32)->default('active');
            $table->text('secret');
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('outbox_id')->index();
            $table->uuid('endpoint_id')->index();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempt')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['outbox_id', 'endpoint_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('platform_outbox_dead');
        Schema::dropIfExists('platform_outbox');
    }
};
