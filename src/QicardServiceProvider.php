<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard;

use Illuminate\Support\Facades\Event;
use LeviLabs\LaravelQicard\Console\Commands\SyncQicardStatuses;
use LeviLabs\LaravelQicard\Contracts\QicardPaymentServiceContract;
use LeviLabs\LaravelQicard\Events\PaymentCancelled;
use LeviLabs\LaravelQicard\Events\PaymentCreated;
use LeviLabs\LaravelQicard\Events\PaymentRefunded;
use LeviLabs\LaravelQicard\Events\PaymentStatusChecked;
use LeviLabs\LaravelQicard\Listeners\PersistQicardPayment;
use LeviLabs\LaravelQicard\Services\QicardPaymentService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QicardServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('qicard')
            ->hasConfigFile('qicard')
            ->hasMigrations('create_qicard_payments_table', 'create_qicard_refunds_table')
            ->runsMigrations()
            ->hasCommand(SyncQicardStatuses::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(QicardPaymentServiceContract::class, QicardPaymentService::class);
    }

    public function packageBooted(): void
    {
        Event::listen(PaymentCreated::class, [PersistQicardPayment::class, 'onCreated']);
        Event::listen(PaymentStatusChecked::class, [PersistQicardPayment::class, 'onStatusChecked']);
        Event::listen(PaymentCancelled::class, [PersistQicardPayment::class, 'onCancelled']);
        Event::listen(PaymentRefunded::class, [PersistQicardPayment::class, 'onRefunded']);
    }
}
