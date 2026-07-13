<?php

declare(strict_types=1);

namespace Modules\Commerce\Catalog\Enums;

enum CollectionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Scheduled = 'scheduled';
}
