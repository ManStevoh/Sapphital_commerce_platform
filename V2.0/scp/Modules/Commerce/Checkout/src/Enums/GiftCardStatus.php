<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Enums;

enum GiftCardStatus: string
{
    case Active = 'active';
    case Depleted = 'depleted';
    case Expired = 'expired';
    case Disabled = 'disabled';
}
