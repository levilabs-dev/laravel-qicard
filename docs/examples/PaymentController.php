<?php

declare(strict_types=1);

/**
 * Full reference example for levilabs/laravel-qicard.
 *
 * This is illustrative, not part of the package's autoload — copy what you
 * need into your own app. It assumes an App\Models\Order with a `total`
 * column.
 *
 * Covers every public method: create, the finishUrl return page, the
 * signature-verified webhook notification, explicit status polling, cancel,
 * and refund.
 */

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use LeviLabs\LaravelQicard\Data\BrowserInfo;
use LeviLabs\LaravelQicard\Data\CustomerInfo;
use LeviLabs\LaravelQicard\Facades\QicardPayment;
use LeviLabs\LaravelQicard\Models\QicardPayment as QicardPaymentModel;

class PaymentController extends Controller
{
    /**
     * Start a QiCard payment for an order and redirect the customer to the
     * hosted Secure Payment Form.
     */
    public function pay(Order $order)
    {
        $payment = QicardPayment::create(
            amount: (float) $order->total,
            customerInfo: new CustomerInfo(
                firstName: $order->customer_first_name,
                lastName: $order->customer_last_name,
                email: $order->customer_email,
                phone: $order->customer_phone,
            ),
            // browserInfo is optional but improves the 3DS experience —
            // collect the real values client-side where possible instead of
            // relying on BrowserInfo::fromRequest()'s server-side fallback.
            browserInfo: BrowserInfo::fromRequest(request()),
            additionalInfo: ['order_id' => (string) $order->id],
        );

        $order->qicardPayment()->associate(
            QicardPaymentModel::where('payment_id', $payment->paymentId)->first()
        )->save();

        return redirect($payment->formUrl);
    }

    /**
     * QiCard's webhook notification — POSTed to notificationUrl once the
     * payment reaches a terminal status (SUCCESS, FAILED,
     * AUTHENTICATION_FAILED, ERROR, or EXPIRED). Register this URL on your
     * Merchant Terminal (or pass notificationUrl explicitly to create()).
     *
     * Always verify the signature before trusting the body — QiCard signs
     * every notification with the terminal's private key specifically so
     * you can tell a real notification from a forged one.
     *
     * QiCard retries with backoff until it gets an HTTP 200 back, so make
     * sure this always returns one once the notification has been recorded
     * — even if you choose to ignore an invalid signature rather than
     * erroring, don't leave QiCard retrying forever.
     */
    public function webhook(Request $request)
    {
        $signature = $request->header('X-Signature');

        if (! $signature || ! QicardPayment::verifyWebhookSignature($request->all(), $signature)) {
            report(new \RuntimeException('QiCard webhook signature verification failed.'));

            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // The body is now trusted, but still re-fetch the authoritative
        // status rather than reading it off the payload — belt and braces,
        // and it's what populates qicard_payments via the PaymentStatusChecked
        // event.
        $status = QicardPayment::status($request->input('paymentId'));

        if ($status->isSuccessful()) {
            Order::where('id', $status->additionalInfo['order_id'] ?? null)->update(['status' => 'paid']);
        }

        return response()->noContent();
    }

    /**
     * The page the customer lands on after being redirected back from
     * QiCard (finishUrl). The webhook might not have arrived yet, so
     * re-check status here too rather than assuming success from the
     * redirect alone — QiCard sends the payer here on every terminal
     * outcome, not just success.
     */
    public function returned(Request $request, Order $order)
    {
        $status = QicardPayment::status($request->input('paymentId'));

        if ($status->isSuccessful()) {
            $order->update(['status' => 'paid']);

            return redirect()->route('orders.show', $order)->with('status', 'Payment successful.');
        }

        return redirect()->route('orders.show', $order)->with('status', 'Payment was not completed.');
    }

    /**
     * Cancel a payment before it settles. Only valid while the payment
     * hasn't yet entered processing (CREATED/FORM_SHOWED), or once it's
     * SUCCESS but still awaiting confirmation — QiCard rejects it otherwise
     * (REFUNDS_NOT_ALLOWED-style error), so use refund() instead for an
     * already-settled payment.
     */
    public function cancel(Order $order)
    {
        $payment = QicardPaymentModel::where('payable_id', $order->id)->where('payable_type', Order::class)->firstOrFail();

        $cancellation = QicardPayment::cancel($payment->payment_id);

        $order->update(['status' => 'cancelled']);

        return response()->json(['canceled' => $cancellation->canceled]);
    }

    /**
     * Full or partial refund of a settled payment. This moves real money —
     * put your own approval workflow in front of this.
     */
    public function refund(Order $order, Request $request)
    {
        $payment = QicardPaymentModel::where('payable_id', $order->id)->where('payable_type', Order::class)->firstOrFail();

        $refund = QicardPayment::refund(
            paymentId: $payment->payment_id,
            amount: $request->float('amount') ?: null, // omit entirely for a full refund
            message: $request->string('reason')->toString() ?: null,
        );

        if ($refund->isSuccessful()) {
            $order->update(['status' => 'refunded']);
        }

        return response()->json([
            'refund_id' => $refund->refundId,
            'status' => $refund->status->value,
        ]);
    }
}
