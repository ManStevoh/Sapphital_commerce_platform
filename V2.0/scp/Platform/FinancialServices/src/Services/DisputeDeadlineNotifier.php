<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Platform\FinancialServices\Models\Dispute;
use Platform\Identity\Enums\MerchantUserRole;
use Platform\Identity\Models\MerchantUser;
use Platform\Tenancy\Models\Tenant;

final class DisputeDeadlineNotifier
{
    public function notify(Dispute $dispute): void
    {
        $tenant = Tenant::query()->find($dispute->tenant_id);
        $storeName = is_string($tenant?->name) ? $tenant->name : 'your store';
        $dueAt = $dispute->due_at?->timezone('Africa/Lagos')->format('Y-m-d H:i T') ?? 'soon';

        $recipients = MerchantUser::query()
            ->where('tenant_id', $dispute->tenant_id)
            ->whereIn('role', [MerchantUserRole::Owner, MerchantUserRole::Admin, MerchantUserRole::Finance])
            ->pluck('email')
            ->filter(static fn ($email): bool => is_string($email) && $email !== '')
            ->unique()
            ->values()
            ->all();

        $subject = "Chargeback evidence due — {$storeName}";
        $body = "A chargeback dispute (case {$dispute->provider_case_id}) on order {$dispute->order_id} "
            ."requires attention. Evidence is due by {$dueAt}. "
            .'Review the dispute in your merchant admin.';

        if (app()->environment('testing')) {
            Log::info('dispute.deadline.notify', [
                'dispute_id' => $dispute->id,
                'tenant_id' => $dispute->tenant_id,
                'recipients' => $recipients,
                'due_at' => $dispute->due_at?->toIso8601String(),
            ]);

            return;
        }

        foreach ($recipients as $email) {
            Mail::raw($body, static function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
        }
    }
}
