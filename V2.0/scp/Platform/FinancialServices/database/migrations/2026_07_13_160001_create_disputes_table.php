<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('order_id');
            $table->string('type')->default('chargeback');
            $table->string('provider')->default('paystack');
            $table->string('status');
            $table->string('provider_case_id');
            $table->unsignedBigInteger('amount_kobo');
            $table->string('currency', 3)->default('NGN');
            $table->string('paystack_reference');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('order_id');
            $table->index(['tenant_id', 'status']);
            $table->unique(['provider', 'provider_case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
