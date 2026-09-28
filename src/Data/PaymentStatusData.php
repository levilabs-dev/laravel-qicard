<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Data;

use Carbon\CarbonImmutable;
use LeviLabs\LaravelQicard\Enums\PaymentStatus;

/**
 * The response to getPaymentStatus (the `paymentStatusResponse` schema).
 * This is the authoritative source of truth for a payment's outcome — always
 * call status() before fulfilling an order, never trust a webhook body or
 * the finishUrl redirect alone.
 */
final readonly class PaymentStatusData
{
    public function __construct(
        public string $requestId,
        public string $paymentId,
        public PaymentStatus $status,
        public bool $canceled,
        public float $amount,
        public ?float $confirmedAmount,
        public string $currency,
        public ?string $paymentType,
        public CarbonImmutable $creationDate,
        public ?PaymentDetails $details,
        /** @var array<string, mixed>|null */
        public ?array $additionalInfo,
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
            confirmedAmount: isset($data['confirmedAmount']) ? (float) $data['confirmedAmount'] : null,
            currency: $data['currency'],
            paymentType: $data['paymentType'] ?? null,
            creationDate: CarbonImmutable::parse($data['creationDate']),
            details: isset($data['details']) ? PaymentDetails::fromArray($data['details']) : null,
            additionalInfo: $data['additionalInfo'] ?? null,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
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
            'confirmedAmount' => $this->confirmedAmount,
            'currency' => $this->currency,
            'paymentType' => $this->paymentType,
            'creationDate' => $this->creationDate->toIso8601String(),
            'details' => $this->details?->toArray(),
            'additionalInfo' => $this->additionalInfo,
        ];
    }
}
