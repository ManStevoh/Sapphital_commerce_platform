<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('fulfillment_type', 20)->default('physical')->after('product_name');
            $table->timestamp('downloaded_at')->nullable()->after('line_total_kobo');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['fulfillment_type', 'downloaded_at']);
        });
    }
};
