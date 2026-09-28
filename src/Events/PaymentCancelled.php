<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelQicard\Data\PaymentCancelData;

final class PaymentCancelled
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentCancelData $cancellation,
        public readonly string $terminal,
    ) {}
}
