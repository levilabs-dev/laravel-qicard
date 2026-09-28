<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LeviLabs\LaravelQicard\Enums\PaymentStatus;

/**
 * @property string $terminal
 * @property string|null $request_id
 * @property string $payment_id
 * @property PaymentStatus $status
 * @property bool $canceled
 * @property float $amount
 * @property float|null $confirmed_amount
 * @property string $currency
 * @property string|null $payment_type
 * @property string|null $form_url
 * @property array<string, mixed>|null $details
 * @property CarbonImmutable|null $gateway_created_at
 * @property CarbonImmutable|null $checked_at
 * @property array<string, mixed>|null $meta
 */
class QicardPayment extends Model
{
    protected $table = 'qicard_payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'canceled' => 'boolean',
            'amount' => 'decimal:2',
            'confirmed_amount' => 'decimal:2',
            'details' => 'array',
            'gateway_created_at' => 'immutable_datetime',
            'checked_at' => 'immutable_datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<QicardRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(QicardRefund::class, 'payment_id');
    }
}
