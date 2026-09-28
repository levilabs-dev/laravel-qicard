<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelQicard\Data\RefundData;

final class PaymentRefunded
{
    use Dispatchable;

    public function __construct(
        public readonly RefundData $refund,
        public readonly string $terminal,
    ) {}
}
