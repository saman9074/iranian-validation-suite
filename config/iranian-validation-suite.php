<?php

// config/iranian-validation-suite.php

return [

    'sample_setting' => 'default_value', // تنظیم نمونه موجود

    /*
    |--------------------------------------------------------------------------
    | پیکربندی سرویس‌های KYC (احراز هویت مشتری)
    |--------------------------------------------------------------------------
    |
    | این بخش تنظیمات مربوط به ارائه‌دهندگان مختلف سرویس KYC
    | و سرویس‌هایی که ارائه می‌دهند (مانند شاهکار، زنده‌سنجی) را تعریف می‌کند.
    |
    */

// بخشی از فایل config/iranian-validation-suite.php

    // ...
    'kyc' => [
        // ...
        'drivers' => [
            // ... سایر درایورها
            'farashenasa' => [
                'api_key' => env('FARASHENASA_GATEWAY_TOKEN'), // این همان gateway-token شماست
                'base_url' => env('FARASHENASA_BASE_URL', 'https://partai.gw.isahab.ir'), // آدرس پایه API فراشناسا
                'gateway_system_value' => env('FARASHENASA_GATEWAY_SYSTEM', 'sahab'), // مقدار ثابت gateway-system

                // نقاط پایانی (اختیاری، در صورت نیاز به بازنویسی مقادیر پیش‌فرض در درایور)
                'endpoints' => [
                    'getText'      => '/farashenasa/v1/test',       // اندپوینت دریافت متن
                    'authenticate' => '/farashenasa/v1/authenticate', // اندپوینت احراز هویت
                    // 'result'    => '/farashenasa/v1/result', // اگر اندپوینت جدا برای نتیجه وجود داشت
                ],

                // مهلت زمانی (timeout) برای درخواست‌ها (اختیاری، درایور مقادیر پیش‌فرض دارد)
                'timeout' => env('FARASHENASA_TIMEOUT', 45), // مهلت زمانی عمومی به ثانیه
                'initiate_timeout' => env('FARASHENASA_INITIATE_TIMEOUT', 30), // برای دریافت متن
                'verify_timeout' => env('FARASHENASA_VERIFY_TIMEOUT', 75),     // برای احراز هویت (ممکن است بیشتر طول بکشد)

                // 'default_callback_url' => env('FARASHENASA_DEFAULT_CALLBACK'), // اگر در initiateLivenessCheck نیاز بود
            ],
            // ...
        ],
        // ...
        'services' => [
            'liveness' => [
                'default_driver' => env('IRANIAN_SUITE_LIVENESS_DRIVER', 'farashenasa'),
            ],
            // ... سایر سرویس‌ها
        ],
    ],

];
