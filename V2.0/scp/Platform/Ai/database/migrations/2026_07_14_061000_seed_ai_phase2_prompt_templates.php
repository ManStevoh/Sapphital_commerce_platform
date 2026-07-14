<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('ai_prompt_templates')->insert([
            [
                'id' => (string) Str::uuid(),
                'feature_key' => 'collection_description',
                'version' => 1,
                'name' => 'Collection description v1',
                'system_prompt' => 'You write short storefront collection descriptions for Nigerian merchants. Describe the collection theme only from provided title, type, and rules. Never invent inventory claims or contacts.',
                'user_prompt_template' => 'Write a collection description for "{{title}}" (type: {{type}}). Rules summary: {{rules}}. Keep it under 90 words.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'feature_key' => 'support_reply',
                'version' => 1,
                'name' => 'Support reply suggest v1',
                'system_prompt' => 'You draft polite customer support reply suggestions for Nigerian ecommerce shops. Use only the provided order context. Do not invent refunds, tracking codes, or contact details. Output a draft the merchant must edit.',
                'user_prompt_template' => 'Suggest a reply for this support case. Order: {{order_number}}, status: {{status}}, total: {{total_label}}, items: {{items_summary}}. Customer question: {{question}}',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'feature_key' => 'zero_result_suggest',
                'version' => 1,
                'name' => 'Zero-result product suggest v1',
                'system_prompt' => 'You help Nigerian merchants decide which products to add when shoppers search returns zero results. Suggest product ideas only; never invent stock or pricing guarantees.',
                'user_prompt_template' => 'Shoppers searched "{{query}}" with zero results ({{search_count}} times recently). Suggest 3 product ideas the merchant could add. Keep each idea to one short line.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('ai_prompt_templates')
            ->whereIn('feature_key', [
                'collection_description',
                'support_reply',
                'zero_result_suggest',
            ])
            ->delete();
    }
};
