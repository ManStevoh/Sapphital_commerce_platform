<?php

declare(strict_types=1);

namespace Platform\Billing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Platform\Billing\Enums\InvoiceStatus;

final class Invoice extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'invoices';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'number',
        'status',
        'currency',
        'subtotal',
        'tax',
        'total',
        'period_start',
        'period_end',
        'lines',
        'paystack_reference',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'status' => InvoiceStatus::class,
            'subtotal' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'lines' => 'array',
        ];
    }
}
