<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('zone_id');
            $table->string('name');
            $table->string('type', 16);
            $table->unsignedBigInteger('min_order_kobo')->nullable();
            $table->unsignedBigInteger('max_order_kobo')->nullable();
            $table->unsignedBigInteger('price_kobo');
            $table->unsignedSmallInteger('estimated_days_min');
            $table->unsignedSmallInteger('estimated_days_max');
            $table->timestamps();

            $table->index('zone_id');
            $table->foreign('zone_id')
                ->references('id')
                ->on('shipping_zones')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
