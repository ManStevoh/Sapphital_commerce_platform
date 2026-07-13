<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Content\Cms\Enums\ContentStatus;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class BlogPost extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'cms_blog_posts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'excerpt',
        'body_json',
        'author_name',
        'tags',
        'featured_image_url',
        'seo_title',
        'seo_description',
        'status',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'body_json' => 'array',
            'tags' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
