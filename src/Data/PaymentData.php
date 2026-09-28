<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Data;

use Carbon\CarbonImmutable;
use LeviLabs\LaravelQicard\Enums\PaymentStatus;

/**
 * The response to createPayment (the `Payment` schema). Freshly-created
 * payments are always CREATED and carry a formUrl — redirect the payer there.
 */
final readonly class PaymentData
{
    public function __construct(
        public string $requestId,
        public string $paymentId,
        public PaymentStatus $status,
        public bool $canceled,
        public float $amount,
        public string $currency,
        public CarbonImmutable $creationDate,
        public ?string $formUrl,
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
            currency: $data['currency'],
            creationDate: CarbonImmutable::parse($data['creationDate']),
            formUrl: $data['formUrl'] ?? null,
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
            'currency' => $this->currency,
            'creationDate' => $this->creationDate->toIso8601String(),
            'formUrl' => $this->formUrl,
            'additionalInfo' => $this->additionalInfo,
        ];
    }
}
