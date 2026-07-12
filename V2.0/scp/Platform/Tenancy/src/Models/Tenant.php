<?php

declare(strict_types=1);

namespace Platform\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Tenant extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'tenants';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'status',
        'plan_id',
        'country',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan_id' => 'string',
            'settings' => 'array',
        ];
    }
}
