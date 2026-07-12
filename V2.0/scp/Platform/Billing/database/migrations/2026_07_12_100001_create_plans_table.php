<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedBigInteger('price_ngn');
            $table->unsignedInteger('product_limit');
            $table->unsignedInteger('staff_limit');
            $table->boolean('custom_domain')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('plans')->insert([
            [
                'id' => (string) Str::uuid(),
                'slug' => 'starter',
                'name' => 'Starter',
                'price_ngn' => 1_500_000,
                'product_limit' => 100,
                'staff_limit' => 2,
                'custom_domain' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'slug' => 'growth',
                'name' => 'Growth',
                'price_ngn' => 4_500_000,
                'product_limit' => 1_000,
                'staff_limit' => 10,
                'custom_domain' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'slug' => 'pro',
                'name' => 'Pro',
                'price_ngn' => 12_000_000,
                'product_limit' => 10_000,
                'staff_limit' => 50,
                'custom_domain' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
