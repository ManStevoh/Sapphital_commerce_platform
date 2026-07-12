<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('checkout_session_id')->nullable();
            $table->string('order_number');
            $table->string('status');
            $table->string('currency', 3)->default('NGN');
            $table->unsignedBigInteger('subtotal_kobo');
            $table->unsignedBigInteger('total_kobo');
            $table->string('customer_email')->nullable();
            $table->string('paystack_reference')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'order_number']);
            $table->index('tenant_id');
            $table->index('checkout_session_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
