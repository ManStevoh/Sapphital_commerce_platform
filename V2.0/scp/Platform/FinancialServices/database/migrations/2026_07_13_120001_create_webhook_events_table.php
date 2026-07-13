<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider', 32);
            $table->string('event_type', 64);
            $table->string('reference', 128);
            $table->string('payload_hash', 64);
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->unique(['provider', 'event_type', 'reference']);
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
