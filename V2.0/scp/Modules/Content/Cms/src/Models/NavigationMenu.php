<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Content\Cms\Enums\NavigationLocation;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class NavigationMenu extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'cms_navigation_menus';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'location',
        'links',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'location' => NavigationLocation::class,
            'links' => 'array',
        ];
    }
}
