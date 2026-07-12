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

        DB::unprepared('ALTER TABLE tenants ENABLE ROW LEVEL SECURITY');
        DB::unprepared('ALTER TABLE tenants FORCE ROW LEVEL SECURITY');

        DB::unprepared(<<<'SQL'
            CREATE POLICY tenant_self_isolation ON tenants
                FOR ALL
                USING (
                    id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid
                )
                WITH CHECK (
                    id = NULLIF(current_setting('app.current_tenant_id', true), '')::uuid
                )
            SQL);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('DROP POLICY IF EXISTS tenant_self_isolation ON tenants');
        DB::unprepared('ALTER TABLE tenants NO FORCE ROW LEVEL SECURITY');
        DB::unprepared('ALTER TABLE tenants DISABLE ROW LEVEL SECURITY');
    }
};
