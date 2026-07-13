<?php

declare(strict_types=1);

namespace Platform\Ai\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AiPromptTemplate extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'ai_prompt_templates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'feature_key',
        'version',
        'name',
        'system_prompt',
        'user_prompt_template',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
