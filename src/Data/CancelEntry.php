<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Data;

use Carbon\CarbonImmutable;

/**
 * One entry in a Payment's or Refund's `cancels` array — QiCard records
 * every cancellation attempt (partial cancels are possible), not just the
 * latest one.
 */
final readonly class CancelEntry
{
    public function __construct(
        public ?string $requestId,
        public ?CarbonImmutable $created,
        public bool $successfully,
        public float $amount,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            requestId: $data['requestId'] ?? null,
            created: isset($data['created']) ? CarbonImmutable::parse($data['created']) : null,
            successfully: (bool) ($data['successfully'] ?? false),
            amount: (float) ($data['amount'] ?? 0),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, self>
     */
    public static function collection(array $items): array
    {
        return array_map(self::fromArray(...), $items);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'requestId' => $this->requestId,
            'created' => $this->created?->toIso8601String(),
            'successfully' => $this->successfully,
            'amount' => $this->amount,
        ];
    }

    /**
     * @param  array<int, self>  $entries
     * @return array<int, array<string, mixed>>
     */
    public static function toArrayCollection(array $entries): array
    {
        return array_map(fn (self $entry): array => $entry->toArray(), $entries);
    }
}
