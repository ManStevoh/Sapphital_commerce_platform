<?php

declare(strict_types=1);

namespace Platform\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commerce\Orders\Models\Order;
use Platform\Identity\Models\Customer;
use Platform\Identity\Models\CustomerAddress;
use Symfony\Component\HttpFoundation\Response;

final class CustomerAccountController
{
    public function orders(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        if ($customer === null) {
            return $this->unauthorized();
        }

        $orders = Order::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where(function ($query) use ($customer): void {
                $query->where('customer_id', $customer->id);

                if (is_string($customer->email) && $customer->email !== '') {
                    $query->orWhere(function ($inner) use ($customer): void {
                        $inner->whereNull('customer_id')
                            ->whereRaw('LOWER(customer_email) = ?', [strtolower($customer->email)]);
                    });
                }
            })
            ->with('items')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $orders->map(static function (Order $order): array {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'currency' => $order->currency,
                    'subtotal_kobo' => $order->subtotal_kobo,
                    'total_kobo' => $order->total_kobo,
                    'customer_email' => $order->customer_email,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'items' => $order->items->map(static fn ($item): array => [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'line_total_kobo' => $item->line_total_kobo,
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    public function addressesIndex(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        if ($customer === null) {
            return $this->unauthorized();
        }

        $addresses = CustomerAddress::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $addresses->map(fn (CustomerAddress $address): array => $this->addressPayload($address))->values(),
        ]);
    }

    public function addressesStore(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        if ($customer === null) {
            return $this->unauthorized();
        }

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:64'],
            'line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'lga' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if (($validated['is_default'] ?? false) === true) {
            CustomerAddress::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->update(['is_default' => false]);
        }

        $address = CustomerAddress::query()->create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'label' => $validated['label'] ?? null,
            'line1' => $validated['line1'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'lga' => $validated['lga'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ]);

        return response()->json(['data' => $this->addressPayload($address)], Response::HTTP_CREATED);
    }

    public function addressesDestroy(Request $request, string $id): JsonResponse
    {
        $customer = $this->customer($request);

        if ($customer === null) {
            return $this->unauthorized();
        }

        $address = CustomerAddress::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->whereKey($id)
            ->first();

        if ($address === null) {
            return response()->json(['message' => 'Address not found.'], Response::HTTP_NOT_FOUND);
        }

        $address->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function customer(Request $request): ?Customer
    {
        $user = $request->user();

        return $user instanceof Customer ? $user : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function addressPayload(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'line1' => $address->line1,
            'city' => $address->city,
            'state' => $address->state,
            'lga' => $address->lga,
            'phone' => $address->phone,
            'is_default' => $address->is_default,
        ];
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Customer authentication required.'], Response::HTTP_UNAUTHORIZED);
    }
}
