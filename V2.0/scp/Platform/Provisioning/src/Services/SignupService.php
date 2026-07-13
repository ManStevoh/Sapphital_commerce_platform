<?php

declare(strict_types=1);

namespace Platform\Provisioning\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Platform\Billing\Enums\SubscriptionStatus;
use Platform\Billing\Models\Plan;
use Platform\Billing\Models\Subscription;
use Platform\Identity\Services\SignupHandoffService;
use Platform\Tenancy\Models\Tenant;

final class SignupService
{
    public function __construct(
        private readonly ProvisionTenantService $provisionTenantService,
        private readonly SignupHandoffService $handoff,
    ) {}

    /**
     * @return array{
     *     tenant_id: string,
     *     provisioning_run_id: string,
     *     status: string,
     *     admin_handoff_token: string,
     *     email: string
     * }
     */
    public function signup(string $email, string $password, string $storeName, string $planSlug): array
    {
        return DB::transaction(function () use ($email, $password, $storeName, $planSlug): array {
            $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();

            $tenant = Tenant::query()->create([
                'slug' => $this->uniqueSlug($storeName),
                'name' => $storeName,
                'status' => 'provisioning',
                'plan_id' => $plan->id,
                'country' => 'NG',
            ]);

            $merchantUserId = $this->createMerchantUser($tenant->id, $email, $password);

            Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Trial,
                'trial_ends_at' => now()->addDays(14),
            ]);

            $run = $this->provisionTenantService->start($tenant);

            return [
                'tenant_id' => $tenant->id,
                'provisioning_run_id' => $run->id,
                'status' => 'provisioning',
                'admin_handoff_token' => $this->handoff->create($merchantUserId, $tenant->id),
                'email' => $email,
            ];
        });
    }

    private function uniqueSlug(string $storeName): string
    {
        $base = Str::slug($storeName);

        if ($base === '') {
            $base = 'store';
        }

        $slug = $base;
        $suffix = 0;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    private function createMerchantUser(string $tenantId, string $email, string $password): string
    {
        if (class_exists(\Platform\Identity\Models\MerchantUser::class)) {
            $user = \Platform\Identity\Models\MerchantUser::query()->create([
                'tenant_id' => $tenantId,
                'email' => $email,
                'password' => $password,
                'role' => \Platform\Identity\Enums\MerchantUserRole::Owner,
            ]);

            return (string) $user->id;
        }

        $id = (string) Str::uuid();

        DB::table('merchant_users')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
