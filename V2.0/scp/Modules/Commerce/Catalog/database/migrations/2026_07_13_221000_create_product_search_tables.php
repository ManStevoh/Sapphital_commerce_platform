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
        Schema::create('product_search_synonyms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('term', 64);
            $table->string('synonym', 64);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'term', 'synonym']);
            $table->index(['tenant_id', 'term']);
        });

        Schema::create('product_search_queries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('query', 255);
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamp('searched_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'searched_at']);
            $table->index(['tenant_id', 'query']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            foreach (['product_search_synonyms', 'product_search_queries'] as $table) {
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
            foreach (['product_search_queries', 'product_search_synonyms'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $policy = $table.'_tenant_isolation';
                DB::unprepared("DROP POLICY IF EXISTS {$policy} ON {$table}");
                DB::unprepared("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
                DB::unprepared("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            }
        }

        Schema::dropIfExists('product_search_queries');
        Schema::dropIfExists('product_search_synonyms');
    }
};
