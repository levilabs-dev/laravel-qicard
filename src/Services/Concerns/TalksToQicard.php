<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LeviLabs\LaravelQicard\Exceptions\QicardException;
use LeviLabs\LaravelQicard\Exceptions\QicardTerminalException;

trait TalksToQicard
{
    /**
     * @return array{environment: string, terminal_id: string, username: string, password: string, base_url: string, public_key: ?string}
     */
    private function terminalConfig(string $terminal): array
    {
        $config = config("qicard.terminals.{$terminal}");

        if (! is_array($config)) {
            throw QicardTerminalException::unknownTerminal($terminal);
        }

        if (empty($config['terminal_id']) || empty($config['username']) || empty($config['password'])) {
            throw QicardTerminalException::missingCredentials($terminal);
        }

        return [
            'environment' => (string) ($config['environment'] ?? 'sandbox'),
            'terminal_id' => (string) $config['terminal_id'],
            'username' => (string) $config['username'],
            'password' => (string) $config['password'],
            'base_url' => rtrim((string) ($config['base_url'] ?? 'https://uat-sandbox-3ds-api.qi.iq/api/v1'), '/'),
            'public_key' => isset($config['public_key']) && $config['public_key'] !== '' ? (string) $config['public_key'] : null,
        ];
    }

    private function client(string $terminal): PendingRequest
    {
        $config = $this->terminalConfig($terminal);

        return Http::baseUrl($config['base_url'])
            ->withBasicAuth($config['username'], $config['password'])
            ->withHeaders(['X-Terminal-Id' => $config['terminal_id']])
            ->timeout((int) config('qicard.http.timeout', 15))
            ->retry(
                (int) config('qicard.http.retry_times', 1),
                (int) config('qicard.http.retry_sleep_ms', 200),
            )
            ->acceptJson()
            ->asJson();
    }

    /**
     * Sends a request and unwraps QiCard's response envelope. QiCard returns
     * the resource schema directly (Payment/Refund/…) with HTTP 200 on
     * success, or `{"error": {"code": int, "message": string}}` with HTTP
     * 400/500 on failure — unlike gateways that return 200 for logical
     * failures, so a plain `->successful()` check is sufficient here.
     *
     * @param  'get'|'post'  $method
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    private function send(string $terminal, string $action, string $method, string $path, ?array $payload = null): array
    {
        $request = $this->client($terminal);
        $response = $method === 'post' ? $request->post($path, $payload ?? []) : $request->get($path, $payload ?? []);

        if ($response->failed()) {
            $code = $response->json('error.code');
            $message = $response->json('error.message');

            throw QicardException::requestFailed(
                $action,
                is_int($code) ? $code : (is_numeric($code) ? (int) $code : null),
                is_string($message) ? $message : null,
            );
        }

        return (array) $response->json();
    }

    private function assertValidAmount(?float $amount): void
    {
        if ($amount !== null && $amount <= 0) {
            throw QicardException::invalidAmount($amount);
        }
    }

    /**
     * QiCard requires a unique, unused requestId per POST call, up to 36
     * characters — a UUID fits exactly.
     */
    private function resolveRequestId(?string $requestId): string
    {
        $requestId ??= (string) Str::uuid();

        if (strlen($requestId) > 36) {
            throw QicardException::requestIdTooLong($requestId);
        }

        return $requestId;
    }

    private function defaultTerminal(): string
    {
        return (string) config('qicard.default', 'default');
    }
}
