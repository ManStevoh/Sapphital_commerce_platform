<?php

declare(strict_types=1);

namespace Modules\Content\Cms\Enums;

enum NavigationLocation: string
{
    case Header = 'header';
    case Footer = 'footer';
}
