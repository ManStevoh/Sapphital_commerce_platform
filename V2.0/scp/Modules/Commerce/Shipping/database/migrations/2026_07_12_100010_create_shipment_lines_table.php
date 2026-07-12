<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('shipment_id');
            $table->uuid('order_item_id');
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_lines');
    }
};
