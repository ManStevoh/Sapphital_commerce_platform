<?php



return [

    App\Providers\AppServiceProvider::class,

    Platform\Billing\BillingServiceProvider::class,

    Platform\FinancialServices\FinancialServicesServiceProvider::class,

    Platform\Identity\IdentityServiceProvider::class,

    Platform\Notifications\NotificationsServiceProvider::class,

    Platform\Provisioning\ProvisioningServiceProvider::class,

    Platform\Secrets\SecretsServiceProvider::class,

    Platform\Tenancy\TenancyServiceProvider::class,

    Platform\Ai\AiServiceProvider::class,

    Connectors\Paystack\PaystackServiceProvider::class,

    Connectors\Flutterwave\FlutterwaveServiceProvider::class,

    Modules\Commerce\Catalog\CatalogServiceProvider::class,

    Modules\Commerce\Cart\CartServiceProvider::class,

    Modules\Commerce\Checkout\CheckoutServiceProvider::class,

    Modules\Commerce\Orders\OrdersServiceProvider::class,

    Modules\Commerce\Shipping\ShippingServiceProvider::class,

    Modules\Content\Cms\CmsServiceProvider::class,

];

