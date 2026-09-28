<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Enums;

/**
 * QiCard's Refund.status, as documented in the Payment Gateway API reference.
 */
enum RefundStatus: string
{
    case Success = 'SUCCESS';
    case Failed = 'FAILED';
    case Processing = 'PROCESSING';

    public function isSuccessful(): bool
    {
        return $this === self::Success;
    }
}
