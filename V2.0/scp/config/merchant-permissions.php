<?php

declare(strict_types=1);

/**
 * Merchant RBAC permissions — Vol 1 Ch. 08 / Phase 1 foundation playbook.
 */
return [
    'owner' => ['*'],
    'admin' => ['*'],
    'staff' => [
        'catalog.read',
        'catalog.write',
        'cms.read',
        'orders.read',
        'shipments.read',
        'returns.manage',
    ],
    'finance' => [
        'orders.read',
        'orders.refund',
        'returns.manage',
        'disputes.manage',
        'billing.read',
        'payments.read',
        'payments.write',
    ],
];
