<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Data;

use Carbon\CarbonImmutable;
use LeviLabs\LaravelQicard\Enums\RefundStatus;

/**
 * The response to refundPayment (the `Refund` schema).
 */
final readonly class RefundData
{
    /**
     * @param  array<int, CancelEntry>  $cancels
     */
    public function __construct(
        public string $refundId,
        public ?string $requestId,
        public string $paymentId,
        public float $amount,
        public string $currency,
        public CarbonImmutable $creationDate,
        public ?string $message,
        public ?PaymentDetails $details,
        public RefundStatus $status,
        public bool $canceled,
        public array $cancels,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            refundId: $data['refundId'],
            requestId: $data['requestId'] ?? null,
            paymentId: $data['paymentId'],
            amount: (float) $data['amount'],
            currency: $data['currency'],
            creationDate: CarbonImmutable::parse($data['creationDate']),
            message: $data['message'] ?? null,
            details: isset($data['details']) ? PaymentDetails::fromArray($data['details']) : null,
            status: RefundStatus::from($data['status']),
            canceled: (bool) ($data['canceled'] ?? false),
            cancels: CancelEntry::collection($data['cancels'] ?? []),
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
            'refundId' => $this->refundId,
            'requestId' => $this->requestId,
            'paymentId' => $this->paymentId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'creationDate' => $this->creationDate->toIso8601String(),
            'message' => $this->message,
            'details' => $this->details?->toArray(),
            'status' => $this->status->value,
            'canceled' => $this->canceled,
            'cancels' => CancelEntry::toArrayCollection($this->cancels),
        ];
    }
}
