<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Facades;

use Illuminate\Support\Facades\Facade;
use LeviLabs\LaravelQicard\Contracts\QicardPaymentServiceContract;
use LeviLabs\LaravelQicard\Data\BrowserInfo;
use LeviLabs\LaravelQicard\Data\CustomerInfo;
use LeviLabs\LaravelQicard\Data\PaymentCancelData;
use LeviLabs\LaravelQicard\Data\PaymentData;
use LeviLabs\LaravelQicard\Data\PaymentStatusData;
use LeviLabs\LaravelQicard\Data\RefundData;

/**
 * @method static PaymentData create(float $amount, ?string $currency = null, ?CustomerInfo $customerInfo = null, ?BrowserInfo $browserInfo = null, ?array<string, string> $additionalInfo = null, ?bool $appChannel = null, ?bool $withoutAuthenticate = null, ?string $requestId = null, ?string $finishUrl = null, ?string $notificationUrl = null, ?string $terminal = null)
 * @method static PaymentStatusData status(string $paymentId, ?string $terminal = null)
 * @method static PaymentStatusData statusByRequest(string $requestId, ?string $terminal = null)
 * @method static PaymentCancelData cancel(string $paymentId, ?float $amount = null, ?string $terminal = null)
 * @method static RefundData refund(string $paymentId, ?float $amount = null, ?string $message = null, ?string $terminal = null)
 * @method static bool verifyWebhookSignature(array<string, mixed> $payload, string $signature, ?string $terminal = null)
 *
 * @see QicardPaymentServiceContract
 */
class QicardPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QicardPaymentServiceContract::class;
    }
}
