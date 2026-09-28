<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Enums;

/**
 * QiCard's Payment.status, exactly as documented in the Payment Gateway API
 * reference. Terminal statuses (isTerminal() === true) never change again;
 * everything else is a transient step the payment moves through en route to
 * one of them.
 */
enum PaymentStatus: string
{
    /**
     * A payment record has been created, a unique identifier assigned, and
     * the payment is awaiting further processing. The initial stage.
     */
    case Created = 'CREATED';

    /**
     * The payment form has been displayed and a payment method is expected
     * to be selected.
     */
    case FormShowed = 'FORM_SHOWED';

    /**
     * Requires a 3DS method call.
     */
    case ThreeDsMethodCallRequired = 'THREE_DS_METHOD_CALL_REQUIRED';

    /**
     * Requires a payer authentication procedure.
     */
    case AuthenticationRequired = 'AUTHENTICATION_REQUIRED';

    /**
     * The payer authentication procedure has started.
     */
    case AuthenticationStarted = 'AUTHENTICATION_STARTED';

    /**
     * The payer's authentication failed; processing completed unsuccessfully.
     * Terminal.
     */
    case AuthenticationFailed = 'AUTHENTICATION_FAILED';

    /**
     * The payer authentication procedure completed. Depending on the
     * authentication method this may or may not imply a successful result —
     * it only confirms the procedure itself finished.
     */
    case Authenticated = 'AUTHENTICATED';

    /**
     * The payment has been initialized.
     */
    case Initialized = 'INITIALIZED';

    /**
     * Start of financial transaction processing — the payment moves here
     * before being sent to (or received from) the settlement system.
     */
    case Started = 'STARTED';

    /**
     * The financial transaction completed successfully; the payment was
     * made. Terminal.
     */
    case Success = 'SUCCESS';

    /**
     * The financial transaction did not complete successfully; the payment
     * was rejected. Terminal.
     */
    case Failed = 'FAILED';

    /**
     * The payment ended with an error. Terminal.
     */
    case Error = 'ERROR';

    /**
     * The payment is overdue. Terminal.
     */
    case Expired = 'EXPIRED';

    /**
     * QiCard's five terminal statuses — once a payment reaches one of these
     * it will never change again, so it's safe to stop polling.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Success, self::Failed, self::AuthenticationFailed, self::Error, self::Expired => true,
            default => false,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Success;
    }
}
