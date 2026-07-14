<?php

declare(strict_types=1);

namespace Platform\Messaging\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class WebhookDelivery extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $table = 'webhook_deliveries';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'outbox_id',
        'endpoint_id',
        'status',
        'attempt',
        'response_code',
        'last_error',
        'delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'response_code' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }
}
