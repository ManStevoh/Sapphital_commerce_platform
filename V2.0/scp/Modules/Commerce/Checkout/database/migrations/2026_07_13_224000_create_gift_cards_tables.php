<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 32);
            $table->unsignedBigInteger('initial_balance_kobo');
            $table->unsignedBigInteger('balance_kobo');
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 32)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->string('purchaser_email')->nullable();
            $table->string('recipient_email')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('gift_card_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('gift_card_id');
            $table->uuid('order_id')->nullable();
            $table->uuid('checkout_session_id')->nullable();
            $table->bigInteger('delta_kobo');
            $table->string('type', 32);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('gift_card_id')->references('id')->on('gift_cards')->cascadeOnDelete();
            $table->index(['tenant_id', 'gift_card_id']);
        });

        Schema::table('checkout_sessions', function (Blueprint $table): void {
            $table->uuid('gift_card_id')->nullable()->after('shipping_kobo');
            $table->unsignedBigInteger('gift_card_applied_kobo')->default(0)->after('gift_card_id');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            foreach (['gift_cards', 'gift_card_transactions'] as $table) {
                $policy = $table.'_tenant_isolation';
                DB::unprepared("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                DB::unprepared("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
                DB::unprepared(<<<SQL
                    CREATE POLICY {$policy} ON {$table}
                        FOR ALL
                        USING (
                            tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid
                        )
                        WITH CHECK (
                            tenant_id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid
                        )
                    SQL);
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            foreach (['gift_card_transactions', 'gift_cards'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $policy = $table.'_tenant_isolation';
                DB::unprepared("DROP POLICY IF EXISTS {$policy} ON {$table}");
                DB::unprepared("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
                DB::unprepared("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            }
        }

        Schema::table('checkout_sessions', function (Blueprint $table): void {
            $table->dropColumn(['gift_card_id', 'gift_card_applied_kobo']);
        });

        Schema::dropIfExists('gift_card_transactions');
        Schema::dropIfExists('gift_cards');
    }
};
