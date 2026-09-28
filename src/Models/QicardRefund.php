<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LeviLabs\LaravelQicard\Enums\RefundStatus;

/**
 * @property int $payment_id
 * @property string|null $refund_id
 * @property string|null $request_id
 * @property RefundStatus $status
 * @property bool $canceled
 * @property float $amount
 * @property string $currency
 * @property string|null $message
 * @property array<string, mixed>|null $details
 * @property CarbonImmutable|null $gateway_created_at
 */
class QicardRefund extends Model
{
    protected $table = 'qicard_refunds';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'canceled' => 'boolean',
            'amount' => 'decimal:2',
            'details' => 'array',
            'gateway_created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<QicardPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(QicardPayment::class, 'payment_id');
    }
}
