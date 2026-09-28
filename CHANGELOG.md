# Changelog

All notable changes to `levilabs/laravel-qicard` are documented here.

Project: **[Levi Labs](https://levilabs.dev)** · maintained by **Nizam Omer** ([nizaamomer.com](https://nizaamomer.com))

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Work on the default branch (`main`). **No `v1.0.0` tag has been published yet** — the first GitHub release / Packagist version will be **1.0.0** when you tag it.

Until then, install from source with:

`composer require levilabs/laravel-qicard:dev-main`

### Changed

- Package renamed to `levilabs/laravel-qicard` under [Levi Labs](https://github.com/levilabs-dev). PHP namespace is now `LeviLabs\LaravelQicard` (previously `Nizaamomer\LaravelQicard`).

### Added

- `QicardPayment::create()`, `status()`, `statusByRequest()`, `cancel()`, `refund()`, `verifyWebhookSignature()`.
- Typed DTOs for every QiCard response shape: `PaymentData`, `PaymentStatusData`, `PaymentCancelData`, `RefundData`, `PaymentDetails`, `CancelEntry`, plus request-side `CustomerInfo` and `BrowserInfo`.
- `PaymentStatus` (13 values) and `RefundStatus` (3 values) enums, matching QiCard's documented status lists exactly, with `isTerminal()`/`isSuccessful()` helpers.
- `WebhookVerifier` — RSA/SHA-256 signature verification for incoming webhook notifications, built field-for-field from the Payment Gateway API reference's "Signature verification in notifications" section.
- All 34 of QiCard's documented API error codes mapped to their string names in `QicardException::ERROR_CODES`.
- Multi-terminal support via the `terminals` array in `config/qicard.php`.
- Automatic persistence: every create/status/cancel/refund call fires an event that upserts into `qicard_payments`/`qicard_refunds`.
- `php artisan qicard:sync-statuses` — re-checks every payment still in a non-terminal status directly against QiCard.
- `payable` polymorphic relation on `QicardPayment` to link a payment to your own order/subscription models.
- `toArray()` on every response DTO — for storing the raw gateway response (e.g. in a `provider_response` JSON column).
- `QicardException::$errorName` and `$errorCode` — structured error name and numeric code for branching or localization.

### Fixed

- `WebhookVerifier` formatted `amount` to two decimal places when building the signed dataString. Live production webhook payloads use **three** decimal places (minor-unit precision), which caused every real webhook signature check to fail until amounts were formatted correctly.

### Security

- TLS certificate verification is always enabled and cannot be disabled through this SDK.
- Webhook payloads are never trusted without a successful `verifyWebhookSignature()` call first.
- QiCard's HTTP-status-per-outcome response shape (200 for success, 400/500 with an `{"error": {...}}` envelope for failure) is normalized into a single `QicardException` with a readable, code-mapped message.
- Amounts must be greater than zero before any request is sent.
- `requestId` is validated against QiCard's documented 36-character limit before any request is sent.

<!-- When you ship v1.0.0: rename [Unreleased] to [1.0.0] - YYYY-MM-DD and add a new empty [Unreleased] section. -->
