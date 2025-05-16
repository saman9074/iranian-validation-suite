# راهنمای استفاده از سرویس احراز هویت زنده‌سنجی (Liveness) فراشناسا

این راهنما نحوه استفاده از سرویس احراز هویت زنده‌سنجی فراشناسا را با استفاده از پکیج `saman9074/iranian-validation-suite` در پروژه لاراول شما توضیح می‌دهد.

## پیش‌نیازها

1.  پکیج `saman9074/iranian-validation-suite` باید قبلاً در پروژه شما نصب و فایل پیکربندی اصلی آن (`config/iranian-validation-suite.php`) منتشر شده باشد.
2.  شما باید اطلاعات حساب کاربری خود در فراشناسا (شامل `gateway-token` یا `api_key`) را در اختیار داشته باشید.

## ۱. پیکربندی درایور فراشناسا

ابتدا، باید اطلاعات مربوط به سرویس فراشناسا را در فایل پیکربندی پکیج خود (`config/iranian-validation-suite.php`) وارد کنید. بخش `kyc.drivers.farashenasa` را پیدا کرده و مقادیر زیر را بر اساس اطلاعات حساب خود و مستندات فراشناسا تنظیم نمایید:

```bash
// config/iranian-validation-suite.php

'kyc' => [
    // ... سایر تنظیمات kyc ...

    'drivers' => [
        // ... سایر درایورها ...

        'farashenasa' => [
            // 'api_key' همان مقدار 'gateway-token' شما از پنل فراشناسا است.
            'api_key'     => env('FARASHENASA_GATEWAY_TOKEN', 'YOUR_FARASHENASA_GATEWAY_TOKEN'),
            // آدرس پایه API فراشناسا
            'base_url'    => env('FARASHENASA_BASE_URL', '[https://partai.gw.isahab.ir](https://partai.gw.isahab.ir)'),
            // مقدار ثابت هدر gateway-system
            'gateway_system_value' => env('FARASHENASA_GATEWAY_SYSTEM', 'sahab'),

            // نقاط پایانی (اختیاری، در صورت نیاز به بازنویسی مقادیر پیش‌فرض در درایور)
            'endpoints' => [
                'getText'      => '/farashenasa/v1/test',       // اندپوینت دریافت متن برای ویدیو
                'authenticate' => '/farashenasa/v1/authenticate', // اندپوینت اصلی احراز هویت
            ],

            // مهلت زمانی (timeout) برای درخواست‌ها به ثانیه (اختیاری)
            'timeout' => env('FARASHENASA_TIMEOUT', 60), // مهلت زمانی عمومی
            'initiate_timeout' => env('FARASHENASA_INITIATE_TIMEOUT', 30), // برای دریافت متن
            'verify_timeout' => env('FARASHENASA_VERIFY_TIMEOUT', 180),    // برای احراز هویت (ممکن است بیشتر طول بکشد)
        ],
    ],

    'services' => [
        'liveness' => [
            // تنظیم درایور پیش‌فرض برای سرویس زنده‌سنجی به فراشناسا
            'default_driver' => env('IRANIAN_SUITE_LIVENESS_DRIVER', 'farashenasa'),
        ],
        // ... سایر سرویس‌ها ...
    ],
],
```
توضیحات مقادیر پیکربندی:

    api_key: مقدار gateway-token شما از پنل فراشناسا.

    base_url: آدرس پایه API فراشناسا (مثلاً https://partai.gw.isahab.ir).

    gateway_system_value: مقدار ثابت sahab که در هدر gateway-system ارسال می‌شود.

    endpoints: می‌توانید اندپوینت‌های خاص را در صورت نیاز بازنویسی کنید.

    timeout, initiate_timeout, verify_timeout: برای تنظیم مهلت زمانی درخواست‌های HTTP. توجه: مطمئن شوید که مقدار max_execution_time در فایل php.ini سرور شما از این مقادیر بیشتر باشد.

فراموش نکنید که مقادیر حساس مانند FARASHENASA_GATEWAY_TOKEN را در فایل .env پروژه خود تعریف کنید:
```bash
FARASHENASA_GATEWAY_TOKEN="your_actual_gateway_token_here"
FARASHENASA_BASE_URL="[https://partai.gw.isahab.ir](https://partai.gw.isahab.ir)"
# IRANIAN_SUITE_LIVENESS_DRIVER=farashenasa # اگر می‌خواهید به صراحت در env تعریف کنید
```
پس از تغییر فایل .env، کش کانفیگ لاراول را پاک کنید: php artisan config:clear

## ۲. استفاده از سرویس زنده‌سنجی فراشناسا

سرویس احراز هویت فراشناسا معمولاً در دو مرحله انجام می‌شود:

دریافت متن برای بازخوانی: ابتدا یک متن از سرویس فراشناسا دریافت می‌کنید که کاربر باید آن را در ویدیو بخواند.

ارسال داده‌ها برای احراز هویت: سپس عکس سلفی کاربر، ویدیوی ضبط شده (که کاربر در آن متن را می‌خواند) و اطلاعات شناسایی کاربر به سرویس ارسال می‌شود.

می‌توانید از Facade IranianKyc برای دسترسی به این سرویس استفاده کنید.

مثال در کنترلر

در ادامه یک مثال ساده از نحوه استفاده در یک کنترلر لاراول آورده شده است.

```bash
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Saman9074\IranianValidationSuite\Facades\IranianKyc;
use Saman9074\IranianValidationSuite\Exceptions\Kyc\KycException;
use Saman9074\IranianValidationSuite\Exceptions\Kyc\KycConfigurationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator as LaravelValidator;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class UserKycController extends Controller
{
    /**
     * نمایش فرم اولیه و دریافت متن از فراشناسا
     */
    public function showKycForm()
    {
        $initiationError = null;
        $uniqueKey = Str::uuid()->toString(); // ایجاد یک کلید یکتای مناسب برای تراکنش
        $textToRead = null;
        $speakingId = null;

        try {
            // فراخوانی سرویس زنده‌سنجی (که به درایور فراشناسا متصل است)
            // برای دریافت متن جهت بازخوانی در ویدیو
            $initiateResponse = IranianKyc::service('liveness')->initiateLivenessCheck([
                'uniqueKey' => $uniqueKey,
            ]);

            if (!$initiateResponse->isSuccessful() || empty($initiateResponse->get('text_to_read'))) {
                Log::error('Farashenasa: Failed to initiate liveness check (get text).', [
                    'uniqueKey' => $uniqueKey,
                    'response_message' => $initiateResponse->getMessage(),
                    'response_data' => $initiateResponse->getRawData()
                ]);
                $initiationError = 'خطا در دریافت متن از سرویس احراز هویت: ' . $initiateResponse->getMessage();
            } else {
                $textToRead = $initiateResponse->get('text_to_read');
                $speakingId = $initiateResponse->get('speaking_id'); // ممکن است برای برخی ارائه‌دهندگان مفید باشد
            }
        } catch (KycConfigurationException $e) {
            Log::error("Farashenasa KYC Configuration Error: " . $e->getMessage());
            $initiationError = "خطای پیکربندی سرویس احراز هویت: " . $e->getMessage();
        } catch (KycException $e) {
            Log::error("Farashenasa KYC Service Error during text initiation: " . $e->getMessage(), ['uniqueKey' => $uniqueKey, 'exception' => $e]);
            $initiationError = "خطا در ارتباط با سرویس احراز هویت: " . $e->getMessage();
        } catch (\Exception $e) {
            Log::error("General Error during Farashenasa KYC form display: " . $e->getMessage(), ['uniqueKey' => $uniqueKey, 'exception' => $e]);
            $initiationError = "خطای غیرمنتظره در سیستم رخ داده است.";
        }

        // ارسال داده‌ها به view برای نمایش فرم به کاربر
        return view('kyc.farashenasa_form', [ // نام view خود را جایگزین کنید
            'uniqueKey' => $uniqueKey,
            'textToRead' => $textToRead,
            'speakingId' => $speakingId,
            'initiationError' => $initiationError,
        ]);
    }

    /**
     * پردازش اطلاعات ارسالی از فرم و ارسال به فراشناسا برای احراز هویت
     */
    public function processKyc(Request $request)
    {
        $validator = LaravelValidator::make($request->all(), [
            'uniqueKey' => 'required|string|max:255',
            'national_code' => ['required', 'string', new \Saman9074\IranianValidationSuite\Rules\NationalIdRule], // استفاده از قانون اعتبارسنجی کد ملی از همین پکیج
            'card_serial' => 'nullable|string|max:50', // سریال کارت ملی
            'birth_date' => 'required|string|max:10',  // مثال: 1370/01/01 یا 13700101
            'selfie' => 'required|file|image|mimetypes:image/jpeg,image/png|max:5120', // حداکثر 5MB
            'video' => 'required|file|mimetypes:video/mp4,video/webm,video/quicktime|max:20480', // حداکثر 20MB
        ]);

        if ($validator->fails()) {
            return redirect()->back() // یا به روت فرم با نام مشخص
                ->withErrors($validator)
                ->withInput();
        }

        try {
            /** @var UploadedFile $selfieUploadedFile */
            $selfieUploadedFile = $request->file('selfie');
            /** @var UploadedFile $videoUploadedFile */
            $videoUploadedFile = $request->file('video');

            $uniqueKey = $request->input('uniqueKey');
            $additionalInfo = [
                'nationalCode'           => $request->input('national_code'),
                'nationalCardSerialNumber' => $request->input('card_serial', ''), // ارسال رشته خالی اگر وارد نشده
                'birthDate'              => str_replace('/', '', $request->input('birth_date')), // تبدیل به فرمت YYYYMMDD
            ];

            Log::info('Farashenasa KYC: Processing verification.', [
                'uniqueKey' => $uniqueKey,
                'additionalInfo' => $additionalInfo,
                'selfie_original_name' => $selfieUploadedFile->getClientOriginalName(),
                'video_original_name' => $videoUploadedFile->getClientOriginalName(),
            ]);

            // فراخوانی سرویس زنده‌سنجی برای احراز هویت
            $verifyResponse = IranianKyc::service('liveness')->verifyLiveness(
                [
                    'selfie_file_info' => [
                        'uploaded_file' => $selfieUploadedFile // ارسال آبجکت UploadedFile
                    ],
                    'video_file_info' => [
                        'uploaded_file' => $videoUploadedFile // ارسال آبجکت UploadedFile
                    ]
                ],
                [
                    'uniqueKey'      => $uniqueKey,
                    'additionalInfo' => $additionalInfo,
                ]
            );

            // بررسی نتیجه کلی فراخوانی API (آیا ارتباط موفق بود و پاسخ معتبر دریافت شد؟)
            if (!$verifyResponse->isSuccessful()) {
                Log::error('Farashenasa KYC: Verification API call failed or returned inconclusive status.', [
                    'uniqueKey' => $uniqueKey,
                    'response_message' => $verifyResponse->getMessage(),
                    'response_data' => $verifyResponse->getRawData()
                ]);
                // پیام خطا از خود فراشناسا (مثلاً "داده های ورودی نامعتبر است!") در $verifyResponse->getMessage() موجود است.
                return redirect()->back()->with('kyc_error', 'خطا در پردازش احراز هویت: ' . $verifyResponse->getMessage())->withInput();
            }

            // اگر فراخوانی API موفق بود، حالا نتیجه خود احراز هویت را بررسی می‌کنیم
            $livenessResultData = $verifyResponse->getData();

            if (isset($livenessResultData['liveness_successful']) && $livenessResultData['liveness_successful'] === true) {
                // احراز هویت موفقیت‌آمیز بود
                Log::info('Farashenasa KYC: Verification successful.', ['uniqueKey' => $uniqueKey, 'result' => $livenessResultData]);
                return redirect()->route('kyc.success.page') // به صفحه موفقیت ریدایرکت کنید
                    ->with('kyc_message', $livenessResultData['result_message'] ?? 'هویت شما با موفقیت تایید شد.');
            } else {
                // احراز هویت ناموفق بود (مثلاً هویت تایید نشد)
                Log::warning('Farashenasa KYC: Verification not confirmed.', ['uniqueKey' => $uniqueKey, 'result' => $livenessResultData]);
                return redirect()->back()->with('kyc_error', $livenessResultData['result_message'] ?? 'هویت شما مورد تایید قرار نگرفت. لطفاً دوباره تلاش کنید.')
                                 ->with('retry_allowed', $livenessResultData['retry_allowed'] ?? false)
                                 ->withInput();
            }

        } catch (KycConfigurationException $e) {
            Log::error("Farashenasa KYC Configuration Error during verification: " . $e->getMessage(), ['uniqueKey' => $request->input('uniqueKey')]);
            return redirect()->back()->with('kyc_error', 'خطای پیکربندی سرویس احراز هویت.')->withInput();
        } catch (KycException $e) {
            Log::error("Farashenasa KYC Service Error during verification: " . $e->getMessage(), ['uniqueKey' => $request->input('uniqueKey'), 'exception' => $e]);
            return redirect()->back()->with('kyc_error', 'خطا در ارتباط با سرویس احراز هویت: ' . $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error("General Error during Farashenasa KYC verification: " . $e->getMessage(), ['uniqueKey' => $request->input('uniqueKey'), 'exception' => $e]);
            return redirect()->back()->with('kyc_error', 'خطای غیرمنتظره در سیستم رخ داده است.')->withInput();
        }
    }
}
```
توضیح فرم HTML و JavaScript سمت کلاینت

در فایل Blade مربوط به فرم (kyc.farashenasa_form.blade.php در مثال بالا)، شما باید:

فیلدهایی برای ورود اطلاعات شناسایی (national_code, birth_date, card_serial) قرار دهید.

بخشی برای نمایش متنی که کاربر باید در ویدیو بخواند (مقدار $textToRead از کنترلر).

بخشی برای گرفتن عکس سلفی از دوربین کاربر و قرار دادن آن در یک فیلد <input type="file" name="selfie">.

بخشی برای ضبط ویدیو از دوربین کاربر (خواندن متن نمایش داده شده) و قرار دادن آن در یک فیلد <input type="file" name="video">.

فیلد مخفی برای ارسال uniqueKey.

کد JavaScript برای دسترسی به دوربین، گرفتن عکس و ضبط ویدیو مشابه چیزی است که قبلاً در فایل verify_form.blade.php (شناسه farashenasa_blade_view_validation_errors) با هم کار کردیم.

نکات مهم

uniqueKey: این کلید باید برای هر تراکنش احراز هویت جدید، یکتا باشد و در هر دو مرحله (دریافت متن و ارسال داده‌ها) یکسان استفاده شود.

مدیریت خطا: همیشه پاسخ‌های دریافتی از متدهای initiateLivenessCheck و verifyLiveness را بررسی کنید ($response->isSuccessful() و $response->getMessage() و $response->getData()).

فایل‌ها: مطمئن شوید که فایل‌های سلفی و ویدیو به درستی توسط کاربر تهیه شده و با فرمت و حجم مجاز ارسال می‌شوند.

تجربه کاربری: به کاربر بازخورد مناسب در طول فرآیند (مثلاً نمایش وضعیت ضبط، پیام‌های خطا) ارائه دهید.