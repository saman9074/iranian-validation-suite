<?php

namespace Saman9074\IranianValidationSuite\Services\Kyc;

use Illuminate\Support\Manager;
// use Illuminate\Contracts\Foundation\Application; // Already available via $this->app from parent constructor
use Saman9074\IranianValidationSuite\Exceptions\KycException;
use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\UIdShahkarDriver;
use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\FinnotechShahkarDriver;

class KycManager extends Manager
{
    /**
     * Get the default driver name (global default).
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getDefaultDriver(): string
    {
        // Use $this->app['config'] to access configuration
        $defaultDriver = $this->app['config']->get('iranian-validation-suite.kyc.default_driver');

        if (is_null($defaultDriver)) {
            throw new \InvalidArgumentException("Default KYC driver (kyc.default_driver) not specified in configuration.");
        }
        return $defaultDriver;
    }

    /**
     * Get a specific KYC service driver instance.
     *
     * @param string $serviceName The name of the KYC service (e.g., 'shahkar', 'liveness').
     * @return mixed The driver instance.
     * @throws \InvalidArgumentException If the resolved driver name is invalid.
     */
    public function service(string $serviceName)
    {
        $serviceDriverConfigKey = "iranian-validation-suite.kyc.services.{$serviceName}.default_driver";
        // Use $this->app['config'] here as well
        $driverName = $this->app['config']->get($serviceDriverConfigKey, $this->getDefaultDriver());

        if (is_null($driverName)) {
            throw new \InvalidArgumentException("No driver specified for KYC service '{$serviceName}' and no global default KYC driver found.");
        }
        // The driver() method from the parent Manager class will handle creating/returning the driver instance.
        return $this->driver($driverName);
    }

    /**
     * Create an instance of the u-id driver.
     * This method name must match the driver key in config: 'uid' -> createUidDriver
     *
     * @return \Saman9074\IranianValidationSuite\Services\Kyc\Drivers\UIdShahkarDriver
     * @throws KycException
     */
    protected function createUidDriver(): UIdShahkarDriver
    {
        // Use $this->app['config']
        $config = $this->app['config']->get('iranian-validation-suite.kyc.drivers.uid');

        if (is_null($config)) {
            throw new KycException("Configuration for u-id KYC driver (drivers.uid) not found.");
        }
        if (empty($config['business_id']) || empty($config['business_token'])) {
             throw new KycException("u-id Business ID or Token is missing in the configuration for 'uid' driver.");
        }
        return (new UIdShahkarDriver())->setConfig($config);
    }

    /**
     * Create an instance of the Finnotech Shahkar driver.
     * This method name must match the driver key in config: 'finnotech' -> createFinnotechDriver
     *
     * @return \Saman9074\IranianValidationSuite\Services\Kyc\Drivers\FinnotechShahkarDriver
     * @throws KycException
     */
    protected function createFinnotechDriver(): FinnotechShahkarDriver
    {
        $config = $this->app['config']->get('iranian-validation-suite.kyc.drivers.finnotech');

        if (is_null($config)) {
            throw new KycException("Configuration for Finnotech KYC driver (drivers.finnotech) not found.");
        }
        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['token_nid'])) {
             throw new KycException("Finnotech Client ID, Client Secret, or Token NID is missing in the configuration for 'finnotech' driver.");
        }
        return (new FinnotechShahkarDriver())->setConfig($config);
    }


    /**
     * Create an instance of the Farashenasa driver (example).
     * This method name must match the driver key in config: 'farashenasa' -> createFarashenasaDriver
     *
     * @return mixed
     * @throws KycException
     */
    protected function createFarashenasaDriver() // Driver name: 'farashenasa'
    {
        // Use $this->app['config']
        $config = $this->app['config']->get('iranian-validation-suite.kyc.drivers.farashenasa');

        if (is_null($config)) {
            throw new KycException("Configuration for Farashenasa KYC driver (drivers.farashenasa) not found.");
        }
        if (empty($config['api_key'])) { // Assuming Farashenasa uses an 'api_key'
             throw new KycException("Farashenasa API Key is missing in the configuration for 'farashenasa' driver.");
        }
        throw new KycException("FarashenasaDriver is not yet implemented.");
    }

    // The __call method is inherited from Illuminate\Support\Manager
    // and will correctly proxy calls to the default driver instance.
}
