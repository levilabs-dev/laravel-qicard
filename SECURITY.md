# Security Policy

This package handles QiCard Merchant Terminal credentials (`terminal_id`, `username`, `password`, and the webhook `public_key`) and processes real financial transactions (payments, cancellations, and refunds). Please report security issues responsibly, as described below.

## Supported Versions

This package follows semantic versioning. Security fixes are applied to the latest tagged release and to `main`; older major versions are not backported.

| Version           | Supported |
| ----------------- | --------- |
| 1.x (and `main`)   | ✅        |
| < 1.0              | ❌        |

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security vulnerabilities.**

The preferred way to report a vulnerability is through GitHub's private vulnerability reporting:

1. Go to the [Security tab](https://github.com/levilabs-dev/laravel-qicard/security)
2. Click **"Report a vulnerability"**
3. Describe the issue, including steps to reproduce and potential impact

Alternatively, you can reach out privately via [hello@levilabs.dev](mailto:hello@levilabs.dev) or [nizaamomer.com](https://nizaamomer.com).

### What to expect

- **Acknowledgement**: within 48 hours of your report
- **Initial assessment**: within 5 business days, including whether the report is accepted and its severity
- **Updates**: you'll be kept informed of progress until the issue is resolved
- **Disclosure**: once a fix is released, we'll credit you in the release notes/changelog, unless you'd prefer to remain anonymous

### Scope

In scope:
- The SDK code in this repository (`src/`) — request construction, response handling, credential handling, webhook signature verification, event/listener persistence
- Anything that could lead to credential exposure, incorrect payment/refund amounts, or trusting an unverified webhook payload

Out of scope:
- Vulnerabilities in QiCard's own API/infrastructure (report those directly to QiCard)
- Issues that require an attacker to already have your `.env`/terminal credentials or the terminal's public key material

Thank you for helping keep this package and its users secure.
