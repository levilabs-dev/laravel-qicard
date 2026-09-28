<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Listeners;

use LeviLabs\LaravelQicard\Events\PaymentCancelled;
use LeviLabs\LaravelQicard\Events\PaymentCreated;
use LeviLabs\LaravelQicard\Events\PaymentRefunded;
use LeviLabs\LaravelQicard\Events\PaymentStatusChecked;
use LeviLabs\LaravelQicard\Models\QicardPayment;
use LeviLabs\LaravelQicard\Models\QicardRefund;

final class PersistQicardPayment
{
    public function onCreated(PaymentCreated $event): void
    {
        $payment = $event->payment;

        QicardPayment::query()->updateOrCreate(
            ['payment_id' => $payment->paymentId],
            [
                'terminal' => $event->terminal,
                'request_id' => $payment->requestId,
                'status' => $payment->status,
                'canceled' => $payment->canceled,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'form_url' => $payment->formUrl,
                'gateway_created_at' => $payment->creationDate,
            ],
        );
    }

    public function onStatusChecked(PaymentStatusChecked $event): void
    {
        $status = $event->status;

        QicardPayment::query()->updateOrCreate(
            ['payment_id' => $status->paymentId],
            array_filter([
                'terminal' => $event->terminal,
                'request_id' => $status->requestId,
                'status' => $status->status,
                'canceled' => $status->canceled,
                'amount' => $status->amount,
                'confirmed_amount' => $status->confirmedAmount,
                'currency' => $status->currency,
                'payment_type' => $status->paymentType,
                'details' => $status->details?->toArray(),
                'gateway_created_at' => $status->creationDate,
                'checked_at' => now(),
            ], fn ($value) => $value !== null),
        );
    }

    public function onCancelled(PaymentCancelled $event): void
    {
        $cancellation = $event->cancellation;

        QicardPayment::query()->where('payment_id', $cancellation->paymentId)->update([
            'status' => $cancellation->status,
            'canceled' => $cancellation->canceled,
            'amount' => $cancellation->amount,
        ]);
    }

    public function onRefunded(PaymentRefunded $event): void
    {
        $refund = $event->refund;

        $payment = QicardPayment::query()->where('payment_id', $refund->paymentId)->first();

        if ($payment === null) {
            return;
        }

        QicardRefund::query()->updateOrCreate(
            ['refund_id' => $refund->refundId],
            [
                'payment_id' => $payment->id,
                'request_id' => $refund->requestId,
                'status' => $refund->status,
                'canceled' => $refund->canceled,
                'amount' => $refund->amount,
                'currency' => $refund->currency,
                'message' => $refund->message,
                'details' => $refund->details?->toArray(),
                'gateway_created_at' => $refund->creationDate,
            ],
        );
    }
}
