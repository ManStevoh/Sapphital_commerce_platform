<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Enums\PageContentType;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class Page extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'cms_pages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'content_type',
        'body_json',
        'seo_title',
        'seo_description',
        'seo_og_image_url',
        'seo_canonical_url',
        'status',
        'published_at',
        'scheduled_publish_at',
        'scheduled_unpublish_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'content_type' => PageContentType::class,
            'body_json' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'scheduled_publish_at' => 'datetime',
            'scheduled_unpublish_at' => 'datetime',
        ];
    }
}
