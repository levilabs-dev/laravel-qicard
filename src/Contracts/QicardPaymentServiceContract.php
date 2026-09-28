<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Contracts;

use LeviLabs\LaravelQicard\Data\BrowserInfo;
use LeviLabs\LaravelQicard\Data\CustomerInfo;
use LeviLabs\LaravelQicard\Data\PaymentCancelData;
use LeviLabs\LaravelQicard\Data\PaymentData;
use LeviLabs\LaravelQicard\Data\PaymentStatusData;
use LeviLabs\LaravelQicard\Data\RefundData;

interface QicardPaymentServiceContract
{
    /**
     * Creates a Payment and returns the hosted payment form URL to redirect
     * the payer to.
     *
     * @param  array<string, string>|null  $additionalInfo  up to 10 string key/value pairs, echoed back on every subsequent response.
     */
    public function create(
        float $amount,
        ?string $currency = null,
        ?CustomerInfo $customerInfo = null,
        ?BrowserInfo $browserInfo = null,
        ?array $additionalInfo = null,
        ?bool $appChannel = null,
        ?bool $withoutAuthenticate = null,
        ?string $requestId = null,
        ?string $finishUrl = null,
        ?string $notificationUrl = null,
        ?string $terminal = null,
    ): PaymentData;

    /**
     * The authoritative source of truth for a payment's outcome. Always call
     * this before fulfilling an order — never trust a webhook body or the
     * finishUrl redirect alone.
     */
    public function status(string $paymentId, ?string $terminal = null): PaymentStatusData;

    /**
     * Same as status(), but looked up by the requestId you generated when
     * calling create() instead of QiCard's paymentId — useful when a
     * create() call's response never arrived (e.g. a timeout) and you don't
     * have a paymentId to poll with.
     */
    public function statusByRequest(string $requestId, ?string $terminal = null): PaymentStatusData;

    /**
     * Cancels a payment. Only possible before it has entered processing
     * (status CREATED/FORM_SHOWED) or once it's SUCCESS but still awaiting
     * confirmation. Omit $amount to cancel the full amount.
     */
    public function cancel(string $paymentId, ?float $amount = null, ?string $terminal = null): PaymentCancelData;

    /**
     * Full or partial refund to the payer. The sum of all refunds on a
     * payment must never exceed the original payment amount. Omit $amount
     * to refund the full amount.
     */
    public function refund(
        string $paymentId,
        ?float $amount = null,
        ?string $message = null,
        ?string $terminal = null,
    ): RefundData;

    /**
     * Verifies the `X-Signature` header on an incoming webhook notification
     * against the terminal's configured public key. $payload is the
     * JSON-decoded request body (the full Payment object QiCard POSTs).
     *
     * Never trust a webhook body until this returns true.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, string $signature, ?string $terminal = null): bool;
}
