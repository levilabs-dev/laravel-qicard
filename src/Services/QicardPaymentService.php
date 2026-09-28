<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Services;

use LeviLabs\LaravelQicard\Contracts\QicardPaymentServiceContract;
use LeviLabs\LaravelQicard\Data\BrowserInfo;
use LeviLabs\LaravelQicard\Data\CustomerInfo;
use LeviLabs\LaravelQicard\Data\PaymentCancelData;
use LeviLabs\LaravelQicard\Data\PaymentData;
use LeviLabs\LaravelQicard\Data\PaymentStatusData;
use LeviLabs\LaravelQicard\Data\RefundData;
use LeviLabs\LaravelQicard\Events\PaymentCancelled;
use LeviLabs\LaravelQicard\Events\PaymentCreated;
use LeviLabs\LaravelQicard\Events\PaymentRefunded;
use LeviLabs\LaravelQicard\Events\PaymentStatusChecked;
use LeviLabs\LaravelQicard\Exceptions\QicardException;
use LeviLabs\LaravelQicard\Services\Concerns\TalksToQicard;
use LeviLabs\LaravelQicard\Support\WebhookVerifier;

final class QicardPaymentService implements QicardPaymentServiceContract
{
    use TalksToQicard;

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
    ): PaymentData {
        if ($amount <= 0) {
            throw QicardException::invalidAmount($amount);
        }

        $terminal ??= $this->defaultTerminal();
        $requestId = $this->resolveRequestId($requestId);

        $data = $this->send($terminal, 'payment creation', 'post', '/payment', array_filter([
            'requestId' => $requestId,
            'amount' => $amount,
            'currency' => $currency ?? (string) config('qicard.currency', 'IQD'),
            'locale' => config('qicard.locale'),
            'finishPaymentUrl' => $finishUrl ?? config('qicard.finish_url'),
            'notificationUrl' => $notificationUrl ?? config('qicard.notification_url'),
            'customerInfo' => $customerInfo?->toApiArray(),
            'browserInfo' => $browserInfo?->toApiArray(),
            'additionalInfo' => $additionalInfo,
            'appChannel' => $appChannel ?? (bool) config('qicard.app_channel', false),
            'withoutAuthenticate' => $withoutAuthenticate,
        ], fn ($value) => $value !== null && $value !== []));

        $payment = PaymentData::fromArray($data);

        PaymentCreated::dispatch($payment, $terminal);

        return $payment;
    }

    public function status(string $paymentId, ?string $terminal = null): PaymentStatusData
    {
        $terminal ??= $this->defaultTerminal();

        $data = $this->send($terminal, 'payment status', 'get', "/payment/{$paymentId}/status");

        $status = PaymentStatusData::fromArray($data);

        PaymentStatusChecked::dispatch($status, $terminal);

        return $status;
    }

    public function statusByRequest(string $requestId, ?string $terminal = null): PaymentStatusData
    {
        $terminal ??= $this->defaultTerminal();

        $data = $this->send($terminal, 'payment status by request', 'get', "/payment/status/by/request/{$requestId}");

        $status = PaymentStatusData::fromArray($data);

        PaymentStatusChecked::dispatch($status, $terminal);

        return $status;
    }

    public function cancel(string $paymentId, ?float $amount = null, ?string $terminal = null): PaymentCancelData
    {
        $this->assertValidAmount($amount);
        $terminal ??= $this->defaultTerminal();

        $data = $this->send($terminal, 'payment cancellation', 'post', "/payment/{$paymentId}/cancel", array_filter([
            'requestId' => $this->resolveRequestId(null),
            'amount' => $amount,
        ], fn ($value) => $value !== null));

        $cancellation = PaymentCancelData::fromArray($data);

        PaymentCancelled::dispatch($cancellation, $terminal);

        return $cancellation;
    }

    public function refund(
        string $paymentId,
        ?float $amount = null,
        ?string $message = null,
        ?string $terminal = null,
    ): RefundData {
        $this->assertValidAmount($amount);
        $terminal ??= $this->defaultTerminal();

        $data = $this->send($terminal, 'payment refund', 'post', "/payment/{$paymentId}/refund", array_filter([
            'requestId' => $this->resolveRequestId(null),
            'amount' => $amount,
            'message' => $message,
        ], fn ($value) => $value !== null));

        $refund = RefundData::fromArray($data);

        PaymentRefunded::dispatch($refund, $terminal);

        return $refund;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, string $signature, ?string $terminal = null): bool
    {
        $terminal ??= $this->defaultTerminal();
        $config = $this->terminalConfig($terminal);

        if ($config['public_key'] === null) {
            throw QicardException::missingPublicKey($terminal);
        }

        return WebhookVerifier::verify($payload, $signature, $config['public_key']);
    }
}
