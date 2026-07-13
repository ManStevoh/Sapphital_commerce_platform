<?php

declare(strict_types=1);

namespace Platform\Provisioning\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Provisioning\Enums\ProvisioningRunStatus;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class ProvisioningRun extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'provisioning_runs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'status',
        'steps',
        'started_at',
        'completed_at',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'status' => ProvisioningRunStatus::class,
            'steps' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
