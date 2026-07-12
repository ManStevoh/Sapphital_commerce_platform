<?php

declare(strict_types=1);

namespace Shared\Money\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shared\Money\Money;

final class MoneyTest extends TestCase
{
    public function test_format_returns_naira_string(): void
    {
        $money = new Money(150_000);

        $this->assertSame('₦1,500.00', $money->format());
    }

    public function test_amount_is_stored_in_kobo(): void
    {
        $money = new Money(99);

        $this->assertSame(99, $money->amountKobo());
        $this->assertSame('₦0.99', $money->format());
    }

    public function test_default_currency_is_ngn(): void
    {
        $money = new Money(100);

        $this->assertSame('NGN', $money->currency());
    }
}
