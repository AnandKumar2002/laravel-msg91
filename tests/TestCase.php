<?php

declare(strict_types=1);

namespace Parvion\Msg91\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Parvion\Msg91\Facades\Msg91;
use Parvion\Msg91\Msg91ServiceProvider;

/**
 * Class TestCase
 *
 * Base test case for all parvion/laravel-msg91 tests.
 * Bootstraps the package via Orchestra Testbench.
 */
abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            Msg91ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Msg91' => Msg91::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('msg91.auth_key', 'test-auth-key');
        $app['config']->set('msg91.sender_id', 'TSTRDR');
        $app['config']->set('msg91.default_country_code', '91');
    }
}
