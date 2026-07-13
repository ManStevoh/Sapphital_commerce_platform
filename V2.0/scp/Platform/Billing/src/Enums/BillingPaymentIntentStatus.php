<?php

declare(strict_types=1);

namespace Platform\Billing\Enums;

enum BillingPaymentIntentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
