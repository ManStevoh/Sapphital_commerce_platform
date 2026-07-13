<?php

declare(strict_types=1);

namespace Platform\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class PlatformAdmin extends Authenticatable
{
    use HasApiTokens;
    use HasUuids;
    use Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'platform_admins';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
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
            'password' => 'hashed',
            'mfa_secret' => 'encrypted',
            'mfa_confirmed_at' => 'datetime',
            'mfa_backup_codes' => 'encrypted:array',
        ];
    }
}
