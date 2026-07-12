<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table): void {
            $table->string('customer_email')->nullable()->after('total_kobo');
            $table->string('customer_phone', 32)->nullable()->after('customer_email');
            $table->json('shipping_address')->nullable()->after('customer_phone');
            $table->uuid('shipping_rate_id')->nullable()->after('shipping_address');
            $table->unsignedBigInteger('shipping_kobo')->default(0)->after('shipping_rate_id');
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_email',
                'customer_phone',
                'shipping_address',
                'shipping_rate_id',
                'shipping_kobo',
            ]);
        });
    }
};
