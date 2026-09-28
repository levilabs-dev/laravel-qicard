<?php

declare(strict_types=1);

namespace LeviLabs\LaravelQicard\Tests;

use LeviLabs\LaravelQicard\QicardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * A throwaway 2048-bit RSA test keypair, used only to sign/verify
     * webhook fixtures in WebhookVerifierTest. Never used against a real
     * QiCard terminal.
     */
    public const TEST_PUBLIC_KEY = <<<'PEM'
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA41H1srp2oClr18nGfcbO
        5iowc2q0SiFqWCfJZYm/n1rLU5q35457VD/DCH8AwvRs1t6EV1g3mLty1ILw0CFp
        Ugmuhk8nMsOaroOpKynW7xyPk0+3+mLG/LGvAwK3m9GiY0fcFA+SuC/iPcSBSXFQ
        vGr16Fp5vB98AkFTLyyXtcJgrAPzbW9R/9EyuW040HuXj86yXXpKNqs88P9ZX0uZ
        uSQnW6ZneFqhm2k+Auy/IAo6cRh0VzI3qIYbmdsZqnpv9IZNHIWL+77NwJhzs++B
        jejItyX5fpyt2pQNfUeM6Shvk27bSB6HaJOO6WrkFCNe48IrTuPZsYrU3ZbIrpWc
        twIDAQAB
        -----END PUBLIC KEY-----
        PEM;

    protected function getPackageProviders($app): array
    {
        return [
            QicardServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('qicard.terminals.default', [
            'environment' => 'sandbox',
            'terminal_id' => '237984',
            'username' => 'paymentgatewaytest',
            'password' => 'WHaNFE5C3qlChqNbAzH4',
            'base_url' => 'https://uat-sandbox-3ds-api.qi.iq/api/v1',
            'public_key' => self::TEST_PUBLIC_KEY,
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.default', 'array');
    }
}
