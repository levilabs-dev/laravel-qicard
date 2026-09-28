<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Console\Commands;

use Illuminate\Console\Command;
use LeviLabs\LaravelQicard\Contracts\QicardPaymentServiceContract;
use LeviLabs\LaravelQicard\Enums\PaymentStatus;
use LeviLabs\LaravelQicard\Models\QicardPayment;
use Throwable;

/**
 * Re-checks payments still stuck in a non-terminal status directly against
 * QiCard. QiCard's own notification is a best-effort webhook — if your
 * endpoint was down, the URL was misconfigured, or the notification was
 * simply never delivered, a payment can sit in a non-terminal status
 * forever without this.
 */
class SyncQicardStatuses extends Command
{
    protected $signature = 'qicard:sync-statuses {--limit=100}';

    protected $description = 'Re-check non-terminal QiCard payment statuses directly from the QiCard API';

    public function handle(QicardPaymentServiceContract $payments): int
    {
        $limit = (int) $this->option('limit');

        $terminalStatuses = array_map(
            fn (PaymentStatus $status): string => $status->value,
            array_filter(PaymentStatus::cases(), fn (PaymentStatus $status): bool => $status->isTerminal()),
        );

        $pending = QicardPayment::query()
            ->whereNotIn('status', $terminalStatuses)
            ->limit($limit)
            ->get();

        foreach ($pending as $payment) {
            try {
                $payments->status($payment->payment_id, $payment->terminal);
            } catch (Throwable $e) {
                $this->warn("Payment {$payment->payment_id} could not be re-checked: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$pending->count()} non-terminal payment(s).");

        return self::SUCCESS;
    }
}
