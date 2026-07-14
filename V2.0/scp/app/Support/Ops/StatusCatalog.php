<?php

declare(strict_types=1);

namespace App\Support\Ops;

final class StatusCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function components(): array
    {
        return [
            ['id' => 'storefront', 'name' => 'Storefront', 'status' => 'operational'],
            ['id' => 'admin', 'name' => 'Admin Dashboard', 'status' => 'operational'],
            ['id' => 'checkout', 'name' => 'Checkout', 'status' => 'operational'],
            ['id' => 'api', 'name' => 'API', 'status' => 'operational'],
            ['id' => 'webhooks', 'name' => 'Webhooks', 'status' => 'operational'],
            ['id' => 'paystack', 'name' => 'Paystack Integration', 'status' => 'operational'],
            ['id' => 'flutterwave', 'name' => 'Flutterwave Integration', 'status' => 'operational'],
            ['id' => 'search', 'name' => 'Search', 'status' => 'operational'],
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    public function runbooks(): array
    {
        return [
            ['id' => 'RB-001', 'title' => 'Deploy rollback', 'owner' => 'Platform on-call'],
            ['id' => 'RB-002', 'title' => 'Database restore', 'owner' => 'Platform on-call'],
            ['id' => 'RB-003', 'title' => 'Webhook backlog', 'owner' => 'Commerce on-call'],
            ['id' => 'RB-004', 'title' => 'Checkout PSP degradation', 'owner' => 'Commerce on-call'],
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    public function supportMacros(): array
    {
        return [
            [
                'id' => 'paystack-test-keys',
                'title' => 'Paystack test keys in production',
                'category' => 'payments',
            ],
            [
                'id' => 'flutterwave-webhook-pending',
                'title' => 'Flutterwave webhook orders stuck pending',
                'category' => 'payments',
            ],
            [
                'id' => 'custom-domain-dns',
                'title' => '.ng custom domain DNS propagation',
                'category' => 'domains',
            ],
            [
                'id' => 'ndpa-deletion-request',
                'title' => 'NDPA shopper deletion request',
                'category' => 'privacy',
            ],
            [
                'id' => 'shipping-zone-setup',
                'title' => 'Shipping zones and local rider setup',
                'category' => 'shipping',
            ],
            [
                'id' => 'gift-card-balance',
                'title' => 'Gift card balance not applying at checkout',
                'category' => 'checkout',
            ],
            [
                'id' => 'checkout-turnstile',
                'title' => 'Customer cannot complete Turnstile challenge',
                'category' => 'checkout',
            ],
            [
                'id' => 'custom-domain-ssl',
                'title' => 'Custom domain verified but SSL pending',
                'category' => 'domains',
            ],
            [
                'id' => 'product-import-inventory',
                'title' => 'Inventory quantity mismatch after product import',
                'category' => 'catalog',
            ],
            [
                'id' => 'digital-download-link',
                'title' => 'Digital download link expired',
                'category' => 'orders',
            ],
            [
                'id' => 'order-paid-pending',
                'title' => 'Order paid but still pending',
                'category' => 'payments',
            ],
            [
                'id' => 'refund-status',
                'title' => 'Refund created but customer asks for status',
                'category' => 'payments',
            ],
            [
                'id' => 'dispute-deadline',
                'title' => 'Payment dispute deadline approaching',
                'category' => 'payments',
            ],
            [
                'id' => 'theme-switch-content',
                'title' => 'Theme switch and merchant-owned content retention',
                'category' => 'themes',
            ],
            [
                'id' => 'ai-generation-empty',
                'title' => 'AI generation returned no useful copy',
                'category' => 'ai',
            ],
            [
                'id' => 'search-no-results',
                'title' => 'Storefront search returns no results',
                'category' => 'search',
            ],
            [
                'id' => 'webhook-delivery-failed',
                'title' => 'Merchant webhook delivery failed',
                'category' => 'integrations',
            ],
            [
                'id' => 'mfa-lost-device',
                'title' => 'Merchant lost authenticator device',
                'category' => 'security',
            ],
            [
                'id' => 'customer-account-login',
                'title' => 'Customer cannot log in to account',
                'category' => 'customers',
            ],
            [
                'id' => 'billing-invoice-vat',
                'title' => 'Merchant invoice VAT clarification',
                'category' => 'billing',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicStatus(): array
    {
        return [
            'page' => [
                'url' => 'https://status.sapphital.com',
                'timezone' => 'Africa/Lagos',
                'overall_status' => 'operational',
            ],
            'components' => $this->components(),
            'incident_template' => [
                'states' => ['investigating', 'identified', 'monitoring', 'resolved'],
                'sev1_first_update_minutes' => 15,
                'sev1_update_interval_minutes' => 30,
            ],
        ];
    }
}
