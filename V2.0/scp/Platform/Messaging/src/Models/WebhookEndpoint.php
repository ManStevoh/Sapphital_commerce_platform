<?php

declare(strict_types=1);

namespace Platform\Messaging\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Tenancy\Models\Concerns\BelongsToTenant;

final class WebhookEndpoint extends Model
{
    use BelongsToTenant;
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    protected $table = 'webhook_endpoints';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'url',
        'topics',
        'description',
        'status',
        'secret',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'topics' => 'array',
            'secret' => 'encrypted',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @param  list<string>  $topics
     */
    public function listensTo(string $eventType): bool
    {
        $topics = is_array($this->topics) ? $this->topics : [];

        return in_array($eventType, $topics, true) || in_array('*', $topics, true);
    }
}
