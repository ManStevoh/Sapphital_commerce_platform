<?php

declare(strict_types=1);

namespace Platform\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class MerchantUser extends Authenticatable
{
    use BelongsToTenant;
    use HasApiTokens;
    use HasUuids;
    use Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'merchant_users';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'email',
        'password',
        'role',
        'mfa_secret',
        'mfa_confirmed_at',
        'mfa_backup_codes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'mfa_secret',
        'mfa_backup_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'role' => MerchantUserRole::class,
            'password' => 'hashed',
            'mfa_secret' => 'encrypted',
            'mfa_confirmed_at' => 'datetime',
            'mfa_backup_codes' => 'encrypted:array',
        ];
    }
}
