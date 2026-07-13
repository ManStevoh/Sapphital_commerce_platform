<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class ContentVersion extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public const ENTITY_PAGE = 'page';

    public const ENTITY_BLOG_POST = 'blog_post';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'cms_content_versions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'version_number',
        'snapshot_json',
        'label',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'entity_id' => 'string',
            'version_number' => 'integer',
            'snapshot_json' => 'array',
        ];
    }
}
