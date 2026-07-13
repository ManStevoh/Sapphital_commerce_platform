<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('disputes') || ! Schema::hasColumn('disputes', 'tenant_id')) {
            return;
        }

        $table = 'disputes';
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

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('disputes')) {
            return;
        }

        $table = 'disputes';
        $policy = $table.'_tenant_isolation';

        DB::unprepared("DROP POLICY IF EXISTS {$policy} ON {$table}");
        DB::unprepared("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
        DB::unprepared("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
    }
};
