<?php

declare(strict_types=1);

namespace Platform\Identity\Enums;

enum MerchantUserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Staff = 'staff';
    case Finance = 'finance';
}
