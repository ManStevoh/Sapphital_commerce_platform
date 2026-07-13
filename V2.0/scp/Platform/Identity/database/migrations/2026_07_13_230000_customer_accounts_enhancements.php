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
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('tenant_id');
            $table->unique(['tenant_id', 'email'], 'customers_tenant_email_unique');
        });

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('customer_id');
            $table->string('label', 64)->nullable();
            $table->string('line1');
            $table->string('city', 120);
            $table->string('state', 120);
            $table->string('lga', 120)->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->index(['tenant_id', 'customer_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('customer_id')->nullable()->after('tenant_id');
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->index(['tenant_id', 'customer_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            $policy = 'customer_addresses_tenant_isolation';
            DB::unprepared('ALTER TABLE customer_addresses ENABLE ROW LEVEL SECURITY');
            DB::unprepared('ALTER TABLE customer_addresses FORCE ROW LEVEL SECURITY');
            DB::unprepared(<<<SQL
                CREATE POLICY {$policy} ON customer_addresses
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

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql' && Schema::hasTable('customer_addresses')) {
            DB::unprepared('DROP POLICY IF EXISTS customer_addresses_tenant_isolation ON customer_addresses');
            DB::unprepared('ALTER TABLE customer_addresses NO FORCE ROW LEVEL SECURITY');
            DB::unprepared('ALTER TABLE customer_addresses DISABLE ROW LEVEL SECURITY');
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::dropIfExists('customer_addresses');

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_tenant_email_unique');
            $table->dropColumn('name');
        });
    }
};
