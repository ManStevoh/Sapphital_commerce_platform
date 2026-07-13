<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Platform\FinancialServices\Enums\DisputeStatus;
use Platform\FinancialServices\Models\Dispute;
use Platform\FinancialServices\Services\DisputeService;
use Symfony\Component\HttpFoundation\Response;

final class DisputeController
{
    public function __construct(
        private readonly DisputeService $disputes,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $disputes = Dispute::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $disputes->map(fn (Dispute $dispute): array => $this->payload($dispute))->values(),
        ]);
    }

    public function resolve(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['won', 'lost', 'withdrawn'])],
        ]);

        try {
            $dispute = $this->disputes->resolve(
                $tenantId,
                $id,
                DisputeStatus::from($validated['status']),
            );
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (ModelNotFoundException) {
            return $this->notFoundResponse('Dispute not found.');
        }

        return response()->json([
            'data' => $this->payload($dispute),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Dispute $dispute): array
    {
        return [
            'id' => $dispute->id,
            'tenant_id' => $dispute->tenant_id,
            'order_id' => $dispute->order_id,
            'type' => $dispute->type,
            'provider' => $dispute->provider,
            'status' => $dispute->status->value,
            'provider_case_id' => $dispute->provider_case_id,
            'amount_kobo' => $dispute->amount_kobo,
            'currency' => $dispute->currency,
            'paystack_reference' => $dispute->paystack_reference,
            'due_at' => $dispute->due_at?->toIso8601String(),
            'resolved_at' => $dispute->resolved_at?->toIso8601String(),
            'created_at' => $dispute->created_at?->toIso8601String(),
        ];
    }

    private function validationError(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => collect($exception->errors())->flatten()->first()
                ?? 'Validation failed.',
            'errors' => $exception->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json(['message' => $message], Response::HTTP_NOT_FOUND);
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
