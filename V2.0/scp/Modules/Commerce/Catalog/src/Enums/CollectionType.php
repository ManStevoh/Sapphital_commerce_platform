<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Enums;

enum CollectionType: string
{
    case Manual = 'manual';
    case Smart = 'smart';
}
