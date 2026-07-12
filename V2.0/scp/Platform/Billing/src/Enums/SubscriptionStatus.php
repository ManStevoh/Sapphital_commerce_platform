<?php

declare(strict_types=1);

namespace Platform\Billing\Enums;

enum SubscriptionStatus: string
{
    case Provisioning = 'provisioning';
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';
    case Churned = 'churned';
    case Deleted = 'deleted';
}
