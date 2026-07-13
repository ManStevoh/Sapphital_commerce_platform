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
        Schema::create('product_digital_assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->string('storage_key');
            $table->string('original_filename');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('byte_size')->default(0);
            $table->unsignedInteger('download_limit')->default(5);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['product_id']);
            $table->index(['tenant_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedInteger('download_count')->default(0)->after('downloaded_at');
            $table->unsignedInteger('download_limit')->nullable()->after('download_count');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            $table = 'product_digital_assets';
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
        if (Schema::getConnection()->getDriverName() === 'pgsql' && Schema::hasTable('product_digital_assets')) {
            $policy = 'product_digital_assets_tenant_isolation';
            DB::unprepared("DROP POLICY IF EXISTS {$policy} ON product_digital_assets");
            DB::unprepared('ALTER TABLE product_digital_assets NO FORCE ROW LEVEL SECURITY');
            DB::unprepared('ALTER TABLE product_digital_assets DISABLE ROW LEVEL SECURITY');
        }

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['download_count', 'download_limit']);
        });

        Schema::dropIfExists('product_digital_assets');
    }
};
