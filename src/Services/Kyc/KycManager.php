<?php

// File: src/Services/Kyc/KycManager.php
namespace Saman9074\IranianValidationSuite\Services\Kyc;

use Illuminate\Support\Manager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpClientFactory; // Import the actual Factory class
use Saman9074\IranianValidationSuite\Contracts\Kyc\LivenessServiceInterface;
use Saman9074\IranianValidationSuite\Contracts\Kyc\ShahkarServiceInterface;
use Saman9074\IranianValidationSuite\Contracts\Kyc\IdentityServiceInterface;
use Saman9074\IranianValidationSuite\Exceptions\Kyc\KycConfigurationException;

// Import actual driver classes - ensure these paths and classes exist or are created
use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\FarashenasaLivenessDriver;
use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\UIdShahkarDriver;
use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\FinnotechShahkarDriver;
// Example for Identity driver, you'll need to create this if it doesn't exist
// use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\UIdIdentityDriver;


class KycManager extends Manager
{
    /**
     * The key for accessing KYC configuration.
     * Matches the key in your main config file (iranian-validation-suite.php).
     * @var string
     */
    protected string $configBaseKey = 'iranian-validation-suite.kyc';

    /**
     * KycManager constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    /**
     * Get the configuration for a specific provider.
     * The provider name is the key used in the 'drivers' array in your config.
     *
     * @param string $providerName e.g., 'uid', 'finnotech', 'farashenasa'
     * @return array
     * @throws KycConfigurationException
     */
    protected function getProviderConfig(string $providerName): array
    {
        $configPath = "{$this->configBaseKey}.drivers.{$providerName}";
        $config = $this->container['config']->get($configPath);

        if (is_null($config)) {
            throw new KycConfigurationException("Configuration for KYC provider '{$providerName}' not found under '{$configPath}'.");
        }
        // Add provider_name to the config array for the driver's use
        $config['provider_name'] = $providerName;
        return $config;
    }

    /**
     * Get the default driver name for the KYC manager.
     * This refers to the 'default_driver' under the 'kyc' key in your config.
     *
     * @return string
     * @throws KycConfigurationException
     */
    public function getDefaultDriver()
    {
        $defaultDriver = $this->container['config']->get("{$this->configBaseKey}.default_driver");
        if (!$defaultDriver) {
            throw new KycConfigurationException("Default KYC driver ('{$this->configBaseKey}.default_driver') is not specified in the configuration.");
        }
        return $defaultDriver; // This is the provider name, e.g., 'finnotech'
    }

    /**
     * Get the default driver name for a specific service type (e.g., 'shahkar', 'liveness').
     *
     * @param string $serviceType e.g., 'shahkar', 'liveness'
     * @return string The name of the provider configured as default for this service.
     * @throws KycConfigurationException
     */
    protected function getDefaultDriverForService(string $serviceType): string
    {
        $serviceConfigPath = "{$this->configBaseKey}.services.{$serviceType}.default_driver";
        $defaultDriverForService = $this->container['config']->get($serviceConfigPath);

        if (!$defaultDriverForService) {
            // Fallback to the overall default KYC driver if service-specific default is not set
            $defaultDriverForService = $this->getDefaultDriver();
            // No need to re-throw here as getDefaultDriver already throws if not set
        }
        return $defaultDriverForService; // This is the provider name, e.g., 'finnotech'
    }

    /**
     * Get a driver instance for a specific KYC service type (e.g., 'shahkar', 'liveness').
     * This resolves the default provider configured for that service type.
     *
     * @param string $serviceType The type of KYC service (e.g., 'shahkar', 'liveness').
     * @return mixed The driver instance implementing the corresponding service interface.
     * @throws KycConfigurationException
     */
    public function service(string $serviceType)
    {
        $providerName = $this->getDefaultDriverForService($serviceType);
        // The `driver()` method of the parent Manager class will call `create[ProviderName]Driver()`.
        return $this->driver($providerName);
    }

    /**
     * Create an instance of the uID Shahkar driver.
     * Method name must be 'create' + StudlyCase(provider_key_in_config) + 'Driver'.
     * Provider key in config: 'uid'
     *
     * @return ShahkarServiceInterface
     * @throws KycConfigurationException
     */
    protected function createUidDriver(): ShahkarServiceInterface // Or a more general interface if uid provides more
    {
        $config = $this->getProviderConfig('uid');
        // Correctly resolve the Http Client Factory instance
        return new UIdShahkarDriver($config, $this->container->make(HttpClientFactory::class));
    }

    /**
     * Create an instance of the Finnotech Shahkar driver.
     * Provider key in config: 'finnotech'
     *
     * @return ShahkarServiceInterface
     * @throws KycConfigurationException
     */
    protected function createFinnotechDriver(): ShahkarServiceInterface // Or a more general interface
    {
        $config = $this->getProviderConfig('finnotech');
        // Correctly resolve the Http Client Factory instance
        return new FinnotechShahkarDriver($config, $this->container->make(HttpClientFactory::class));
    }

    /**
     * Create an instance of the Farashenasa Liveness driver.
     * Provider key in config: 'farashenasa'
     *
     * @return LivenessServiceInterface
     * @throws KycConfigurationException
     */
    protected function createFarashenasaDriver(): LivenessServiceInterface
    {
        $config = $this->getProviderConfig('farashenasa');
        // Correctly resolve the Http Client Factory instance
        return new FarashenasaLivenessDriver($config, $this->container->make(HttpClientFactory::class));
    }

    // Add create[DriverName]Driver methods for other providers listed in your config (e.g., for identity_check)
    // Example for a hypothetical UIdIdentityDriver:
    // protected function createUidIdentityDriver(): IdentityServiceInterface
    // {
    //     // Note: This assumes 'uid' driver can also provide identity services.
    //     // If 'uid' is only for Shahkar, you'd need a different provider key for identity or a multi-service UidDriver.
    //     $config = $this->getProviderConfig('uid'); // Or a different config key if 'uid_identity'
    //     return new UIdIdentityDriver($config, $this->container->make(HttpClientFactory::class));
    // }


    /**
     * Dynamically call the default driver instance.
     * This is less useful for a multi-service manager like this one.
     * It's better to use `service('service_type')->method()` or `driver('provider_name')->method()`.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Calling on the overall default driver.
        return $this->driver()->$method(...$parameters);
    }
}
?>
