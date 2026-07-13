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
use Modules\Content\Cms\Enums\ContentStatus;
use Modules\Content\Cms\Enums\PageContentType;
use Modules\Content\Cms\Models\Page;
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

            $defaultPages = [
                [
                    'slug' => 'about',
                    'title' => 'About Us',
                    'content_type' => PageContentType::Page,
                    'body' => 'Welcome to our store. We are proud to serve customers across Nigeria.',
                ],
                [
                    'slug' => 'contact',
                    'title' => 'Contact Us',
                    'content_type' => PageContentType::Page,
                    'body' => 'Reach us by email or phone. We typically respond within one business day.',
                ],
                [
                    'slug' => 'privacy',
                    'title' => 'Privacy Policy',
                    'content_type' => PageContentType::Legal,
                    'body' => 'We process personal data in line with the Nigeria Data Protection Act (NDPA).',
                ],
                [
                    'slug' => 'shipping-policy',
                    'title' => 'Shipping Policy',
                    'content_type' => PageContentType::Legal,
                    'body' => 'Orders ship within Lagos in 1–3 business days. Nationwide delivery in 3–7 business days.',
                ],
                [
                    'slug' => 'return-policy',
                    'title' => 'Return Policy',
                    'content_type' => PageContentType::Legal,
                    'body' => 'Contact the store within 14 days of delivery for return eligibility on physical goods.',
                ],
            ];

            foreach ($defaultPages as $defaultPage) {
                Page::query()->create([
                    'tenant_id' => $run->tenant_id,
                    'title' => $defaultPage['title'],
                    'slug' => $defaultPage['slug'],
                    'content_type' => $defaultPage['content_type'],
                    'body_json' => [
                        'sections' => [
                            ['type' => 'rich-text', 'content' => $defaultPage['body']],
                        ],
                    ],
                    'seo_title' => $defaultPage['title'],
                    'status' => ContentStatus::Published,
                    'published_at' => now(),
                ]);
            }

            $steps['create_pages'] = [
                'completed' => true,
                'data' => [
                    'pages' => array_map(
                        static fn (array $page): array => [
                            'slug' => $page['slug'],
                            'title' => $page['title'],
                        ],
                        $defaultPages,
                    ),
                    'count' => count($defaultPages),
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
