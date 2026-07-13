<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Enums;

enum FulfillmentType: string
{
    case Physical = 'physical';
    case Digital = 'digital';
}
