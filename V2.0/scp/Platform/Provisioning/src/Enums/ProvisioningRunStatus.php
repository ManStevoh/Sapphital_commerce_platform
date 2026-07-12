<?php

declare(strict_types=1);

namespace Platform\Provisioning\Enums;

enum ProvisioningRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
