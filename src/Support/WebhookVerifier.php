<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Support;

/**
 * Verifies the `X-Signature` header QiCard sends on webhook notifications
 * and on the getPaymentForm callback, per the "Signature verification in
 * notifications" section of the Payment Gateway API reference.
 *
 * The five fields below are concatenated with `|` into a single string, in
 * this exact order; a missing or null value becomes a literal `-`. The
 * amount is formatted to exactly three decimal places ("0.000", dot
 * separator) — matching the minor-unit precision QiCard actually signs
 * with (verified against live production webhook payloads; two decimal
 * places, which an earlier revision of this file used, makes every
 * signature check fail). A SHA-256 hash of that string is what the RSA
 * signature actually covers.
 */
final class WebhookVerifier
{
    /**
     * @param  array<string, mixed>  $payload  the JSON-decoded webhook body (a Payment object)
     * @param  string  $signature  the raw `X-Signature` header value (Base64-encoded)
     * @param  string  $publicKey  the terminal's RSA public key, PEM format
     */
    public static function verify(array $payload, string $signature, string $publicKey): bool
    {
        if ($signature === '' || $publicKey === '') {
            return false;
        }

        $signatureBytes = base64_decode($signature, true);

        if ($signatureBytes === false) {
            return false;
        }

        $dataString = self::buildDataString($payload);

        return openssl_verify($dataString, $signatureBytes, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function buildDataString(array $payload): string
    {
        $field = function (string $key) use ($payload): string {
            $value = $payload[$key] ?? null;

            return $value === null || $value === '' ? '-' : (string) $value;
        };

        $amount = isset($payload['amount'])
            ? number_format((float) $payload['amount'], 3, '.', '')
            : '-';

        return implode('|', [
            $field('paymentId'),
            $amount,
            $field('currency'),
            $field('creationDate'),
            $field('status'),
        ]);
    }
}
