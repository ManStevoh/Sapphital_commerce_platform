<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Platform\FinancialServices\Services\PaymentReconciliationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PaymentReconciliationController
{
    public function __construct(
        private readonly PaymentReconciliationService $reconciliation,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        try {
            [$from, $to] = $this->resolvePeriod($request);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return response()->json([
            'data' => $this->reconciliation->buildReport($tenantId, $from, $to),
        ]);
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return $this->missingTenantResponse();
        }

        try {
            [$from, $to] = $this->resolvePeriod($request);
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        $report = $this->reconciliation->buildReport($tenantId, $from, $to);
        $csv = $this->reconciliation->toCsv($report);
        $filename = sprintf(
            'payments-reconciliation-%s-to-%s.csv',
            $report['period']['from'],
            $report['period']['to'],
        );

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $to = isset($validated['to'])
            ? Carbon::parse((string) $validated['to'])
            : now();

        $from = isset($validated['from'])
            ? Carbon::parse((string) $validated['from'])
            : $to->copy()->subDays(30);

        if ($from->greaterThan($to)) {
            throw ValidationException::withMessages([
                'from' => ['Start date must be before end date.'],
            ]);
        }

        return [$from, $to];
    }

    private function validationError(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => collect($exception->errors())->flatten()->first()
                ?? 'Validation failed.',
            'errors' => $exception->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
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
