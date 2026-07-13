<?php

declare(strict_types=1);

namespace Platform\Notifications\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Commerce\Orders\Models\Order;
use Platform\FinancialServices\Models\Refund;

final class RefundConfirmationNotifier
{
    public function send(Order $order, Refund $refund): void
    {
        $email = $order->customer_email;

        if (! is_string($email) || $email === '') {
            return;
        }

        $amount = number_format($refund->amount_kobo / 100, 2);
        $subject = "Refund processed — {$order->order_number}";
        $body = "A refund of NGN {$amount} has been processed for order {$order->order_number}. "
            .'It may take a few business days to appear on your statement.';

        if (app()->environment('testing')) {
            Log::info('refund.confirmation', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'email' => $email,
                'amount_kobo' => $refund->amount_kobo,
            ]);

            return;
        }

        Mail::raw($body, function ($message) use ($email, $subject): void {
            $message->to($email)->subject($subject);
        });
    }
}
