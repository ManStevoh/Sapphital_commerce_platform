<?php

declare(strict_types=1);

namespace Platform\Notifications\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Commerce\Orders\Models\Order;

final class OrderConfirmationNotifier
{
    public function send(Order $order): void
    {
        $email = $order->customer_email;

        if (! is_string($email) || $email === '') {
            return;
        }

        $subject = "Order confirmed — {$order->order_number}";
        $body = "Thank you for your purchase. Your order {$order->order_number} has been paid and is being prepared.";

        if (app()->environment('testing')) {
            Log::info('order.confirmation', [
                'order_id' => $order->id,
                'email' => $email,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        Mail::raw($body, function ($message) use ($email, $subject): void {
            $message->to($email)->subject($subject);
        });
    }
}
