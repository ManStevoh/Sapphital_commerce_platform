<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tenantScopedTables = [
        'products',
        'carts',
        'checkout_sessions',
        'orders',
        'shipping_zones',
        'shipments',
        'provisioning_runs',
        'invoices',
        'subscriptions',
        'customers',
        'merchant_users',
        'custom_domains',
    ];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tenantScopedTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

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

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tenantScopedTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $policy = $table.'_tenant_isolation';

            DB::unprepared("DROP POLICY IF EXISTS {$policy} ON {$table}");
            DB::unprepared("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
            DB::unprepared("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
