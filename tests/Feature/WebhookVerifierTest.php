<?php

declare(strict_types=1);

use LeviLabs\LaravelQicard\Contracts\QicardPaymentServiceContract;
use LeviLabs\LaravelQicard\Support\WebhookVerifier;
use LeviLabs\LaravelQicard\Tests\TestCase;

// Fixture payload + signature generated once against a throwaway 2048-bit
// RSA test keypair (see TestCase::TEST_PUBLIC_KEY) — never a real QiCard key.
const FIXTURE_PAYLOAD = [
    'paymentId' => 'f2bb43a8-488a-4281-977b-5b3418fc3c67',
    'amount' => 256.89,
    'currency' => 'IQD',
    'creationDate' => '2024-08-04T15:34:33Z',
    'status' => 'SUCCESS',
];

const FIXTURE_SIGNATURE = 'kVvQlZtvkLPnWb2VRMMLvaz/iDKgttJKA2ms/OpbN+SiKzDeaCMjHk3JSETW07fF0nLlXJhWFLoA6WnAXym3KOb7vBAI/mf3inPXuusPSkmDiHj5BnODU7XjhCgl5l4o6znVerfq4zamdkjJQCKOUou5qe53EO5MLRWR1hZkHqo6OtZnL9SyEk8ugmrN6vouEWRpHk/8dVfullvIWt0wuP0S/E9ZMHNxL2Q0GVjujtnAhSUIOyUbdLrWUz+TROoJU3QxPst6R3Dqc/A8mLFPO2g9uKxz6Nx0ZQ9iFyV6lR9XahEUBP8kmbu4/IKYOYqGdgY6Sp6CxF2r0Po8zny0iw==';

it('builds the pipe-delimited dataString in the documented field order', function () {
    expect(WebhookVerifier::buildDataString(FIXTURE_PAYLOAD))
        ->toBe('f2bb43a8-488a-4281-977b-5b3418fc3c67|256.890|IQD|2024-08-04T15:34:33Z|SUCCESS');
});

it('substitutes "-" for missing or null fields', function () {
    expect(WebhookVerifier::buildDataString(['paymentId' => 'abc', 'status' => 'EXPIRED']))
        ->toBe('abc|-|-|-|EXPIRED');
});

it('formats the amount to exactly three decimal places, matching what QiCard actually signs', function () {
    expect(WebhookVerifier::buildDataString(['paymentId' => 'abc', 'amount' => 3000, 'currency' => 'IQD', 'creationDate' => '-', 'status' => 'SUCCESS']))
        ->toContain('|3000.000|');
});

it('verifies a correctly signed payload', function () {
    expect(WebhookVerifier::verify(FIXTURE_PAYLOAD, FIXTURE_SIGNATURE, TestCase::TEST_PUBLIC_KEY))->toBeTrue();
});

it('rejects a tampered payload', function () {
    $tampered = [...FIXTURE_PAYLOAD, 'amount' => 999.99];

    expect(WebhookVerifier::verify($tampered, FIXTURE_SIGNATURE, TestCase::TEST_PUBLIC_KEY))->toBeFalse();
});

it('rejects a malformed base64 signature instead of throwing', function () {
    expect(WebhookVerifier::verify(FIXTURE_PAYLOAD, 'not-valid-base64!!!', TestCase::TEST_PUBLIC_KEY))->toBeFalse();
});

it('rejects an empty signature or public key', function () {
    expect(WebhookVerifier::verify(FIXTURE_PAYLOAD, '', TestCase::TEST_PUBLIC_KEY))->toBeFalse()
        ->and(WebhookVerifier::verify(FIXTURE_PAYLOAD, FIXTURE_SIGNATURE, ''))->toBeFalse();
});

it('verifies through the facade using the configured terminal public key', function () {
    expect(app(QicardPaymentServiceContract::class)->verifyWebhookSignature(FIXTURE_PAYLOAD, FIXTURE_SIGNATURE))->toBeTrue();
});
