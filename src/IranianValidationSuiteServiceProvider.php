<?php

namespace Saman9074\IranianValidationSuite;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

// Import Rule classes
use Saman9074\IranianValidationSuite\Rules\NationalIdRule;
use Saman9074\IranianValidationSuite\Rules\BankCardNumberRule;
use Saman9074\IranianValidationSuite\Rules\ShebaNumberRule;
use Saman9074\IranianValidationSuite\Rules\PostalCodeRule;
use Saman9074\IranianValidationSuite\Rules\MobileNumberRule;
use Saman9074\IranianValidationSuite\Rules\LandlinePhoneNumberRule;

// Import the service class for the IranianValidator Facade
use Saman9074\IranianValidationSuite\Services\IranianValidatorService;

// Import KYC Manager and Drivers
use Saman9074\IranianValidationSuite\Services\Kyc\KycManager;
use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\UIdShahkarDriver; // Existing
use Saman9074\IranianValidationSuite\Services\Kyc\Drivers\FinnotechShahkarDriver; // New

class IranianValidationSuiteServiceProvider extends ServiceProvider
{

   // php artisan vendor:publish --provider="Saman9074\IranianValidationSuite\IranianValidationSuiteServiceProvider" --tag="iranian-validation-suite-config"
    protected string $configKey = 'iranian-validation-suite';

    public function register(): void
    {
        $this->app['config']->set($this->configKey, $this->app['config']->get($this->configKey, []));
        if (!is_array($this->app['config']->get($this->configKey))) {
             $this->app['config']->set($this->configKey, []);
        }
        $configPath = __DIR__ . '/../config/' . $this->configKey . '.php';
        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->configKey);
        }

        $this->app->singleton('iranian.validator', function ($app) {
            return new IranianValidatorService();
        });

        $this->app->singleton(KycManager::class, function ($app) {
            return new KycManager($app);
        });
    }

    public function boot(): void
    {
        $configPath = __DIR__ . '/../config/' . $this->configKey . '.php';
        $langPath = __DIR__ . '/../resources/lang';

        if (File::exists($configPath) && $this->app->runningInConsole()) {
            $this->publishes([
                $configPath => config_path($this->configKey . '.php'),
            ], ['config', 'iranian-validation-suite-config']);
        }

        if (File::isDirectory($langPath) && $this->app->runningInConsole()) {
            $this->publishes([
                $langPath => $this->app->langPath('vendor/' . $this->configKey),
            ], ['lang', 'iranian-validation-suite-lang']);
        }

        if (File::isDirectory($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->configKey);
        }

        $this->registerCustomValidators();
    }

    protected function registerCustomValidators(): void
    {
        Validator::extend('iranian_national_id', NationalIdRule::class . '@passes');
        Validator::replacer('iranian_national_id', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, __($this->configKey . '::validation.iranian_national_id'));
        });

        Validator::extend('iranian_bank_card', BankCardNumberRule::class . '@passes');
        Validator::replacer('iranian_bank_card', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, __($this->configKey . '::validation.iranian_bank_card'));
        });

        Validator::extend('iranian_sheba', ShebaNumberRule::class . '@passes');
        Validator::replacer('iranian_sheba', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, __($this->configKey . '::validation.iranian_sheba'));
        });

        Validator::extend('iranian_postal_code', PostalCodeRule::class . '@passes');
        Validator::replacer('iranian_postal_code', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, __($this->configKey . '::validation.iranian_postal_code'));
        });

        Validator::extend('iranian_mobile_number', MobileNumberRule::class . '@passes');
        Validator::replacer('iranian_mobile_number', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, __($this->configKey . '::validation.iranian_mobile_number'));
        });
    }
}
