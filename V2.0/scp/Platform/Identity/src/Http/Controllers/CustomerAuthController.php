<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Platform\Identity\Models\Customer;
use Symfony\Component\HttpFoundation\Response;

final class CustomerAuthController
{
    public function register(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenantId,
            'email' => strtolower(trim($validated['email'])),
            'password' => $validated['password'],
            'name' => $validated['name'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        $token = $customer->createToken('customer-api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'data' => $this->payload($customer),
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::query()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
            ->first();

        if ($customer === null
            || $customer->password === null
            || ! Hash::check($validated['password'], (string) $customer->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $customer->createToken('customer-api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'data' => $this->payload($customer),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Customer) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'email' => $customer->email,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'tenant_id' => $customer->tenant_id,
        ];
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
