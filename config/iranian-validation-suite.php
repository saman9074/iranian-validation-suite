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
    'kyc' => [

        // درایور پیش‌فرض KYC که در صورت عدم درخواست درایور خاص،
        // یا اگر یک سرویس درایور پیش‌فرض خود را مشخص نکرده باشد، استفاده می‌شود.
        'default_driver' => env('IRANIAN_SUITE_KYC_DEFAULT_DRIVER', 'finnotech'), // مثال: 'uid' یا 'finnotech'

        // پیکربندی برای هر درایور KYC پشتیبانی شده.
        'drivers' => [

            'uid' => [
                'driver' => 'uid', // شناسه داخلی، با متد createUidDriver در KycManager مطابقت دارد
                'business_id' => env('UID_BUSINESS_ID'),
                'business_token' => env('UID_BUSINESS_TOKEN'),
                // اختیاری: در صورت نیاز، base_url و endpoint را بازنویسی کنید،
                // در غیر این صورت از مقادیر پیش‌فرض درایور استفاده خواهد شد.
                // 'base_url' => env('UID_BASE_URL', 'https://json-api.uid.ir'),
                // 'shahkar_endpoint' => env('UID_SHAHKAR_ENDPOINT', '/api/inquiry/mobile/owner/v2'),
            ],

            'finnotech' => [
                'driver' => 'finnotech', // با متد createFinnotechDriver در KycManager مطابقت دارد
                'client_id' => env('FINNOTECH_CLIENT_ID'),
                'client_secret' => env('FINNOTECH_CLIENT_SECRET'), // کلید مخفی کلاینت
                'token_nid' => env('FINNOTECH_TOKEN_NID'),       // کد ملی برای تولید توکن
                'shahkar_scope' => env('FINNOTECH_SHAHKAR_SCOPE', 'kyc:sms-shahkar-send:get'), // اسکوپ پیش‌فرض برای شاهکار
                // آدرس پایه پیش‌فرض پروداکشن است، می‌توان برای سندباکس آن را بازنویسی کرد
                'base_url' => env('FINNOTECH_BASE_URL', 'https://api.finnotech.ir'),
                // 'base_url' => env('FINNOTECH_BASE_URL', 'https://sandboxapi.finnotech.ir'), // برای سندباکس
                // 'token_endpoint' => '/dev/v2/oauth2/token', // پیش‌فرض در درایور
                // 'shahkar_endpoint' => '/kyc/v2/clients/{clientId}/shahkar/smsSend', // پیش‌فرض در درایور
            ],

            'farashenasa' => [ // مثال برای ارائه‌دهنده دیگر
                'driver' => 'farashenasa',
                'api_key' => env('FARASHENASA_API_KEY'),
                'base_url' => env('FARASHENASA_BASE_URL', 'https://api.farashenasa.ir'), // آدرس مثال
                // 'liveness_endpoint' => '/liveness/v1/check',
            ],
        ],

        // پیکربندی برای سرویس‌های خاص KYC.
        // برای هر سرویس، می‌توانید یک درایور پیش‌فرض تعریف کنید.
        // اگر سرویسی در اینجا لیست نشده باشد، یا 'default_driver' آن تنظیم نشده باشد،
        // از 'kyc.default_driver' (درایور پیش‌فرض کلی) برای آن سرویس استفاده خواهد شد.
        'services' => [
            'shahkar' => [ // سرویس برای تطابق کد ملی و موبایل
                'default_driver' => env('IRANIAN_SUITE_SHAHKAR_DRIVER', 'finnotech'), // درایور پیش‌فرض برای سرویس شاهکار
                // برای تنظیم فینوتک به عنوان پیش‌فرض برای شاهکار، در فایل .env تنظیم کنید:
                // IRANIAN_SUITE_SHAHKAR_DRIVER=uid
            ],
            'identity_check' => [ // سرویس برای استعلام اطلاعات هویتی
                // اگر 'default_driver' اینجا تنظیم نشود، به kyc.default_driver بازمی‌گردد
                 'default_driver' => env('IRANIAN_SUITE_IDENTITY_DRIVER', 'uid'), // مثال
            ],
            'liveness' => [ // سرویس برای زنده‌سنجی
                'default_driver' => env('IRANIAN_SUITE_LIVENESS_DRIVER', 'farashenasa'), // مثال: فراشناسا برای زنده‌سنجی
            ],
            // سرویس‌های دیگر مانند تطابق کارت با کد ملی و غیره را اینجا اضافه کنید.
        ],
    ],

];
