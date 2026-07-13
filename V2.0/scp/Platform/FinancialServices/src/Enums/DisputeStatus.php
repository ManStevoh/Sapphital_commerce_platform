<?php

declare(strict_types=1);

namespace Platform\FinancialServices\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case Won = 'won';
    case Lost = 'lost';
    case Withdrawn = 'withdrawn';
}
