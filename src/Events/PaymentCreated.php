<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelQicard\Data\PaymentData;

final class PaymentCreated
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentData $payment,
        public readonly string $terminal,
    ) {}
}
