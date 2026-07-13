<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Scheduled = 'scheduled';
}
