<?php

declare(strict_types=1);

namespace Modules\Commerce\Orders\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Commerce\Orders\Services\StoreSettingsService;
use Platform\FinancialServices\Services\TenantPaymentCredentialsService;
use Symfony\Component\HttpFoundation\Response;

final class StoreSettingsController
{
    public function __construct(
        private readonly StoreSettingsService $settings,
        private readonly TenantPaymentCredentialsService $paymentCredentials,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        return response()->json([
            'data' => $this->settings->getForTenant($tenantId),
        ]);
    }

    public function checkoutSettings(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        return response()->json([
            'data' => $this->settings->getCheckoutSettingsForTenant($tenantId),
        ]);
    }

    public function showPayments(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $settings = $this->settings->getForTenant($tenantId);

        return response()->json([
            'data' => [
                'payment_provider' => $settings['payment_provider'],
                'currency' => $settings['currency'],
            ],
        ]);
    }

    public function updatePayments(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'payment_provider' => ['required', 'string', Rule::in(['paystack', 'flutterwave'])],
        ]);

        $provider = $this->settings->updatePaymentProvider(
            $tenantId,
            $validated['payment_provider'],
        );

        return response()->json([
            'data' => array_merge(
                $this->settings->getForTenant($tenantId),
                ['payment_provider' => $provider],
            ),
        ]);
    }

    public function showPaymentCredentials(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        return response()->json([
            'data' => $this->paymentCredentials->statusForTenant($tenantId),
        ]);
    }

    public function updatePaymentCredentials(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', Rule::in(['paystack', 'flutterwave'])],
            'secret_key' => ['nullable', 'string', 'max:512'],
            'secret_hash' => ['nullable', 'string', 'max:512'],
        ]);

        $provider = $validated['provider'];
        $secretKey = $validated['secret_key'] ?? null;
        $secretHash = $validated['secret_hash'] ?? null;

        if (is_string($secretKey) && $secretKey !== '') {
            $this->paymentCredentials->storeSecretKey($tenantId, $provider, $secretKey);
        } elseif ($secretKey === '' || $secretKey === null) {
            if ($request->has('secret_key')) {
                $this->paymentCredentials->clearSecretKey($tenantId, $provider);
            }
        }

        if ($provider === 'flutterwave') {
            if (is_string($secretHash) && $secretHash !== '') {
                $this->paymentCredentials->storeWebhookHash($tenantId, $provider, $secretHash);
            } elseif ($secretHash === '' || ($secretHash === null && $request->has('secret_hash'))) {
                $this->paymentCredentials->clearWebhookHash($tenantId, $provider);
            }
        }

        return response()->json([
            'data' => $this->paymentCredentials->statusForTenant($tenantId),
        ]);
    }

    public function updateReturns(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'return_window_days' => ['required', 'integer', 'min:7', 'max:30'],
        ]);

        $days = $this->settings->updateReturnWindow(
            $tenantId,
            (int) $validated['return_window_days'],
        );

        return response()->json([
            'data' => array_merge(
                $this->settings->getForTenant($tenantId),
                ['return_window_days' => $days],
            ),
        ]);
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    private function missingTenantResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant context required.',
        ], Response::HTTP_FORBIDDEN);
    }
}
