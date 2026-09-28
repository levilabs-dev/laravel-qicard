<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelQicard\Data\PaymentStatusData;

/**
 * Fired by every status() call — whether triggered by a webhook
 * notification, the finishUrl return page, or the qicard:sync-statuses
 * command. This is what PersistQicardPayment listens to for updating
 * `qicard_payments`.
 */
final class PaymentStatusChecked
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentStatusData $status,
        public readonly string $terminal,
    ) {}
}
