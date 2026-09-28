<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Exceptions;

use RuntimeException;

class QicardTerminalException extends RuntimeException
{
    public static function unknownTerminal(string $terminal): self
    {
        return new self("QiCard terminal [{$terminal}] is not configured in config/qicard.php.");
    }

    public static function missingCredentials(string $terminal): self
    {
        return new self(
            "QiCard terminal [{$terminal}] is missing terminal_id, username, or password — "
            .'set QICARD_TERMINAL_ID, QICARD_USERNAME and QICARD_PASSWORD.'
        );
    }
}
