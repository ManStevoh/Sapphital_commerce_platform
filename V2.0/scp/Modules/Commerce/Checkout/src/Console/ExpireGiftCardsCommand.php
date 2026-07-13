<?php

declare(strict_types=1);

namespace Modules\Commerce\Checkout\Console;

use Illuminate\Console\Command;
use Modules\Commerce\Checkout\Services\GiftCardService;

final class ExpireGiftCardsCommand extends Command
{
    protected $signature = 'checkout:expire-gift-cards';

    protected $description = 'Mark gift cards past expires_at as expired';

    public function handle(GiftCardService $giftCards): int
    {
        $count = $giftCards->expireDue();

        if ($count === 0) {
            $this->info('No gift cards to expire.');

            return self::SUCCESS;
        }

        $this->info("Expired {$count} gift card(s).");

        return self::SUCCESS;
    }
}
