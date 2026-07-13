<?php

declare(strict_types=1);

namespace Platform\Provisioning\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Modules\Commerce\Catalog\Models\Product;
use Platform\Provisioning\Enums\ProvisioningRunStatus;
use Platform\Provisioning\Models\ProvisioningRun;
use Platform\Tenancy\Models\Tenant;
use Throwable;

final class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $provisioningRunId,
    ) {}

    /**
     * @return array<string, array{completed: bool, data?: mixed}>
     */
    public static function initialSteps(): array
    {
        return [
            'create_default_store_settings' => ['completed' => false],
            'assign_theme' => ['completed' => false],
            'seed_sample_products' => ['completed' => false],
            'create_pages' => ['completed' => false],
            'configure_paystack_placeholder' => ['completed' => false],
        ];
    }

    public function handle(): void
    {
        $run = ProvisioningRun::query()->findOrFail($this->provisioningRunId);

        $run->update([
            'status' => ProvisioningRunStatus::Running,
            'started_at' => now(),
        ]);

        try {
            $steps = $run->steps;

            $steps['create_default_store_settings'] = [
                'completed' => true,
                'data' => [
                    'currency' => 'NGN',
                    'timezone' => 'Africa/Lagos',
                ],
            ];

            $steps['assign_theme'] = [
                'completed' => true,
                'data' => [
                    'theme' => 'scp-dawn',
                ],
            ];

            $sampleProducts = [
                ['name' => 'Sample Product 1', 'price_kobo' => 500_000, 'inventory_qty' => 10],
                ['name' => 'Sample Product 2', 'price_kobo' => 1_200_000, 'inventory_qty' => 10],
                ['name' => 'Sample Product 3', 'price_kobo' => 2_500_000, 'inventory_qty' => 0],
            ];

            foreach ($sampleProducts as $index => $sample) {
                Product::query()->create([
                    'tenant_id' => $run->tenant_id,
                    'name' => $sample['name'],
                    'slug' => Str::slug($sample['name']).'-'.($index + 1),
                    'price_kobo' => $sample['price_kobo'],
                    'status' => 'published',
                    'inventory_qty' => $sample['inventory_qty'],
                ]);
            }

            $steps['seed_sample_products'] = [
                'completed' => true,
                'data' => [
                    'products' => $sampleProducts,
                    'count' => count($sampleProducts),
                ],
            ];

            $steps['create_pages'] = [
                'completed' => true,
                'data' => [
                    'pages' => [
                        ['slug' => 'about', 'title' => 'About Us'],
                        ['slug' => 'contact', 'title' => 'Contact Us'],
                    ],
                ],
            ];

            $steps['configure_paystack_placeholder'] = [
                'completed' => true,
                'data' => [
                    'provider' => 'paystack',
                    'mode' => 'test',
                    'configured' => false,
                ],
            ];

            $run->update(['steps' => $steps]);

            Tenant::query()
                ->whereKey($run->tenant_id)
                ->update([
                    'status' => 'trial',
                    'settings' => [
                        'currency' => 'NGN',
                        'timezone' => 'Africa/Lagos',
                        'return_window_days' => 14,
                        'payment_provider' => 'paystack',
                    ],
                ]);

            $run->update([
                'status' => ProvisioningRunStatus::Completed,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Tenant::query()
                ->whereKey($run->tenant_id)
                ->update(['status' => 'failed']);

            $run->update([
                'status' => ProvisioningRunStatus::Failed,
                'error' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        }
    }
}
