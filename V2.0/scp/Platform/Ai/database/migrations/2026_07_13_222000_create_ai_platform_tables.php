<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('feature_key', 64);
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->text('system_prompt');
            $table->text('user_prompt_template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['feature_key', 'version']);
            $table->index(['feature_key', 'is_active']);
        });

        Schema::create('ai_usage_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('feature_key', 64);
            $table->string('model', 64);
            $table->string('provider', 32);
            $table->string('prompt_hash', 64);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->boolean('was_watermarked')->default(true);
            $table->json('meta_json')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['tenant_id', 'feature_key']);
        });

        DB::table('ai_prompt_templates')->insert([
            [
                'id' => (string) Str::uuid(),
                'feature_key' => 'product_description',
                'version' => 1,
                'name' => 'Product description v1',
                'system_prompt' => 'You write concise Nigerian marketplace product descriptions. Never invent unverified claims. Do not include emails or phone numbers.',
                'user_prompt_template' => 'Write a short product description for "{{title}}" using these keywords: {{keywords}}. Keep it under 120 words.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'feature_key' => 'seo_meta',
                'version' => 1,
                'name' => 'SEO meta v1',
                'system_prompt' => 'You write SEO title and meta description pairs for Nigerian storefronts. Output plain text with two lines: Title: ... then Description: ... Never invent contacts.',
                'user_prompt_template' => 'Create an SEO title and meta description for "{{title}}" based on: {{content}}',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            $table = 'ai_usage_events';
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
        if (Schema::getConnection()->getDriverName() === 'pgsql' && Schema::hasTable('ai_usage_events')) {
            $policy = 'ai_usage_events_tenant_isolation';
            DB::unprepared("DROP POLICY IF EXISTS {$policy} ON ai_usage_events");
            DB::unprepared('ALTER TABLE ai_usage_events NO FORCE ROW LEVEL SECURITY');
            DB::unprepared('ALTER TABLE ai_usage_events DISABLE ROW LEVEL SECURITY');
        }

        Schema::dropIfExists('ai_usage_events');
        Schema::dropIfExists('ai_prompt_templates');
    }
};
