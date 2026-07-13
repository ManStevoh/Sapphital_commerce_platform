<?php

declare(strict_types=1);

/**
 * Tenant isolation manifest — Vol 13 Ch. 04.
 *
 * Every model with tenant_id must appear here before merge.
 */
return [
    'session_variable' => 'app.current_tenant_id',

    'models' => [
        Modules\Commerce\Catalog\Models\Product::class,
        Modules\Commerce\Catalog\Models\Collection::class,
        Modules\Commerce\Catalog\Models\CollectionProduct::class,
        Modules\Commerce\Cart\Models\Cart::class,
        Modules\Commerce\Checkout\Models\CheckoutSession::class,
        Modules\Commerce\Orders\Models\Order::class,
        Modules\Commerce\Orders\Models\ReturnRequest::class,
        Modules\Commerce\Shipping\Models\ShippingZone::class,
        Modules\Commerce\Shipping\Models\Shipment::class,
        Platform\Provisioning\Models\ProvisioningRun::class,
        Platform\Billing\Models\Invoice::class,
        Platform\Billing\Models\Subscription::class,
        Platform\Identity\Models\Customer::class,
        Platform\Identity\Models\MerchantUser::class,
        Platform\FinancialServices\Models\Refund::class,
        Platform\FinancialServices\Models\Dispute::class,
        Platform\Billing\Models\BillingPaymentIntent::class,
        Platform\Tenancy\Models\CustomDomain::class,
        Modules\Content\Cms\Models\Page::class,
        Modules\Content\Cms\Models\BlogPost::class,
        Modules\Content\Cms\Models\NavigationMenu::class,
        Modules\Content\Cms\Models\ContentVersion::class,
    ],

    'api_resources' => [
        'catalog.products' => '/api/v1/commerce/catalog/products',
        'catalog.collections' => '/api/v1/commerce/catalog/collections',
        'orders' => '/api/v1/commerce/orders',
        'carts' => '/api/v1/commerce/cart',
        'shipments' => '/api/v1/commerce/shipping/shipments',
    ],
];
