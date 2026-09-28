# Laravel QiCard SDK — Payment Gateway, Refunds & Signature-Verified Webhooks

<p>
<a href="https://packagist.org/packages/levilabs/laravel-qicard"><img src="https://img.shields.io/packagist/v/levilabs/laravel-qicard.svg?style=flat-square&label=Packagist&color=orange" alt="Latest Version on Packagist"></a>
<a href="https://github.com/levilabs-dev/laravel-qicard/actions"><img src="https://img.shields.io/github/actions/workflow/status/levilabs-dev/laravel-qicard/run-tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
<a href="https://packagist.org/packages/levilabs/laravel-qicard"><img src="https://img.shields.io/packagist/dt/levilabs/laravel-qicard.svg?style=flat-square&label=Downloads&color=blue" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/levilabs/laravel-qicard"><img src="https://img.shields.io/packagist/php-v/levilabs/laravel-qicard.svg?style=flat-square&label=PHP&color=777bb4" alt="PHP Version"></a>
<a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?style=flat-square" alt="Laravel Version"></a>
<a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/levilabs/laravel-qicard.svg?style=flat-square&color=success" alt="License"></a>
</p>

A modern Laravel SDK for the [QiCard Payment Gateway](https://developers-gate.qi.iq) (Iraq) — hosted card payments via the Secure Payment Form, status polling, cancellation, full/partial refunds, and RSA-signature-verified webhook notifications. Typed DTOs and enums for every documented response shape, multi-terminal support, and automatic status persistence.

Maintained by **[Levi Labs](https://levilabs.dev)** ([GitHub](https://github.com/levilabs-dev)) — built by [Nizam Omer](https://nizaamomer.com)

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Creating a payment](#creating-a-payment)
- [The webhook notification — always verify the signature](#the-webhook-notification--always-verify-the-signature)
- [Checking payment status](#checking-payment-status)
- [Missed notifications: the sync command](#missed-notifications-the-sync-command)
- [Cancelling a payment](#cancelling-a-payment)
- [Refunding a payment](#refunding-a-payment)
- [Mobile payments](#mobile-payments)
- [Multiple terminals](#multiple-terminals)
- [Automatic persistence](#automatic-persistence)
- [Error handling](#error-handling)
- [Full example](#full-example)
- [Security](#security)
- [Testing](#testing)
- [QiCard API Reference](#qicard-api-reference)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Author](#author)
- [License](#license)

## Requirements

- PHP 8.2+ (Laravel **13.x** requires PHP **8.3+** on the application)
- Laravel 11.x, 12.x, or 13.x

## Installation

Stable release (after `v1.0.0` is tagged on GitHub / Packagist):

```bash
composer require levilabs/laravel-qicard
```

While the first release is pending, use the `main` branch:

```bash
composer require levilabs/laravel-qicard:dev-main
```

Publish the config file:

```bash
php artisan vendor:publish --tag="qicard-config"
```

Run the migrations — creates `qicard_payments` and `qicard_refunds`; every payment, status check, cancellation, and refund is persisted automatically, no manual tracking code needed:

```bash
php artisan migrate
```

Add your QiCard Merchant Terminal credentials to `.env` (provided by your acquirer):

```env
QICARD_ENVIRONMENT=sandbox                     # label only — see "Base URL" note below
QICARD_TERMINAL_ID=237984                      # X-Terminal-Id, from your acquirer
QICARD_USERNAME=your-username                  # Basic Auth username
QICARD_PASSWORD=your-password                  # Basic Auth password — keep this out of version control
QICARD_BASE_URL=https://uat-sandbox-3ds-api.qi.iq/api/v1   # sandbox is public; production is issued per merchant, see below
QICARD_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"  # for webhook signature verification, literal \n for newlines
QICARD_CURRENCY=IQD
QICARD_FINISH_URL=https://your-app.test/checkout/finish     # where QiCard redirects the payer after processing completes
QICARD_NOTIFICATION_URL=https://your-app.test/qicard/webhook # where QiCard POSTs the webhook notification
```

> **Base URL:** the sandbox host (`uat-sandbox-3ds-api.qi.iq`) is public and safe to default to. QiCard does **not** publish a single production URL — it's issued to you directly by your acquirer alongside your terminal credentials. Set `QICARD_BASE_URL` explicitly once you have it; there is no environment-name-based fallback for production, on purpose, so a misconfigured `.env` fails loudly instead of silently hitting the wrong host.

<!--
### Multiple terminals

Add more entries under `terminals` in `config/qicard.php` to accept payments through multiple QiCard Merchant Terminals, then pass the terminal name as the last argument of any SDK call:

```php
QicardPayment::create($amount, terminal: 'second_terminal');
```

-->

## Creating a payment

Creates a Payment and returns a `formUrl` — redirect the payer there to complete checkout on QiCard's hosted Secure Payment Form.

```php
use LeviLabs\LaravelQicard\Facades\QicardPayment;
use LeviLabs\LaravelQicard\Data\CustomerInfo;

$payment = QicardPayment::create(
    amount: 256.89,
    customerInfo: new CustomerInfo(
        firstName: 'John',
        lastName: 'Doe',
        email: 'j.doe@gmail.com',
        phone: '009647xxxxxxxxx',
    ),
    additionalInfo: ['order_id' => (string) $order->id], // up to 10 string key/value pairs, echoed back on every later response
);

return redirect($payment->formUrl);
```

`currency`, `finishUrl`, and `notificationUrl` all fall back to `QICARD_CURRENCY`/`QICARD_FINISH_URL`/`QICARD_NOTIFICATION_URL` when omitted. `requestId` is auto-generated (a UUID, well within QiCard's 36-character limit) if you don't pass one — QiCard requires it to be unique per request.

`browserInfo` is optional but improves the 3DS authentication experience. If you include it, every one of its fields is required (QiCard rejects the request otherwise) — collect the real values client-side in JS where you can:

```php
use LeviLabs\LaravelQicard\Data\BrowserInfo;

QicardPayment::create(256.89, browserInfo: new BrowserInfo(
    browserAcceptHeader: $request->header('Accept'),
    browserIp: $request->ip(),
    browserJavaEnabled: false,
    browserLanguage: 'en-US',
    browserColorDepth: '24',      // screen.colorDepth
    browserScreenWidth: '1920',   // screen.width
    browserScreenHeight: '1080',  // screen.height
    browserTZ: '-180',            // Date().getTimezoneOffset()
    browserUserAgent: $request->userAgent(),
));
```

`BrowserInfo::fromRequest($request)` builds a best-effort fallback from server-side headers alone (used in the [full example](#full-example)) for flows that can't collect real screen/Java/timezone values.

## The webhook notification — always verify the signature

QiCard POSTs the full Payment object to `notificationUrl` once a payment reaches a **terminal** status (`SUCCESS`, `FAILED`, `AUTHENTICATION_FAILED`, `ERROR`, or `EXPIRED`), signing it with the terminal's private key so you can tell a genuine notification from a forged one. **Never trust the body until the signature checks out:**

```php
Route::post('/qicard/webhook', function (\Illuminate\Http\Request $request) {
    $signature = $request->header('X-Signature');

    if (! $signature || ! QicardPayment::verifyWebhookSignature($request->all(), $signature)) {
        return response()->json(['message' => 'Invalid signature'], 400);
    }

    // Re-fetch the authoritative status rather than reading it off the
    // payload — this also fires the event that persists to qicard_payments.
    $status = QicardPayment::status($request->input('paymentId'));

    if ($status->isSuccessful()) {
        // fulfil the order
    }

    return response()->noContent();
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
```

QiCard retries with backoff until your endpoint returns HTTP 200 — make sure it always does once the notification has been recorded, even for a rejected signature, or QiCard will keep retrying indefinitely.

`verifyWebhookSignature()` implements the algorithm exactly as documented in the "Signature verification in notifications" section of the Payment Gateway API reference: the five fields `paymentId|amount|currency|creationDate|status` are concatenated with `|` (a missing/null value becomes a literal `-`), the amount is formatted to exactly two decimal places, and the resulting string's SHA-256 hash is checked against the Base64-decoded `X-Signature` header using the terminal's RSA public key (`QICARD_PUBLIC_KEY`).

## Checking payment status

The authoritative source of truth for a payment's outcome — call this from the webhook handler above, from the page the payer lands on after `finishUrl`, or anywhere else you need to confirm a result:

```php
$status = QicardPayment::status($paymentId);

$status->status;           // PaymentStatus::Success | Failed | AuthenticationFailed | Expired | … (13 values total)
$status->status->isTerminal();    // bool — true once the payment will never change status again
$status->isSuccessful();   // bool — shorthand for status === PaymentStatus::Success
$status->amount;
$status->confirmedAmount;
$status->details?->maskedPan;     // e.g. "521372******8582"
$status->details?->paymentSystem; // "VISA" | "MASTER_CARD"
```

Lost the `paymentId` (e.g. the `create()` response never arrived)? Look it up by the `requestId` you generated instead:

```php
$status = QicardPayment::statusByRequest($requestId);
```

## Missed notifications: the sync command

A webhook is best-effort — if `notificationUrl` was misconfigured, your endpoint was briefly down, or delivery was simply dropped, a payment can sit in a non-terminal status forever with nothing else prompting a re-check. Run:

```bash
php artisan qicard:sync-statuses
```

to re-check every payment still in a non-terminal status (`CREATED`, `FORM_SHOWED`, `AUTHENTICATION_REQUIRED`, etc.) directly against QiCard. Schedule it in `bootstrap/app.php`:

```php
->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
    $schedule->command('qicard:sync-statuses')->everyFiveMinutes();
})
```

## Cancelling a payment

Only valid while a payment hasn't yet entered processing (`CREATED`/`FORM_SHOWED`), or once it's `SUCCESS` but still awaiting confirmation — QiCard rejects a cancel attempt outside those windows. Use [refund](#refunding-a-payment) for an already-settled payment instead.

```php
$cancellation = QicardPayment::cancel($paymentId); // omit $amount for a full cancel, or pass one for a partial cancel

$cancellation->canceled; // bool
```

## Refunding a payment

Full or partial refund to the payer. The sum of all refunds on a payment must never exceed its original amount. This moves real money — put your own approval workflow in front of it.

```php
$refund = QicardPayment::refund(
    paymentId: $paymentId,
    amount: 100.00,           // omit entirely for a full refund
    message: 'Requested by customer',
);

$refund->isSuccessful();  // bool
$refund->status;          // RefundStatus::Success | Failed | Processing
$refund->refundId;
```

## Mobile payments

Payments initiated from a mobile app must be created with `appChannel: true` so the gateway tailors the authentication flow for an in-app context — omitting this returns a `payment is not in app channel` error the moment your mobile SDK tries to use it:

```php
QicardPayment::create(256.89, appChannel: true);
```

Set `QICARD_APP_CHANNEL=true` in `.env` instead if every payment your app creates is a mobile payment, so you don't have to pass it on every call.

## Multiple terminals

Add more entries under `terminals` in `config/qicard.php` to accept payments through multiple QiCard Merchant Terminals — each with its own credentials, base URL, and public key — then pass the terminal name as the last argument of any SDK call:

```php
QicardPayment::create($amount, terminal: 'second_terminal');
QicardPayment::status($paymentId, terminal: 'second_terminal');
```

## Automatic persistence

Every `create()`, `status()`, `cancel()`, and `refund()` call fires an event (`PaymentCreated`, `PaymentStatusChecked`, `PaymentCancelled`, `PaymentRefunded`) that this package listens to and upserts into `qicard_payments`/`qicard_refunds` automatically — no manual tracking code required. Link your own models via the `payable` polymorphic relation:

```php
use LeviLabs\LaravelQicard\Models\QicardPayment;

QicardPayment::where('payment_id', $paymentId)->first()?->payable()->associate($order)->save();
```

## Error handling

QiCard returns HTTP 200 with the resource body on success, or HTTP 400/500 with `{"error": {"code": int, "message": string}}` on failure — this SDK normalizes both into a single `QicardException` with a readable message:

```php
use LeviLabs\LaravelQicard\Exceptions\QicardException;

try {
    QicardPayment::refund($paymentId);
} catch (QicardException $e) {
    // "QiCard payment refund request failed [REFUNDS_NOT_ALLOWED] (code 18): ..."
}
```

All 34 of QiCard's documented error codes (`QicardException::ERROR_CODES`) are mapped to their string names, so the exception message is readable even when the API response omits the string `message` and returns only a numeric `code` — which it does for some failures.

## Full example

[`docs/examples/PaymentController.php`](docs/examples/PaymentController.php) is a complete, heavily-commented controller covering every public method — creating a payment, the signature-verified webhook, the finishUrl return page, cancel, and refund. It's illustrative (not autoloaded), so copy what you need into your own app.

## Security

- **TLS verification is never disabled.** This SDK does not expose a way to set Guzzle's `verify => false`.
- **Webhook payloads are never trusted without a valid signature.** `verifyWebhookSignature()` implements the exact algorithm from QiCard's own "Signature verification in notifications" documentation — field order, the `-` placeholder for missing values, and three-decimal amount formatting (as used in live webhook payloads) — rather than assuming a prior integration's format was correct.
- **QiCard's per-outcome HTTP status is handled for you.** A 400/500 with an `{"error": {...}}` body is normalized into a thrown `QicardException`, with all 34 documented error codes mapped to readable names even when the response omits the string message.
- **Amounts must be greater than zero**, and `requestId` is checked against QiCard's 36-character limit, before any request is sent.
- **Credentials live in `.env`,** never in version control. Rotate `QICARD_PASSWORD` immediately if it's ever exposed.

If you discover a security issue, please see [SECURITY.md](SECURITY.md) instead of using the public issue tracker. Reports are handled by **Levi Labs** and **Nizam Omer** (see contact details in that file).

## Testing

```bash
composer test        # Pest
composer analyse      # Larastan / PHPStan (level 8)
composer format        # Laravel Pint
```

## QiCard API Reference

See the [QiCard Developer Documentation](https://developers-gate.qi.iq) for the underlying REST API this SDK wraps.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for what's changed in each release.

## Contributing

Bug reports and feature ideas are welcome via [GitHub Issues](https://github.com/levilabs-dev/laravel-qicard/issues). For contribution guidelines, see [CONTRIBUTING.md](CONTRIBUTING.md).

## Author

**[Levi Labs](https://levilabs.dev)** — software development studio · [GitHub](https://github.com/levilabs-dev) · [hello@levilabs.dev](mailto:hello@levilabs.dev)

Created and maintained by **Nizam Omer** — [nizaamomer.com](https://nizaamomer.com) · [nizaamomer@gmail.com](mailto:nizaamomer@gmail.com)

## License

This package is open-source software licensed under the [MIT License](LICENSE.md).

Copyright (c) 2026 **Levi Labs** and **Nizam Omer**.
