<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Data;

use Illuminate\Http\Request;

/**
 * The payer's browser fingerprint, required by 3DS. Optional to include on
 * payment creation overall, but if you include it at all, every property
 * here is mandatory — QiCard returns a "bad request" if any is missing.
 */
final readonly class BrowserInfo
{
    public function __construct(
        public string $browserAcceptHeader,
        public string $browserIp,
        public bool $browserJavaEnabled,
        public string $browserLanguage,
        /** One of "1", "4", "8", "15", "16", "24", "32", "48" — from `screen.colorDepth`. */
        public string $browserColorDepth,
        /** From `screen.width`. */
        public string $browserScreenWidth,
        /** From `screen.height`. */
        public string $browserScreenHeight,
        /** Minutes between UTC and the browser's local time, e.g. "-180" for UTC+3. From `Date().getTimezoneOffset()`. */
        public string $browserTZ,
        public string $browserUserAgent,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'browserAcceptHeader' => $this->browserAcceptHeader,
            'browserIp' => $this->browserIp,
            'browserJavaEnabled' => $this->browserJavaEnabled,
            'browserLanguage' => $this->browserLanguage,
            'browserColorDepth' => $this->browserColorDepth,
            'browserScreenWidth' => $this->browserScreenWidth,
            'browserScreenHeight' => $this->browserScreenHeight,
            'browserTZ' => $this->browserTZ,
            'browserUserAgent' => $this->browserUserAgent,
        ];
    }

    /**
     * Builds a BrowserInfo from the incoming request — accurate for
     * browserAcceptHeader/browserIp/browserUserAgent, but the screen/Java/TZ
     * fields can only come from client-side JS, so this is a best-effort
     * fallback (0 color depth/screen size, javaEnabled false, TZ "0")
     * meant for server-rendered flows that can't collect the real values.
     * Prefer sending the real values from your checkout page's JS when possible.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            browserAcceptHeader: (string) $request->header('Accept', '*/*'),
            browserIp: (string) $request->ip(),
            browserJavaEnabled: false,
            browserLanguage: (string) $request->header('Accept-Language', 'en-US'),
            browserColorDepth: '24',
            browserScreenWidth: '1920',
            browserScreenHeight: '1080',
            browserTZ: '0',
            browserUserAgent: (string) $request->userAgent(),
        );
    }
}
