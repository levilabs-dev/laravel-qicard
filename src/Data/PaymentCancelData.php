<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Data;

use Carbon\CarbonImmutable;
use LeviLabs\LaravelQicard\Enums\PaymentStatus;

/**
 * The response to cancelPayment (the `paymentCancelResponse` schema). Only
 * possible before a payment has entered processing (CREATED/FORM_SHOWED) or
 * once it's SUCCESS but still awaiting confirmation — see
 * QicardPaymentServiceContract::cancel() for details.
 */
final readonly class PaymentCancelData
{
    /**
     * @param  array<int, CancelEntry>  $cancels
     */
    public function __construct(
        public string $requestId,
        public string $paymentId,
        public PaymentStatus $status,
        public bool $canceled,
        public float $amount,
        public string $currency,
        public CarbonImmutable $creationDate,
        public array $cancels,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            requestId: $data['requestId'],
            paymentId: $data['paymentId'],
            status: PaymentStatus::from($data['status']),
            canceled: (bool) ($data['canceled'] ?? false),
            amount: (float) $data['amount'],
            currency: $data['currency'],
            creationDate: CarbonImmutable::parse($data['creationDate']),
            cancels: CancelEntry::collection($data['cancels'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'requestId' => $this->requestId,
            'paymentId' => $this->paymentId,
            'status' => $this->status->value,
            'canceled' => $this->canceled,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'creationDate' => $this->creationDate->toIso8601String(),
            'cancels' => CancelEntry::toArrayCollection($this->cancels),
        ];
    }
}
