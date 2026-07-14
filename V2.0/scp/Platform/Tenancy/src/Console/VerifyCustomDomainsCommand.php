<?php

declare(strict_types=1);

namespace Platform\Tenancy\Console;

use Illuminate\Console\Command;
use Platform\Tenancy\Models\CustomDomain;
use Platform\Tenancy\Services\CustomDomainService;

final class VerifyCustomDomainsCommand extends Command
{
    protected $signature = 'tenancy:verify-custom-domains {--limit=50}';

    protected $description = 'Poll pending custom domains for DNS verification and SSL provisioning';

    public function handle(CustomDomainService $domains): int
    {
        $limit = max(1, min((int) $this->option('limit'), 200));

        $pending = CustomDomain::query()
            ->whereIn('status', [
                CustomDomainService::STATUS_PENDING,
                CustomDomainService::STATUS_FAILED,
                CustomDomainService::STATUS_SSL_PROVISIONING,
            ])
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        $ok = 0;
        $failed = 0;

        foreach ($pending as $domain) {
            try {
                $domains->verify($domain->tenant_id, $domain->id);
                $ok++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $this->info("Verified {$ok}; failed {$failed}.");

        return self::SUCCESS;
    }
}
