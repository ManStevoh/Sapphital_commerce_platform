<?php

declare(strict_types=1);

namespace Platform\Messaging\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OutboxEvent extends Model
{
    use HasUuids;

    protected $table = 'platform_outbox';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload',
        'retry_count',
        'next_attempt_at',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'retry_count' => 'integer',
            'next_attempt_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
