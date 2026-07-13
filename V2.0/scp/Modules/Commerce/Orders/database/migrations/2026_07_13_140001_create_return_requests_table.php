<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('order_id');
            $table->string('status');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('order_id');
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('return_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('return_request_id');
            $table->uuid('order_item_id');
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->index('return_request_id');
            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_lines');
        Schema::dropIfExists('return_requests');
    }
};
