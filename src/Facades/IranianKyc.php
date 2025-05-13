<?php

namespace Saman9074\IranianValidationSuite\Facades;

    use Illuminate\Support\Facades\Facade;
    use Saman9074\IranianValidationSuite\Services\Kyc\KycManager; // For PHPDoc and accessor

    /**
     * @method static \Saman9074\IranianValidationSuite\Contracts\Kyc\ShahkarInterface service(string $serviceName)
     * // Add more specific @method annotations here as you define more interfaces for services
     *
     * @method static bool matchMobileNationalId(string $nationalId, string $mobile) // Example for default driver
     *
     * @see \Saman9074\IranianValidationSuite\Services\Kyc\KycManager
     */
    class IranianKyc extends Facade
    {
        /**
         * Get the registered name of the component.
         *
         * This should match the key used to bind the KycManager in the service container.
         * Now using the Fully Qualified Class Name (FQCN).
         *
         * @return string
         */
        protected static function getFacadeAccessor(): string
        {
            // Use the FQCN of the KycManager class
            return KycManager::class;
        }
    }
    