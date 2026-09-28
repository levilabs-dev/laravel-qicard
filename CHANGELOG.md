# Changelog

All notable changes to `levilabs/laravel-qicard` are documented here.

Project: **[Levi Labs](https://levilabs.dev)** · maintained by **Nizam Omer** ([nizaamomer.com](https://nizaamomer.com))

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Package renamed to `levilabs/laravel-qicard` under [Levi Labs](https://github.com/levilabs-dev). PHP namespace is now `LeviLabs\LaravelQicard` (previously `LeviLabs\LaravelQicard`).

### Added

- `toArray()` on every response DTO (`PaymentData`, `PaymentStatusData`, `PaymentCancelData`, `RefundData`, `CancelEntry`) — for storing the raw gateway response (e.g. in a `provider_response` JSON column) without hand-unpacking each property. Mirrors `PaymentDetails::toArray()`, which already existed.
- `QicardException::$errorName` and `$errorCode` — the resolved error name (`"REFUNDS_NOT_ALLOWED"`) and numeric code are now readable as structured properties, not just embedded in the exception message string, so calling code can branch or localize on them directly instead of parsing the message.

### Fixed

- `WebhookVerifier` formatted `amount` to two decimal places when building the signed dataString. Verified against live production webhook payloads, QiCard actually signs the amount to **three** decimal places (minor-unit precision) — the two-decimal format made every real webhook signature check fail with no way to recover short of manually re-syncing each payment. Payments still completed on QiCard's side, but the local app never found out until something else (a manual status check) polled for it.

## [1.0.0] - 2026-08-07

### Added

- `QicardPayment::create()`, `status()`, `statusByRequest()`, `cancel()`, `refund()`, `verifyWebhookSignature()`.
- Typed DTOs for every QiCard response shape: `PaymentData`, `PaymentStatusData`, `PaymentCancelData`, `RefundData`, `PaymentDetails`, `CancelEntry`, plus request-side `CustomerInfo` and `BrowserInfo`.
- `PaymentStatus` (13 values) and `RefundStatus` (3 values) enums, matching QiCard's documented status lists exactly, with `isTerminal()`/`isSuccessful()` helpers.
- `WebhookVerifier` — RSA/SHA-256 signature verification for incoming webhook notifications, built field-for-field from the Payment Gateway API reference's "Signature verification in notifications" section (not from any third-party package's re-implementation of it).
- All 34 of QiCard's documented API error codes mapped to their string names in `QicardException::ERROR_CODES`, so a thrown exception is readable even when the API response omits the string `message` and only returns a numeric `code`.
- Multi-terminal support via the `terminals` array in `config/qicard.php`.
- Automatic persistence: every create/status/cancel/refund call fires an event that upserts into `qicard_payments`/`qicard_refunds` — no manual tracking code needed.
- `php artisan qicard:sync-statuses` — re-checks every payment still in a non-terminal status directly against QiCard, since a webhook notification can fail to arrive (endpoint down, misconfigured URL, dropped delivery) with nothing else forcing a retry on your side.
- `payable` polymorphic relation on `QicardPayment` to link a payment to your own order/subscription models.

### Security

- TLS certificate verification is always enabled and cannot be disabled through this SDK.
- Webhook payloads are never trusted without a successful `verifyWebhookSignature()` call first — the signature algorithm is implemented directly from QiCard's own documented field list, order, separator, and amount format, not carried over from a prior integration's assumptions about the format.
- QiCard's HTTP-status-per-outcome response shape (200 for success, 400/500 with an `{"error": {...}}` envelope for failure) is normalized into a single `QicardException` with a readable, code-mapped message.
- Amounts must be greater than zero before any request is sent.
- `requestId` is validated against QiCard's documented 36-character limit before any request is sent.

[1.0.0]: https://github.com/levilabs-dev/laravel-qicard/releases/tag/v1.0.0
