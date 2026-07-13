<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Enums;

enum ReturnRequestStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Shipped = 'shipped';
    case Received = 'received';
    case Refunded = 'refunded';
    case Closed = 'closed';
}
