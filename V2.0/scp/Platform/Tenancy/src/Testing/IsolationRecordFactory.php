<?php

declare(strict_types=1);

namespace Platform\Tenancy\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Commerce\Cart\Models\Cart;
use Modules\Commerce\Cart\Models\CartItem;
use Modules\Commerce\Catalog\Models\Product;
use Modules\Commerce\Checkout\Models\CheckoutSession;
use Modules\Commerce\Orders\Models\Order;
use Modules\Commerce\Orders\Models\OrderItem;
use Modules\Commerce\Orders\Models\ReturnRequest;
use Modules\Commerce\Orders\Enums\ReturnRequestStatus;
use Modules\Commerce\Shipping\Models\Shipment;
use Modules\Commerce\Shipping\Models\ShippingZone;
use Platform\Billing\Enums\InvoiceStatus;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\BillingPaymentIntent;
use Platform\Billing\Enums\BillingPaymentIntentStatus;
use Platform\Billing\Models\Invoice;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\Customer;
use Platform\Identity\Models\MerchantUser;
use Platform\Provisioning\Models\ProvisioningRun;
use Platform\FinancialServices\Enums\DisputeStatus;
use Platform\FinancialServices\Enums\RefundStatus;
use Platform\FinancialServices\Models\Dispute;
use Platform\FinancialServices\Models\Refund;
use Platform\Tenancy\Models\CustomDomain;
use Modules\Content\Cms\Models\BlogPost;
use Modules\Content\Cms\Models\ContentVersion;
use Modules\Content\Cms\Models\NavigationMenu;
use Modules\Content\Cms\Models\Page;
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Enums\NavigationLocation;

final class IsolationRecordFactory
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function create(string $modelClass, string $tenantId): Model
    {
        return match ($modelClass) {
            Product::class => Product::query()->create([
                'tenant_id' => $tenantId,
                'name' => 'Isolation Product',
                'slug' => 'isolation-'.Str::random(6),
                'price_kobo' => 100_000,
                'status' => 'published',
                'inventory_qty' => 1,
            ]),
            Cart::class => Cart::query()->create([
                'tenant_id' => $tenantId,
                'session_id' => (string) Str::uuid(),
                'currency' => 'NGN',
            ]),
            CheckoutSession::class => $this->createCheckoutSession($tenantId),
            Order::class => $this->createOrder($tenantId),
            ShippingZone::class => ShippingZone::query()->create([
                'tenant_id' => $tenantId,
                'name' => 'Isolation Zone',
                'countries' => ['NG'],
                'is_default' => true,
            ]),
            Shipment::class => $this->createShipment($tenantId),
            ProvisioningRun::class => ProvisioningRun::query()->create([
                'tenant_id' => $tenantId,
                'status' => 'completed',
                'steps' => [],
                'started_at' => now(),
                'completed_at' => now(),
            ]),
            Invoice::class => Invoice::query()->create([
                'tenant_id' => $tenantId,
                'number' => 'INV-ISO-'.Str::upper(Str::random(6)),
                'status' => InvoiceStatus::Open,
                'currency' => 'NGN',
                'subtotal' => 1_500_000,
                'tax' => 0,
                'total' => 1_500_000,
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'lines' => [['description' => 'Isolation invoice', 'amount' => 1_500_000]],
            ]),
            Subscription::class => $this->createSubscription($tenantId),
            Customer::class => Customer::query()->create([
                'tenant_id' => $tenantId,
                'email' => 'customer-'.Str::random(6).'@example.com',
            ]),
            MerchantUser::class => MerchantUser::query()->create([
                'tenant_id' => $tenantId,
                'email' => 'merchant-'.Str::random(6).'@example.com',
                'password' => 'password12345',
                'role' => MerchantUserRole::Staff,
            ]),
            Refund::class => $this->createRefund($tenantId),
            Dispute::class => $this->createDispute($tenantId),
            BillingPaymentIntent::class => $this->createBillingPaymentIntent($tenantId),
            ReturnRequest::class => $this->createReturnRequest($tenantId),
            CustomDomain::class => CustomDomain::query()->create([
                'tenant_id' => $tenantId,
                'domain' => 'iso-'.Str::random(8).'.example.ng',
                'is_primary' => false,
                'verification_token' => 'verify_'.Str::random(16),
                'status' => 'pending',
            ]),
            Page::class => Page::query()->create([
                'tenant_id' => $tenantId,
                'title' => 'Isolation Page',
                'slug' => 'iso-page-'.Str::random(6),
                'status' => ContentStatus::Draft,
            ]),
            BlogPost::class => BlogPost::query()->create([
                'tenant_id' => $tenantId,
                'title' => 'Isolation Post',
                'slug' => 'iso-post-'.Str::random(6),
                'author_name' => 'Isolation Author',
                'status' => ContentStatus::Draft,
            ]),
            NavigationMenu::class => NavigationMenu::query()->create([
                'tenant_id' => $tenantId,
                'location' => NavigationLocation::Header,
                'links' => [['label' => 'Home', 'href' => '/']],
            ]),
            ContentVersion::class => ContentVersion::query()->create([
                'tenant_id' => $tenantId,
                'entity_type' => ContentVersion::ENTITY_PAGE,
                'entity_id' => (string) $this->create(Page::class, $tenantId)->getKey(),
                'version_number' => 1,
                'snapshot_json' => ['title' => 'Isolation Snapshot'],
                'label' => 'Isolation',
            ]),
            default => throw new InvalidArgumentException("No isolation seeder for {$modelClass}"),
        };
    }

    private function createCheckoutSession(string $tenantId): CheckoutSession
    {
        $cart = $this->create(Cart::class, $tenantId);

        return CheckoutSession::query()->create([
            'tenant_id' => $tenantId,
            'cart_id' => $cart->id,
            'status' => CheckoutSession::STATUS_PENDING,
            'total_kobo' => 100_000,
        ]);
    }

    private function createOrder(string $tenantId): Order
    {
        $product = $this->create(Product::class, $tenantId);

        $order = Order::query()->create([
            'tenant_id' => $tenantId,
            'checkout_session_id' => null,
            'order_number' => 'ORD-'.Str::upper(Str::random(6)),
            'status' => Order::STATUS_PAID,
            'currency' => 'NGN',
            'subtotal_kobo' => 100_000,
            'total_kobo' => 100_000,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price_kobo' => 100_000,
            'line_total_kobo' => 100_000,
        ]);

        return $order;
    }

    private function createShipment(string $tenantId): Shipment
    {
        $order = $this->create(Order::class, $tenantId);

        return Shipment::query()->create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'status' => 'pending',
            'carrier' => 'manual',
        ]);
    }

    private function createSubscription(string $tenantId): Subscription
    {
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        return Subscription::query()->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function createRefund(string $tenantId): Refund
    {
        $order = $this->create(Order::class, $tenantId);
        $order->update([
            'status' => Order::STATUS_PAID,
            'paystack_reference' => 'pay_ref_'.Str::random(8),
        ]);

        return Refund::query()->create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'amount_kobo' => 50_000,
            'currency' => 'NGN',
            'status' => RefundStatus::Completed,
            'reason' => 'Isolation refund',
            'paystack_reference' => (string) $order->paystack_reference,
            'gateway_refund_reference' => 'refund_'.Str::random(8),
            'processed_at' => now(),
        ]);
    }

    private function createReturnRequest(string $tenantId): ReturnRequest
    {
        $order = $this->create(Order::class, $tenantId);
        $order->update(['status' => Order::STATUS_PAID]);

        return ReturnRequest::query()->create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'status' => ReturnRequestStatus::Requested,
            'reason' => 'defective',
            'requested_at' => now(),
        ]);
    }

    private function createDispute(string $tenantId): Dispute
    {
        $order = $this->create(Order::class, $tenantId);
        $order->update([
            'status' => Order::STATUS_PAID,
            'paystack_reference' => 'pay_ref_'.Str::random(8),
        ]);

        return Dispute::query()->create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'type' => 'chargeback',
            'provider' => 'paystack',
            'status' => DisputeStatus::Open,
            'provider_case_id' => 'case_'.Str::random(8),
            'amount_kobo' => 100_000,
            'currency' => 'NGN',
            'paystack_reference' => (string) $order->paystack_reference,
            'due_at' => now()->addDays(2),
        ]);
    }

    private function createBillingPaymentIntent(string $tenantId): BillingPaymentIntent
    {
        $subscription = $this->createSubscription($tenantId);

        return BillingPaymentIntent::query()->create([
            'tenant_id' => $tenantId,
            'subscription_id' => $subscription->id,
            'paystack_reference' => 'saas_iso_'.Str::random(8),
            'amount_kobo' => 1_500_000,
            'status' => BillingPaymentIntentStatus::Pending,
        ]);
    }
}
