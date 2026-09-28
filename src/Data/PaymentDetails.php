<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Data;

/**
 * The `details` object attached to a Payment/Refund once the underlying card
 * network has processed the transaction. Every field is nullable — QiCard
 * only fills in what's relevant to the payment's current status.
 */
final readonly class PaymentDetails
{
    public function __construct(
        public ?string $resultCode,
        public ?string $resultDescription,
        public ?string $rrn,
        public ?string $externalRrn,
        public ?string $authId,
        public ?string $authDate,
        public ?string $maskedPan,
        public ?string $paymentSystem,
        /** @var array<string, mixed>|null */
        public ?array $customDetails,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            resultCode: $data['resultCode'] ?? null,
            resultDescription: $data['resultDescription'] ?? null,
            rrn: $data['rrn'] ?? null,
            externalRrn: $data['externalRrn'] ?? null,
            authId: $data['authId'] ?? null,
            authDate: $data['authDate'] ?? null,
            maskedPan: $data['maskedPan'] ?? null,
            paymentSystem: $data['paymentSystem'] ?? null,
            customDetails: $data['customDetails'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resultCode' => $this->resultCode,
            'resultDescription' => $this->resultDescription,
            'rrn' => $this->rrn,
            'externalRrn' => $this->externalRrn,
            'authId' => $this->authId,
            'authDate' => $this->authDate,
            'maskedPan' => $this->maskedPan,
            'paymentSystem' => $this->paymentSystem,
            'customDetails' => $this->customDetails,
        ];
    }
}
