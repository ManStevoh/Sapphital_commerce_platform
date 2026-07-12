<?php

declare(strict_types=1);

namespace Shared\Money;

final class Money
{
    public function __construct(
        private readonly int $amountKobo,
        private readonly string $currency = 'NGN',
    ) {}

    public function amountKobo(): int
    {
        return $this->amountKobo;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function format(): string
    {
        $naira = $this->amountKobo / 100;

        return '₦'.number_format($naira, 2);
    }
}
