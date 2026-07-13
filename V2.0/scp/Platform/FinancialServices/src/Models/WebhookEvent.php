<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class WebhookEvent extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'webhook_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'event_type',
        'reference',
        'payload_hash',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
