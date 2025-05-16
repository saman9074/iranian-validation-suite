<?php

namespace Saman9074\IranianValidationSuite;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Support\DeferrableProvider; // For deferred loading

// Import Rule classes
use Saman9074\IranianValidationSuite\Rules\NationalIdRule;
use Saman9074\IranianValidationSuite\Rules\BankCardNumberRule;
use Saman9074\IranianValidationSuite\Rules\ShebaNumberRule;
use Saman9074\IranianValidationSuite\Rules\PostalCodeRule;
use Saman9074\IranianValidationSuite\Rules\MobileNumberRule;
// use Saman9074\IranianValidationSuite\Rules\LandlinePhoneNumberRule; // Uncomment if you have this rule

// Import the service class for the IranianValidator Facade
use Saman9074\IranianValidationSuite\Services\IranianValidatorService;

// Import KYC related classes
use Saman9074\IranianValidationSuite\Services\Kyc\KycManager;
use Saman9074\IranianValidationSuite\Contracts\Kyc\LivenessServiceInterface;
use Saman9074\IranianValidationSuite\Contracts\Kyc\ShahkarServiceInterface;
use Saman9074\IranianValidationSuite\Contracts\Kyc\IdentityServiceInterface;

class IranianValidationSuiteServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected string $configKey = 'iranian-validation-suite';
    protected string $kycConfigKey = 'iranian-validation-suite.kyc'; // More specific key for KYC config

    public function register(): void
    {
        // Ensure the main config key exists
        if (!$this->app['config']->has($this->configKey)) {
            $this->app['config']->set($this->configKey, []);
        }

        $configPath = __DIR__ . '/../config/' . $this->configKey . '.php';
        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->configKey);
        } else {
            // If the main config file doesn't exist in the package, create an empty kyc array
            // to prevent errors if kyc settings are accessed before publishing.
            $this->app['config']->set($this->kycConfigKey, $this->app['config']->get($this->kycConfigKey, []));
        }


        // Register IranianValidatorService for the 'IranianValidator' Facade
        $this->app->singleton('iranian.validator', function ($app) {
            return new IranianValidatorService();
        });

        // Register KycManager for the 'IranianKyc' Facade
        // This is already correctly registered by class name due to Facade accessor.
        $this->app->singleton(KycManager::class, function ($app) {
            return new KycManager($app);
        });
        // Alias for easier manual resolution if needed, though class name is preferred.
        $this->app->alias(KycManager::class, 'iranian.kyc.manager');


        // Bind specific KYC service interfaces to their default driver implementations via the manager.
        // This allows for easy dependency injection of these interfaces.

        $this->app->singleton(LivenessServiceInterface::class, function ($app) {
            /** @var KycManager $manager */
            $manager = $app->make(KycManager::class);
            return $manager->service('liveness'); // 'liveness' is the service type key in config
        });

        $this->app->singleton(ShahkarServiceInterface::class, function ($app) {
            /** @var KycManager $manager */
            $manager = $app->make(KycManager::class);
            return $manager->service('shahkar'); // 'shahkar' is the service type key in config
        });

        $this->app->singleton(IdentityServiceInterface::class, function ($app) {
            /** @var KycManager $manager */
            $manager = $app->make(KycManager::class);
            // 'identity_check' is the service type key in your config for identity services
            return $manager->service('identity_check');
        });
    }

    public function boot(): void
    {
        $configPath = __DIR__ . '/../config/' . $this->configKey . '.php';
        $langPath = __DIR__ . '/../resources/lang';

        if (File::exists($configPath) && $this->app->runningInConsole()) {
            $this->publishes([
                $configPath => config_path($this->configKey . '.php'),
            ], ['config', 'iranian-validation-suite-config']); // Keep existing tag
        }

        if (File::isDirectory($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->configKey); // Namespace for translations

            if ($this->app->runningInConsole()) {
                $this->publishes([
                    $langPath => $this->app->langPath('vendor/' . $this->configKey),
                ], ['lang', 'iranian-validation-suite-lang']); // Keep existing tag
            }
        }

        $this->registerCustomValidators();
    }

    protected function registerCustomValidators(): void
    {
        $rules = [
            'iranian_national_id' => NationalIdRule::class,
            'iranian_bank_card' => BankCardNumberRule::class,
            'iranian_sheba' => ShebaNumberRule::class,
            'iranian_postal_code' => PostalCodeRule::class,
            'iranian_mobile_number' => MobileNumberRule::class,
            // 'iranian_landline_phone' => LandlinePhoneNumberRule::class, // Example
        ];

        foreach ($rules as $ruleName => $ruleClass) {
            if (class_exists($ruleClass)) {
                Validator::extend($ruleName, $ruleClass . '@passes');
                Validator::replacer($ruleName, function ($message, $attribute, $rule, $parameters) use ($ruleName) {
                    // Use the main configKey for translation namespace
                    return str_replace(':attribute', $attribute, __($this->configKey . '::validation.' . $ruleName));
                });
            }
        }
    }

    /**
     * Get the services provided by the provider.
     * For DeferrableProvider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'iranian.validator', // For IranianValidator Facade
            IranianValidatorService::class,
            KycManager::class,    // For IranianKyc Facade and direct resolution
            'iranian.kyc.manager', // Alias
            LivenessServiceInterface::class,
            ShahkarServiceInterface::class,
            IdentityServiceInterface::class,
        ];
    }
}
