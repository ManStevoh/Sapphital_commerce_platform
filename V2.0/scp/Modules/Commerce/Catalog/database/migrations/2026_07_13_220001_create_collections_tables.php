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
        Schema::create('collections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('type', 32)->default('manual');
            $table->json('rules_json')->nullable();
            $table->string('sort_order', 32)->default('manual');
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'starts_at', 'ends_at']);
        });

        Schema::create('collection_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('collection_id');
            $table->uuid('product_id');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['collection_id', 'product_id']);
            $table->index(['tenant_id', 'collection_id', 'position']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            foreach (['collections', 'collection_products'] as $table) {
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
            foreach (['collection_products', 'collections'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $policy = $table.'_tenant_isolation';
                DB::unprepared("DROP POLICY IF EXISTS {$policy} ON {$table}");
                DB::unprepared("ALTER TABLE {$table} NO FORCE ROW LEVEL SECURITY");
                DB::unprepared("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
            }
        }

        Schema::dropIfExists('collection_products');
        Schema::dropIfExists('collections');
    }
};
