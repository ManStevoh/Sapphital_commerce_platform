<?php

declare(strict_types=1);

namespace Platform\Identity\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Platform\Identity\Models\MerchantUser;

final class MerchantLoginNotifier
{
    public function notify(MerchantUser $user, Request $request): void
    {
        $email = $user->email;

        if (! is_string($email) || $email === '') {
            return;
        }

        $ip = $request->ip() ?? 'unknown';
        $userAgent = (string) $request->userAgent();
        $subject = 'New sign-in to your SAPPHITAL merchant account';
        $body = "We detected a new sign-in to your merchant account ({$email}).\n\n"
            ."IP: {$ip}\n"
            .'Device: '.($userAgent !== '' ? $userAgent : 'unknown')."\n\n"
            ."If this wasn't you, revoke active sessions in Admin → Security.";

        if (app()->environment('testing')) {
            Log::info('merchant.login.notification', [
                'merchant_user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'email' => $email,
                'ip' => $ip,
            ]);

            return;
        }

        Mail::raw($body, function ($message) use ($email, $subject): void {
            $message->to($email)->subject($subject);
        });
    }
}
