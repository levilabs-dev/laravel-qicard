<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Exceptions;

use InvalidArgumentException;
use RuntimeException;

class QicardException extends RuntimeException
{
    /**
     * QiCard's documented error codes (Payment Gateway API reference,
     * "Error Codes" section). The API returns both a numeric `code` and a
     * matching string `message` in its `{"error": {...}}` envelope; this
     * map lets a bare numeric code (some failure responses omit the string
     * message entirely) still resolve to a readable name.
     *
     * @var array<int, string>
     */
    public const ERROR_CODES = [
        1 => 'ORDER_ALREADY_EXISTS',
        2 => 'ORDER_NOT_FOUND',
        3 => 'ORDER_ALREADY_CANCELLED',
        4 => 'NO_COMPATIBLE_SERVICES_FOUND',
        5 => 'CAN_NOT_PROCESS_REQUEST',
        6 => 'REQUISITES_NOT_FOUND',
        7 => 'REQUISITES_ALREADY_EXISTS',
        8 => 'CAN_NOT_CREATE_NEW_REQUISITES',
        9 => 'TERMINAL_NOT_FOUND_EXCEPTION',
        10 => 'PAYMENT_ALREADY_EXISTS',
        11 => 'MAX_NUMBER_OF_PAYMENTS_FOR_ORDER_EXCEEDED',
        12 => 'PAYMENT_NOT_FOUND',
        13 => 'UNKNOWN_STRATEGY',
        14 => 'PROCESSING_IMPOSSIBLE',
        15 => 'CAN_NOT_CANCEL_PAYMENT',
        16 => 'CAN_NOT_CONFIRM_PAYMENT',
        17 => 'CAN_NOT_FINISH_AUTHENTICATION',
        18 => 'REFUNDS_NOT_ALLOWED',
        19 => 'PAYMENT_PARAMS_NOT_FOUND',
        20 => 'REFUND_ERROR',
        21 => 'VALIDATION_ERROR',
        22 => 'INCORRECT_PAYMENT_STATE',
        23 => 'INTERNAL_SYSTEM_ERROR',
        24 => 'EXTERNAL_SYSTEM_ERROR',
        26 => 'INVALID_PAYMENT_FORM_DOMAIN',
        27 => 'BAD_CREDENTIALS',
        28 => 'LIMIT_VIOLATION',
        29 => 'TRANSFER_NOT_FOUND',
        30 => 'INCORRECT_TRANSFER_STATE',
        31 => 'TOKEN_NOT_FOUND',
        32 => 'TOKEN_PROCESS_NOT_ALLOWED',
        33 => 'CAN_NOT_CANCEL_TRANSFER',
        34 => 'TRANSFER_ALREADY_EXISTS',
        35 => 'INVALID_TOKEN_TYPE',
    ];

    /**
     * The resolved error name (e.g. "REFUNDS_NOT_ALLOWED"), when this
     * exception came from requestFailed() — null for validation/config
     * exceptions raised locally before any request was sent.
     */
    public readonly ?string $errorName;

    /**
     * QiCard's numeric error code, when available. May be null even when
     * $errorName is set — some failure responses return a code the API
     * itself doesn't document (see requestFailed()'s UNKNOWN_ERROR_CODE_*
     * fallback), or omit a code entirely.
     */
    public readonly ?int $errorCode;

    public function __construct(string $message, ?string $errorName = null, ?int $errorCode = null)
    {
        parent::__construct($message);

        $this->errorName = $errorName;
        $this->errorCode = $errorCode;
    }

    public static function requestFailed(string $action, ?int $code, ?string $message): self
    {
        $name = $code !== null ? (self::ERROR_CODES[$code] ?? "UNKNOWN_ERROR_CODE_{$code}") : ($message ?? 'UNKNOWN_ERROR');
        $detail = $message ?? $name;

        return new self("QiCard {$action} request failed [{$name}] (code {$code}): {$detail}", $name, $code);
    }

    public static function invalidAmount(float $amount): InvalidArgumentException
    {
        return new InvalidArgumentException("Amount must be greater than zero, got {$amount}.");
    }

    public static function requestIdTooLong(string $requestId): InvalidArgumentException
    {
        return new InvalidArgumentException(
            "Request ID [{$requestId}] exceeds QiCard's 36-character limit for requestId."
        );
    }

    public static function missingPublicKey(string $terminal): self
    {
        return new self(
            "QiCard terminal [{$terminal}] has no public_key configured — set QICARD_PUBLIC_KEY "
            .'to verify webhook signatures. Obtain it from QiCard alongside your terminal credentials.'
        );
    }
}
