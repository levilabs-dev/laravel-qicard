<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelQicard\Contracts\QicardPaymentServiceContract;
use LeviLabs\LaravelQicard\Data\PaymentCancelData;
use LeviLabs\LaravelQicard\Data\PaymentData;
use LeviLabs\LaravelQicard\Enums\PaymentStatus;
use LeviLabs\LaravelQicard\Enums\RefundStatus;
use LeviLabs\LaravelQicard\Exceptions\QicardException;
use LeviLabs\LaravelQicard\Models\QicardPayment;
use LeviLabs\LaravelQicard\Models\QicardRefund;

it('creates a payment and persists it', function () {
    Http::fake([
        '*/payment' => Http::response([
            'requestId' => '4256ab83-de74-450f-b442-8fb995458243',
            'paymentId' => 'f2bb43a8-488a-4281-977b-5b3418fc3c67',
            'status' => 'CREATED',
            'canceled' => false,
            'amount' => 256.89,
            'currency' => 'IQD',
            'creationDate' => '2024-08-04T15:34:33Z',
            'formUrl' => 'https://uat-sandbox-3ds-api.qi.iq/api/v1/payment/f2bb43a8-488a-4281-977b-5b3418fc3c67',
        ], 200),
    ]);

    $payment = app(QicardPaymentServiceContract::class)->create(amount: 256.89);

    expect($payment)->toBeInstanceOf(PaymentData::class)
        ->and($payment->status)->toBe(PaymentStatus::Created)
        ->and($payment->formUrl)->toContain('f2bb43a8-488a-4281-977b-5b3418fc3c67');

    expect(QicardPayment::query()->where('payment_id', 'f2bb43a8-488a-4281-977b-5b3418fc3c67')->value('amount'))
        ->toEqual(256.89);
});

it('rejects a non-positive amount before sending a request', function () {
    app(QicardPaymentServiceContract::class)->create(amount: 0);
})->throws(InvalidArgumentException::class);

it('throws with the resolved error name when QiCard returns an error envelope', function () {
    Http::fake([
        '*/payment' => Http::response([
            'error' => ['code' => 18, 'message' => 'REFUNDS_NOT_ALLOWED'],
        ], 400),
    ]);

    app(QicardPaymentServiceContract::class)->create(amount: 100);
})->throws(QicardException::class, 'REFUNDS_NOT_ALLOWED');

it('resolves an error by numeric code alone when the API omits the message', function () {
    Http::fake([
        '*/payment/*/refund' => Http::response([
            'error' => ['code' => 30],
        ], 400),
    ]);

    app(QicardPaymentServiceContract::class)->refund('f2bb43a8-488a-4281-977b-5b3418fc3c67');
})->throws(QicardException::class, 'INCORRECT_TRANSFER_STATE');

it('fetches payment status and persists it', function () {
    Http::fake([
        '*/payment/*/status' => Http::response([
            'requestId' => '618faf78-0ef5-4c19-a06b-86199fb77d02',
            'paymentId' => '56fe7b8d-b5af-4036-8c2d-5d2e7b2faaf7',
            'status' => 'SUCCESS',
            'canceled' => false,
            'amount' => 256.89,
            'confirmedAmount' => 256.89,
            'currency' => 'IQD',
            'paymentType' => 'CARD',
            'creationDate' => '2024-08-06T15:14:46Z',
            'details' => [
                'resultCode' => '00',
                'rrn' => '421900005327',
                'authId' => '052045',
                'maskedPan' => '521372******8582',
                'paymentSystem' => 'MASTER_CARD',
            ],
        ], 200),
    ]);

    $status = app(QicardPaymentServiceContract::class)->status('56fe7b8d-b5af-4036-8c2d-5d2e7b2faaf7');

    expect($status->isSuccessful())->toBeTrue()
        ->and($status->details?->maskedPan)->toBe('521372******8582');

    expect(QicardPayment::query()->where('payment_id', '56fe7b8d-b5af-4036-8c2d-5d2e7b2faaf7')->value('status'))
        ->toBe(PaymentStatus::Success);
});

it('cancels a payment', function () {
    Http::fake([
        '*/payment/*/cancel' => Http::response([
            'requestId' => '142acf33-2146-4f06-b190-b74452b98f82',
            'paymentId' => 'd83c50ba-65c3-41e4-97fc-7209cc29bce4',
            'status' => 'CREATED',
            'canceled' => true,
            'amount' => 256.89,
            'currency' => 'IQD',
            'creationDate' => '2024-08-06T15:24:48Z',
            'cancels' => [
                ['requestId' => '5df5c849-9258-4f9b-87d0-f0b467735331', 'created' => '2024-08-06T15:26:18Z', 'successfully' => true, 'amount' => 256.89],
            ],
        ], 200),
    ]);

    $cancellation = app(QicardPaymentServiceContract::class)->cancel('d83c50ba-65c3-41e4-97fc-7209cc29bce4');

    expect($cancellation)->toBeInstanceOf(PaymentCancelData::class)
        ->and($cancellation->canceled)->toBeTrue()
        ->and($cancellation->cancels[0]->successfully)->toBeTrue();
});

it('refunds a payment and persists it', function () {
    QicardPayment::query()->create([
        'terminal' => 'default',
        'payment_id' => 'f2bb43a8-488a-4281-977b-5b3418fc3c67',
        'status' => PaymentStatus::Success,
        'amount' => 256.89,
        'currency' => 'IQD',
    ]);

    Http::fake([
        '*/payment/*/refund' => Http::response([
            'refundId' => '37b85e60-e7a6-4abb-9466-703472fb83b9',
            'requestId' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
            'paymentId' => 'f2bb43a8-488a-4281-977b-5b3418fc3c67',
            'amount' => 256.89,
            'currency' => 'IQD',
            'creationDate' => '2020-05-14T12:06:39Z',
            'message' => 'Requested by customer',
            'status' => 'SUCCESS',
            'canceled' => false,
        ], 200),
    ]);

    $refund = app(QicardPaymentServiceContract::class)->refund('f2bb43a8-488a-4281-977b-5b3418fc3c67', message: 'Requested by customer');

    expect($refund->isSuccessful())->toBeTrue()
        ->and($refund->status)->toBe(RefundStatus::Success);

    expect(QicardRefund::query()->where('refund_id', '37b85e60-e7a6-4abb-9466-703472fb83b9')->value('status'))
        ->toBe(RefundStatus::Success);
});

it('rejects a non-positive refund amount before sending a request', function () {
    app(QicardPaymentServiceContract::class)->refund('f2bb43a8-488a-4281-977b-5b3418fc3c67', amount: -5);
})->throws(InvalidArgumentException::class);

it('exposes the resolved error name and numeric code as structured properties, not just in the message', function () {
    Http::fake([
        '*/payment' => Http::response(['error' => ['code' => 18, 'message' => 'REFUNDS_NOT_ALLOWED']], 400),
    ]);

    try {
        app(QicardPaymentServiceContract::class)->create(amount: 100);
    } catch (QicardException $e) {
        expect($e->errorName)->toBe('REFUNDS_NOT_ALLOWED')
            ->and($e->errorCode)->toBe(18);

        return;
    }

    $this->fail('Expected QicardException was not thrown.');
});

it('round-trips every response DTO through toArray()', function () {
    Http::fake([
        '*/payment' => Http::response([
            'requestId' => '4256ab83-de74-450f-b442-8fb995458243',
            'paymentId' => 'f2bb43a8-488a-4281-977b-5b3418fc3c67',
            'status' => 'CREATED',
            'canceled' => false,
            'amount' => 256.89,
            'currency' => 'IQD',
            'creationDate' => '2024-08-04T15:34:33Z',
            'formUrl' => 'https://uat-sandbox-3ds-api.qi.iq/api/v1/payment/f2bb43a8-488a-4281-977b-5b3418fc3c67',
        ], 200),
    ]);

    $payment = app(QicardPaymentServiceContract::class)->create(amount: 256.89);
    $array = $payment->toArray();

    expect($array)->toBeArray()
        ->and($array['paymentId'])->toBe('f2bb43a8-488a-4281-977b-5b3418fc3c67')
        ->and($array['status'])->toBe('CREATED')
        ->and(json_encode($array))->not->toBeFalse();
});
