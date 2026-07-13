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
        Schema::create('cms_content_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('entity_type', 32);
            $table->uuid('entity_id');
            $table->unsignedInteger('version_number');
            $table->json('snapshot_json');
            $table->string('label', 255)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'entity_type', 'entity_id', 'version_number'], 'cms_content_versions_unique_version');
            $table->index(['tenant_id', 'entity_type', 'entity_id', 'created_at'], 'cms_content_versions_entity_created_idx');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::unprepared('ALTER TABLE cms_content_versions ENABLE ROW LEVEL SECURITY');
            DB::unprepared('ALTER TABLE cms_content_versions FORCE ROW LEVEL SECURITY');
            DB::unprepared(<<<'SQL'
                CREATE POLICY cms_content_versions_tenant_isolation ON cms_content_versions
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
        if (Schema::getConnection()->getDriverName() === 'pgsql' && Schema::hasTable('cms_content_versions')) {
            DB::unprepared('DROP POLICY IF EXISTS cms_content_versions_tenant_isolation ON cms_content_versions');
            DB::unprepared('ALTER TABLE cms_content_versions NO FORCE ROW LEVEL SECURITY');
            DB::unprepared('ALTER TABLE cms_content_versions DISABLE ROW LEVEL SECURITY');
        }

        Schema::dropIfExists('cms_content_versions');
    }
};
