<?php

namespace Saman9074\IranianValidationSuite\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Saman9074\IranianValidationSuite\IranianValidationSuiteServiceProvider;
use Saman9074\IranianValidationSuite\Facades\IranianKyc; // Import Facade
use Saman9074\IranianValidationSuite\Facades\IranianValidator; // Import Facade

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // You can perform additional setup here if needed, like running migrations
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        // $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        // Register our package's service provider for the test environment
        return [
            IranianValidationSuiteServiceProvider::class,
        ];
    }

    /**
     * Override application aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app): array
    {
        return [
            'IranianValidator' => IranianValidator::class,
            'IranianKyc' => IranianKyc::class,
        ];
    }


    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Set the default locale for the testing environment
        $app['config']->set('app.locale', 'en');

        // Set up the necessary configuration for the u-id driver for shahkar service for KYC tests
        $app['config']->set('iranian-validation-suite.kyc.default_driver', 'uid');
        $app['config']->set('iranian-validation-suite.kyc.services.shahkar.default_driver', 'uid');
        $app['config']->set('iranian-validation-suite.kyc.drivers.uid.business_id', 'test_business_id');
        $app['config']->set('iranian-validation-suite.kyc.drivers.uid.business_token', 'test_business_token');
    }
}
