<?php

declare(strict_types=1);

namespace Platform\Messaging\Models;

use Illuminate\Database\Eloquent\Model;

final class OutboxDeadLetter extends Model
{
    protected $table = 'platform_outbox_dead';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload',
        'retry_count',
        'last_error',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'retry_count' => 'integer',
            'failed_at' => 'datetime',
        ];
    }
}
