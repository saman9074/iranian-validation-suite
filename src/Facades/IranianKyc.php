<?php

// File: src/Facades/IranianKyc.php
// Note: The alias in composer.json is "IranianKyc", so the class name should match if you want direct use.
// However, Laravel's Facade system allows the class name to be different from the alias.
// Let's name it IranianKycFacade for clarity within the package and use the alias "IranianKyc".

namespace Saman9074\IranianValidationSuite\Facades;

use Illuminate\Support\Facades\Facade;
use Saman9074\IranianValidationSuite\Contracts\Kyc\LivenessServiceInterface;
use Saman9074\IranianValidationSuite\Contracts\Kyc\ShahkarServiceInterface;
use Saman9074\IranianValidationSuite\Contracts\Kyc\IdentityServiceInterface;
use Saman9074\IranianValidationSuite\Contracts\Kyc\KycDriverResponseInterface;

/**
 * @see \Saman9074\IranianValidationSuite\Services\Kyc\KycManager
 *
 * @method static LivenessServiceInterface service(string $serviceType)
 * @method static mixed driver(string $driverName = null)
 *
 * @method static KycDriverResponseInterface initiateLivenessCheck(array $options = [])
 * @method static KycDriverResponseInterface verifyLiveness($livenessData, array $options = [])
 * @method static KycDriverResponseInterface getLivenessResult(string $transactionId, array $options = [])
 * @method static KycDriverResponseInterface matchNationalIdAndMobile(string $nationalId, string $mobileNumber, array $options = [])
 * @method static KycDriverResponseInterface getIdentityInfo(string $nationalId, array $options = [])
 * (Add more specific methods if you want to call them directly on the facade, assuming they are routed to the correct service's default driver)
 */
class IranianKyc extends Facade
{
    /**
     * Get the registered name of the component.
     * This should match the key used to bind KycManager in the service provider.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        // This matches the binding in your IranianValidationSuiteServiceProvider:
        // $this->app->singleton(KycManager::class, function ($app) { ... });
        // So, we can use the class name directly.
        return \Saman9074\IranianValidationSuite\Services\Kyc\KycManager::class;
    }
}
?>
