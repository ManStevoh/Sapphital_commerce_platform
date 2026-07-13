<?php

declare(strict_types=1);

namespace Platform\Billing\Http\Controllers;

use Illuminate\Http\Request;
use Platform\Billing\Models\Invoice;
use Platform\Billing\Services\InvoicePdfService;
use Platform\Tenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InvoicePdfController
{
    public function __construct(
        private readonly InvoicePdfService $invoicePdf,
    ) {}

    public function __invoke(Request $request, string $id): StreamedResponse|Response
    {
        $tenantId = $this->tenantId($request);

        if ($tenantId === null) {
            return response()->json([
                'message' => 'Tenant context required.',
            ], Response::HTTP_FORBIDDEN);
        }

        $invoice = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($invoice === null) {
            return response()->json([
                'message' => 'Invoice not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $tenant = Tenant::query()->findOrFail($tenantId);
        $pdf = $this->invoicePdf->render($invoice, $tenant);
        $filename = strtolower($invoice->number).'.pdf';

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf;
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
            ],
        );
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->attributes->get('tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }
}
