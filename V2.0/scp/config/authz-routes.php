<?php

declare(strict_types=1);

/**
 * Authz matrix manifest — Vol 13 Ch. 07 / Launch Ch. 12 §2.2.
 *
 * Archetypes:
 * - public: anonymous allowed
 * - tenant: requires X-Tenant-ID
 * - sanctum: requires Bearer token
 * - merchant: sanctum + merchant.tenant
 * - platform: platform.admin
 */
return [
    'identity.health.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/identity/health', 'archetype' => 'public'],
    'identity.auth.merchant.login' => ['method' => 'POST', 'uri' => '/api/v1/auth/merchant/login', 'archetype' => 'public'],
    'identity.auth.merchant.handoff' => ['method' => 'POST', 'uri' => '/api/v1/auth/merchant/handoff', 'archetype' => 'public'],
    'identity.auth.platform.login' => ['method' => 'POST', 'uri' => '/api/v1/auth/platform/login', 'archetype' => 'public'],
    'identity.auth.platform.mfa.setup' => ['method' => 'POST', 'uri' => '/api/v1/auth/platform/mfa/setup', 'archetype' => 'sanctum'],
    'identity.auth.platform.mfa.confirm' => ['method' => 'POST', 'uri' => '/api/v1/auth/platform/mfa/confirm', 'archetype' => 'sanctum'],
    'identity.auth.platform.mfa.verify' => ['method' => 'POST', 'uri' => '/api/v1/auth/platform/mfa/verify', 'archetype' => 'sanctum'],
    'identity.auth.me' => ['method' => 'GET', 'uri' => '/api/v1/auth/me', 'archetype' => 'sanctum'],

    'tenancy.health.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/tenancy/health', 'archetype' => 'public'],
    'tenancy.tenants.show-by-slug' => ['method' => 'GET', 'uri' => '/api/v1/platform/tenancy/tenants/by-slug/demo-store', 'archetype' => 'public'],
    'tenancy.platform.tenants.index' => ['method' => 'GET', 'uri' => '/api/v1/platform/tenants', 'archetype' => 'platform'],
    'tenancy.platform.tenants.update-status' => ['method' => 'PATCH', 'uri' => '/api/v1/platform/tenants/00000000-0000-4000-8000-000000000001/status', 'archetype' => 'platform'],

    'billing.health.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/billing/health', 'archetype' => 'public'],
    'billing.plans.index' => ['method' => 'GET', 'uri' => '/api/v1/platform/billing/plans', 'archetype' => 'public'],
    'billing.subscriptions.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/billing/subscriptions/00000000-0000-4000-8000-000000000001', 'archetype' => 'public'],
    'billing.subscriptions.activate' => ['method' => 'POST', 'uri' => '/api/v1/platform/billing/subscriptions/00000000-0000-4000-8000-000000000001/activate', 'archetype' => 'merchant'],
    'billing.subscription.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/billing/subscription', 'archetype' => 'merchant'],
    'billing.invoices.index' => ['method' => 'GET', 'uri' => '/api/v1/platform/billing/invoices', 'archetype' => 'merchant'],
    'billing.invoices.pdf' => ['method' => 'GET', 'uri' => '/api/v1/platform/billing/invoices/00000000-0000-4000-8000-000000000001/pdf', 'archetype' => 'merchant'],
    'billing.settings.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/billing/settings', 'archetype' => 'merchant'],
    'billing.settings.update' => ['method' => 'PUT', 'uri' => '/api/v1/platform/billing/settings', 'archetype' => 'merchant'],
    'billing.subscriptions.initialize-payment' => ['method' => 'POST', 'uri' => '/api/v1/platform/billing/subscriptions/00000000-0000-4000-8000-000000000001/initialize-payment', 'archetype' => 'merchant'],

    'provisioning.health.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/provisioning/health', 'archetype' => 'public'],
    'provisioning.signup.store' => ['method' => 'POST', 'uri' => '/api/v1/signup', 'archetype' => 'public'],
    'provisioning.status.show' => ['method' => 'GET', 'uri' => '/api/v1/provisioning/00000000-0000-4000-8000-000000000001/status', 'archetype' => 'public'],

    'secrets.health.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/secrets/health', 'archetype' => 'public'],
    'notifications.health.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/notifications/health', 'archetype' => 'public'],

    'financial-services.health.show' => ['method' => 'GET', 'uri' => '/api/v1/platform/financial-services/health', 'archetype' => 'public'],
    'webhooks.paystack' => ['method' => 'POST', 'uri' => '/api/v1/webhooks/paystack', 'archetype' => 'public'],
    'webhooks.flutterwave' => ['method' => 'POST', 'uri' => '/api/v1/webhooks/flutterwave', 'archetype' => 'public'],
    'financial-services.payments.initialize' => ['method' => 'POST', 'uri' => '/api/v1/platform/financial-services/payments/initialize', 'archetype' => 'tenant'],
    'financial-services.payments.verify' => ['method' => 'POST', 'uri' => '/api/v1/platform/financial-services/payments/verify', 'archetype' => 'tenant'],

    'catalog.health.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/health', 'archetype' => 'public'],
    'storefront.themes.index' => ['method' => 'GET', 'uri' => '/api/v1/commerce/storefront/themes', 'archetype' => 'public'],
    'storefront.theme.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/storefront/theme', 'archetype' => 'tenant'],
    'catalog.products.index' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/products', 'archetype' => 'tenant'],
    'catalog.products.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/products/00000000-0000-4000-8000-000000000001', 'archetype' => 'tenant'],
    'catalog.products.related' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/products/00000000-0000-4000-8000-000000000001/related', 'archetype' => 'tenant'],
    'catalog.products.store' => ['method' => 'POST', 'uri' => '/api/v1/commerce/catalog/products', 'archetype' => 'merchant'],
    'catalog.products.update' => ['method' => 'PUT', 'uri' => '/api/v1/commerce/catalog/products/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'catalog.products.destroy' => ['method' => 'DELETE', 'uri' => '/api/v1/commerce/catalog/products/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'catalog.collections.index' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/collections', 'archetype' => 'tenant'],
    'catalog.collections.published' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/collections/published', 'archetype' => 'tenant'],
    'catalog.collections.by-slug' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/collections/by-slug/summer-sale', 'archetype' => 'tenant'],
    'catalog.collections.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/collections/00000000-0000-4000-8000-000000000001', 'archetype' => 'tenant'],
    'catalog.collections.products' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/collections/00000000-0000-4000-8000-000000000001/products', 'archetype' => 'tenant'],
    'catalog.collections.store' => ['method' => 'POST', 'uri' => '/api/v1/commerce/catalog/collections', 'archetype' => 'merchant'],
    'catalog.collections.update' => ['method' => 'PUT', 'uri' => '/api/v1/commerce/catalog/collections/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'catalog.collections.destroy' => ['method' => 'DELETE', 'uri' => '/api/v1/commerce/catalog/collections/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'catalog.collections.products.sync' => ['method' => 'PUT', 'uri' => '/api/v1/commerce/catalog/collections/00000000-0000-4000-8000-000000000001/products', 'archetype' => 'merchant'],
    'catalog.search' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/search', 'archetype' => 'tenant'],
    'catalog.search.analytics' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/search/analytics', 'archetype' => 'merchant'],
    'catalog.search.synonyms.index' => ['method' => 'GET', 'uri' => '/api/v1/commerce/catalog/search/synonyms', 'archetype' => 'merchant'],
    'catalog.search.synonyms.store' => ['method' => 'POST', 'uri' => '/api/v1/commerce/catalog/search/synonyms', 'archetype' => 'merchant'],
    'catalog.search.synonyms.destroy' => ['method' => 'DELETE', 'uri' => '/api/v1/commerce/catalog/search/synonyms/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'storefront.theme.settings.update' => ['method' => 'PUT', 'uri' => '/api/v1/commerce/storefront/theme/settings', 'archetype' => 'merchant'],

    'cart.health.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/cart/health', 'archetype' => 'public'],
    'cart.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/cart', 'archetype' => 'tenant'],
    'cart.items.store' => ['method' => 'POST', 'uri' => '/api/v1/commerce/cart/items', 'archetype' => 'tenant'],

    'checkout.health.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/checkout/health', 'archetype' => 'public'],
    'checkout.sessions.store' => ['method' => 'POST', 'uri' => '/api/v1/commerce/checkout/sessions', 'archetype' => 'tenant'],
    'checkout.sessions.update' => ['method' => 'PATCH', 'uri' => '/api/v1/commerce/checkout/sessions/00000000-0000-4000-8000-000000000001', 'archetype' => 'tenant'],

    'orders.health.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/orders/health', 'archetype' => 'public'],
    'orders.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/orders/00000000-0000-4000-8000-000000000001', 'archetype' => 'tenant'],
    'orders.from-checkout' => ['method' => 'POST', 'uri' => '/api/v1/commerce/orders/from-checkout', 'archetype' => 'tenant'],
    'orders.digital-downloads.store' => ['method' => 'POST', 'uri' => '/api/v1/commerce/orders/digital-downloads', 'archetype' => 'tenant'],
    'orders.index' => ['method' => 'GET', 'uri' => '/api/v1/commerce/orders', 'archetype' => 'merchant'],
    'orders.refund' => ['method' => 'POST', 'uri' => '/api/v1/commerce/orders/00000000-0000-4000-8000-000000000001/refund', 'archetype' => 'merchant'],

    'returns.index' => ['method' => 'GET', 'uri' => '/api/v1/commerce/returns', 'archetype' => 'merchant'],
    'returns.store' => ['method' => 'POST', 'uri' => '/api/v1/commerce/returns', 'archetype' => 'merchant'],
    'returns.approve' => ['method' => 'POST', 'uri' => '/api/v1/commerce/returns/00000000-0000-4000-8000-000000000001/approve', 'archetype' => 'merchant'],
    'returns.reject' => ['method' => 'POST', 'uri' => '/api/v1/commerce/returns/00000000-0000-4000-8000-000000000001/reject', 'archetype' => 'merchant'],
    'returns.guest.store' => ['method' => 'POST', 'uri' => '/api/v1/commerce/returns/guest', 'archetype' => 'tenant'],
    'returns.guest.lookup' => ['method' => 'POST', 'uri' => '/api/v1/commerce/returns/guest/lookup', 'archetype' => 'tenant'],
    'returns.ship' => ['method' => 'POST', 'uri' => '/api/v1/commerce/returns/00000000-0000-4000-8000-000000000001/ship', 'archetype' => 'merchant'],
    'returns.receive' => ['method' => 'POST', 'uri' => '/api/v1/commerce/returns/00000000-0000-4000-8000-000000000001/receive', 'archetype' => 'merchant'],
    'storefront.settings.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/storefront/settings', 'archetype' => 'merchant'],
    'storefront.settings.returns.update' => ['method' => 'PUT', 'uri' => '/api/v1/commerce/storefront/settings/returns', 'archetype' => 'merchant'],
    'storefront.settings.payments.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/storefront/settings/payments', 'archetype' => 'merchant'],
    'storefront.settings.payments.update' => ['method' => 'PUT', 'uri' => '/api/v1/commerce/storefront/settings/payments', 'archetype' => 'merchant'],
    'storefront.settings.payments.credentials.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/storefront/settings/payments/credentials', 'archetype' => 'merchant'],
    'storefront.settings.payments.credentials.update' => ['method' => 'PUT', 'uri' => '/api/v1/commerce/storefront/settings/payments/credentials', 'archetype' => 'merchant'],
    'storefront.checkout-settings.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/storefront/checkout-settings', 'archetype' => 'tenant'],

    'financial-services.disputes.index' => ['method' => 'GET', 'uri' => '/api/v1/platform/financial-services/disputes', 'archetype' => 'merchant'],
    'financial-services.disputes.resolve' => ['method' => 'POST', 'uri' => '/api/v1/platform/financial-services/disputes/00000000-0000-4000-8000-000000000001/resolve', 'archetype' => 'merchant'],
    'financial-services.reconciliation.index' => ['method' => 'GET', 'uri' => '/api/v1/platform/financial-services/reconciliation', 'archetype' => 'merchant'],
    'financial-services.reconciliation.export' => ['method' => 'GET', 'uri' => '/api/v1/platform/financial-services/reconciliation/export', 'archetype' => 'merchant'],

    'shipping.health.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/shipping/health', 'archetype' => 'public'],
    'shipping.rates.index' => ['method' => 'GET', 'uri' => '/api/v1/commerce/shipping/rates', 'archetype' => 'tenant'],
    'shipping.shipments.index' => ['method' => 'GET', 'uri' => '/api/v1/commerce/shipping/shipments', 'archetype' => 'tenant'],
    'shipping.shipments.show' => ['method' => 'GET', 'uri' => '/api/v1/commerce/shipping/shipments/00000000-0000-4000-8000-000000000001', 'archetype' => 'tenant'],
    'shipping.shipments.from-order' => ['method' => 'POST', 'uri' => '/api/v1/commerce/shipping/shipments/from-order', 'archetype' => 'merchant'],
    'shipping.shipments.ship' => ['method' => 'PATCH', 'uri' => '/api/v1/commerce/shipping/shipments/00000000-0000-4000-8000-000000000001/ship', 'archetype' => 'merchant'],
    'shipping.shipments.deliver' => ['method' => 'PATCH', 'uri' => '/api/v1/commerce/shipping/shipments/00000000-0000-4000-8000-000000000001/deliver', 'archetype' => 'merchant'],

    'cms.health.show' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/health', 'archetype' => 'public'],
    'cms.pages.index' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/pages', 'archetype' => 'tenant'],
    'cms.pages.show-by-slug' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/pages/by-slug/about', 'archetype' => 'tenant'],
    'cms.pages.published' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/pages/published', 'archetype' => 'tenant'],
    'cms.pages.store' => ['method' => 'POST', 'uri' => '/api/v1/content/cms/pages', 'archetype' => 'merchant'],
    'cms.pages.update' => ['method' => 'PUT', 'uri' => '/api/v1/content/cms/pages/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'cms.pages.destroy' => ['method' => 'DELETE', 'uri' => '/api/v1/content/cms/pages/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'cms.pages.versions.index' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/pages/00000000-0000-4000-8000-000000000001/versions', 'archetype' => 'merchant'],
    'cms.pages.versions.restore' => ['method' => 'POST', 'uri' => '/api/v1/content/cms/pages/00000000-0000-4000-8000-000000000001/versions/00000000-0000-4000-8000-000000000002/restore', 'archetype' => 'merchant'],
    'cms.blog-posts.index' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/blog-posts', 'archetype' => 'tenant'],
    'cms.blog-posts.published' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/blog-posts/published', 'archetype' => 'tenant'],
    'cms.blog.feed' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/blog/feed.xml', 'archetype' => 'tenant'],
    'cms.blog-posts.related' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/blog-posts/00000000-0000-4000-8000-000000000001/related', 'archetype' => 'tenant'],
    'cms.blog-posts.show-by-slug' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/blog-posts/by-slug/launch', 'archetype' => 'tenant'],
    'cms.blog-posts.store' => ['method' => 'POST', 'uri' => '/api/v1/content/cms/blog-posts', 'archetype' => 'merchant'],
    'cms.blog-posts.update' => ['method' => 'PUT', 'uri' => '/api/v1/content/cms/blog-posts/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'cms.blog-posts.destroy' => ['method' => 'DELETE', 'uri' => '/api/v1/content/cms/blog-posts/00000000-0000-4000-8000-000000000001', 'archetype' => 'merchant'],
    'cms.blog-posts.versions.index' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/blog-posts/00000000-0000-4000-8000-000000000001/versions', 'archetype' => 'merchant'],
    'cms.blog-posts.versions.restore' => ['method' => 'POST', 'uri' => '/api/v1/content/cms/blog-posts/00000000-0000-4000-8000-000000000001/versions/00000000-0000-4000-8000-000000000002/restore', 'archetype' => 'merchant'],
    'cms.navigation.show' => ['method' => 'GET', 'uri' => '/api/v1/content/cms/navigation/header', 'archetype' => 'tenant'],
    'cms.navigation.upsert' => ['method' => 'PUT', 'uri' => '/api/v1/content/cms/navigation/header', 'archetype' => 'merchant'],
];
