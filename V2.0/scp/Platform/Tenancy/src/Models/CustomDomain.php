<?php

declare(strict_types=1);

namespace Platform\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class CustomDomain extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'custom_domains';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
        'verification_token',
        'status',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }
}
