<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Illuminate\Validation\ValidationException;
use Platform\FinancialServices\Contracts\PaymentGatewayInterface;
use Platform\FinancialServices\Gateways\FlutterwavePaymentGateway;
use Platform\FinancialServices\Gateways\PaystackPaymentGateway;

final class PaymentGatewayResolver
{
    public function __construct(
        private readonly PaystackPaymentGateway $paystack,
        private readonly FlutterwavePaymentGateway $flutterwave,
        private readonly PaymentConnectorFactory $connectorFactory,
        private readonly TenantPaymentProviderService $tenantPaymentProvider,
    ) {}

    public function resolve(?string $provider = null): PaymentGatewayInterface
    {
        $provider = $provider ?? (string) config('payments.default_provider', 'paystack');

        return match ($provider) {
            'flutterwave' => $this->flutterwave,
            'paystack' => $this->paystack,
            default => throw ValidationException::withMessages([
                'provider' => ['Unsupported payment provider.'],
            ]),
        };
    }

    public function resolveForTenant(string $tenantId, ?string $provider = null): PaymentGatewayInterface
    {
        $provider = $provider ?? $this->tenantPaymentProvider->forTenant($tenantId);

        return match ($provider) {
            'paystack' => new PaystackPaymentGateway(
                $this->connectorFactory->paystackForTenant($tenantId),
            ),
            'flutterwave' => new FlutterwavePaymentGateway(
                $this->connectorFactory->flutterwaveForTenant($tenantId),
            ),
            default => throw ValidationException::withMessages([
                'provider' => ['Unsupported payment provider.'],
            ]),
        };
    }
}
