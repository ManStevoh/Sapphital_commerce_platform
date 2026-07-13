<?php

declare(strict_types=1);

namespace Platform\Ai\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class AiUsageEvent extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'ai_usage_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'feature_key',
        'model',
        'provider',
        'prompt_hash',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'was_watermarked',
        'meta_json',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'was_watermarked' => 'boolean',
            'meta_json' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
