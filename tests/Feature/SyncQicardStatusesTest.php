<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelQicard\Enums\PaymentStatus;
use LeviLabs\LaravelQicard\Models\QicardPayment;

it('re-checks only non-terminal payments', function () {
    QicardPayment::query()->create([
        'terminal' => 'default',
        'payment_id' => 'pending-1',
        'status' => PaymentStatus::FormShowed,
        'amount' => 100,
        'currency' => 'IQD',
    ]);

    QicardPayment::query()->create([
        'terminal' => 'default',
        'payment_id' => 'already-success',
        'status' => PaymentStatus::Success,
        'amount' => 100,
        'currency' => 'IQD',
    ]);

    Http::fake([
        '*/payment/pending-1/status' => Http::response([
            'requestId' => 'req-1',
            'paymentId' => 'pending-1',
            'status' => 'SUCCESS',
            'canceled' => false,
            'amount' => 100,
            'currency' => 'IQD',
            'creationDate' => '2024-08-06T15:14:46Z',
        ], 200),
    ]);

    $this->artisan('qicard:sync-statuses')->assertSuccessful();

    Http::assertSentCount(1);
    expect(QicardPayment::query()->where('payment_id', 'pending-1')->value('status'))->toBe(PaymentStatus::Success);
});

it('keeps going after one payment fails to re-check', function () {
    QicardPayment::query()->create([
        'terminal' => 'default',
        'payment_id' => 'will-error',
        'status' => PaymentStatus::Created,
        'amount' => 100,
        'currency' => 'IQD',
    ]);

    Http::fake([
        '*/payment/*/status' => Http::response(['error' => ['code' => 12, 'message' => 'PAYMENT_NOT_FOUND']], 400),
    ]);

    $this->artisan('qicard:sync-statuses')->assertSuccessful();
});
