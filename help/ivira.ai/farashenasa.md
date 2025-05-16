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

کد JavaScript برای دسترسی به دوربین، گرفتن عکس و ضبط ویدیو که باید طراحی کنید مانند نمونه کد زیر:

```bash
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست احراز هویت فراشناسا (سلفی و ویدیو از دوربین)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
        }
        .form-input, .file-input-styling {
            @apply mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md text-sm shadow-sm placeholder-slate-400
                   focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500
                   disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 disabled:shadow-none
                   invalid:border-pink-500 invalid:text-pink-600
                   focus:invalid:border-pink-500 focus:invalid:ring-pink-500;
        }
        .form-label {
            @apply block text-sm font-medium text-slate-700;
        }
        .btn {
            @apply px-4 py-2 rounded-md shadow-sm text-sm font-medium transition duration-150 ease-in-out;
        }
        .btn-primary {
            @apply bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
        }
        .btn-secondary {
            @apply bg-slate-200 text-slate-700 hover:bg-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-400;
        }
        .btn-danger {
            @apply bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500;
        }
        .error-message {
            @apply text-pink-600 text-xs mt-1;
        }
        .camera-container { /* Renamed from video-container for clarity */
            @apply mt-2 border border-slate-300 rounded-md overflow-hidden bg-slate-200;
        }
        #liveSelfieFeed, #liveVideoFeed, #capturedSelfiePreview, #recordedVideoPlayback {
            @apply w-full h-auto block;
            max-height: 240px; /* Adjust as needed, smaller for selfie maybe */
        }
        #capturedSelfiePreview {
             border: 1px solid #ccc;
        }
    </style>
</head>
<body class="bg-slate-100">
    <div class="container mx-auto p-4 sm:p-8 max-w-2xl">
        <div class="bg-white shadow-xl rounded-lg p-6 sm:p-8">
            <h1 class="text-2xl font-semibold text-slate-800 mb-6 text-center">تست احراز هویت فراشناسا (سلفی و ویدیو از دوربین)</h1>

            @if(isset($initiationError) && $initiationError)
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p class="font-bold">خطا در آماده‌سازی فرم:</p>
                    <p>{{ $initiationError }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">خطا در ورودی‌ها!</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!isset($initiationError) || (isset($textToRead) && $textToRead))
            <form id="farashenasaForm" action="{{ route('farashenasa.test.verify') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <p class="form-label">کلید یکتا (Unique Key):</p>
                    <input type="text" name="uniqueKey_display" id="uniqueKey_display" value="{{ $uniqueKey ?? old('uniqueKey') }}" class="form-input bg-slate-100" readonly>
                    <input type="hidden" name="uniqueKey" id="formUniqueKey" value="{{ $uniqueKey ?? old('uniqueKey') }}">
                    @error('uniqueKey') <p class="error-message">{{ $message }}</p> @enderror
                </div>

                @if(isset($textToRead) && $textToRead)
                <div class="bg-amber-100 border-l-4 border-amber-500 text-amber-700 p-4" role="alert">
                    <p class="font-bold">متن جهت بازخوانی در ویدیو:</p>
                    <p id="textToReadContent">{{ $textToRead }}</p>
                    @if(isset($speakingId))
                        <p class="text-xs mt-1">(شناسه متن: {{ $speakingId }})</p>
                    @endif
                </div>
                @else
                 <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                    <p class="font-bold">توجه:</p>
                    <p>متن برای بازخوانی دریافت نشد. لطفاً از صحت پیکربندی و در دسترس بودن سرویس فراشناسا اطمینان حاصل کنید و صفحه را مجدداً بارگذاری نمایید.</p>
                </div>
                @endif

                {{-- Fields for additionalInfo --}}
                <div>
                    <label for="national_code" class="form-label">کد ملی:</label>
                    <input type="text" name="national_code" id="national_code" value="{{ old('national_code') }}" class="form-input ltr-input @error('national_code') border-pink-500 @enderror" required placeholder="xxxxxxxxxx">
                    @error('national_code') <p class="error-message">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="birth_date" class="form-label">تاریخ تولد (مثال: 1365/10/12 یا 13741213):</label>
                    <input type="text" name="birth_date" id="birth_date" value="{{ old('birth_date') }}" class="form-input ltr-input @error('birth_date') border-pink-500 @enderror" required placeholder="yyyy/mm/dd">
                    @error('birth_date') <p class="error-message">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="card_serial" class="form-label">سریال کارت ملی:</label>
                    <input type="text" name="card_serial" id="card_serial" value="{{ old('card_serial') }}" class="form-input ltr-input @error('card_serial') border-pink-500 @enderror" placeholder="مثال: 0G37000000">
                    @error('card_serial') <p class="error-message">{{ $message }}</p> @enderror
                </div>
                {{-- End of fields for additionalInfo --}}

                {{-- بخش گرفتن عکس سلفی --}}
                <div class="space-y-2">
                    <label class="form-label">عکس سلفی (از دوربین):</label>
                    <div class="camera-container">
                        <video id="liveSelfieFeed" autoplay muted playsinline></video>
                        <img id="capturedSelfiePreview" src="#" alt="عکس سلفی گرفته شده" style="display:none;" class="w-full max-w-xs mx-auto mt-2 rounded"/>
                        <canvas id="selfieCanvas" style="display:none;"></canvas> {{-- Hidden canvas for processing --}}
                    </div>
                    <div id="selfieError" class="error-message"></div>
                    <div id="selfieStatus" class="text-sm text-slate-600"></div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="startSelfieCameraBtn" class="btn btn-secondary">فعال‌سازی دوربین سلفی</button>
                        <button type="button" id="captureSelfieBtn" class="btn btn-danger" style="display:none;">گرفتن عکس سلفی</button>
                        <button type="button" id="retakeSelfieBtn" class="btn btn-secondary" style="display:none;">گرفتن مجدد عکس</button>
                    </div>
                    @error('selfie') <p class="error-message">{{ $message }}</p> @enderror
                    <input type="file" name="selfie" id="selfieFile" style="display:none;">
                </div>

                {{-- بخش ضبط ویدیو --}}
                <div class="space-y-2">
                    <label class="form-label">ویدیو خواندن متن (از دوربین):</label>
                    <div class="camera-container">
                        <video id="liveVideoFeed" autoplay muted playsinline></video>
                        <video id="recordedVideoPlayback" controls style="display:none;"></video>
                    </div>
                    <div id="videoError" class="error-message"></div>
                    <div id="videoStatus" class="text-sm text-slate-600"></div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="startVideoCameraBtn" class="btn btn-secondary" @if(!isset($textToRead) || !$textToRead) disabled @endif>فعال‌سازی دوربین ویدیو</button>
                        <button type="button" id="startRecordingBtn" class="btn btn-danger" style="display:none;" @if(!isset($textToRead) || !$textToRead) disabled @endif>شروع ضبط ویدیو</button>
                        <button type="button" id="stopRecordingBtn" class="btn btn-secondary" style="display:none;">توقف ضبط</button>
                        <button type="button" id="retakeVideoBtn" class="btn btn-secondary" style="display:none;">ضبط مجدد ویدیو</button>
                    </div>
                    @error('video') <p class="error-message">{{ $message }}</p> @enderror
                    <input type="file" name="video" id="videoFile" style="display:none;">
                </div>

                <div>
                    <button type="submit" id="submitFormBtn" class="btn btn-primary w-full @if(!isset($textToRead) || !$textToRead) opacity-50 cursor-not-allowed @endif" @if(!isset($textToRead) || !$textToRead) disabled @endif>
                        ارسال و احراز هویت
                    </button>
                </div>
            </form>
            @elseif(!isset($initiationError))
             <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                <p class="font-bold">توجه:</p>
                <p>متن برای بازخوانی دریافت نشد. لطفاً از صحت پیکربندی و در دسترس بودن سرویس فراشناسا اطمینان حاصل کنید و صفحه را مجدداً بارگذاری نمایید.</p>
            </div>
            @endif
        </div>
    </div>

<script>
    // --- Selfie Capture Elements ---
    const liveSelfieFeed = document.getElementById('liveSelfieFeed');
    const capturedSelfiePreview = document.getElementById('capturedSelfiePreview');
    const selfieCanvas = document.getElementById('selfieCanvas');
    const startSelfieCameraBtn = document.getElementById('startSelfieCameraBtn');
    const captureSelfieBtn = document.getElementById('captureSelfieBtn');
    const retakeSelfieBtn = document.getElementById('retakeSelfieBtn');
    const selfieFileField = document.getElementById('selfieFile');
    const selfieError = document.getElementById('selfieError');
    const selfieStatus = document.getElementById('selfieStatus');
    let selfieStream;

    // --- Video Recording Elements (from previous code) ---
    const liveVideoFeed = document.getElementById('liveVideoFeed'); // Note: ID conflict if not careful, using separate for selfie
    const recordedVideoPlayback = document.getElementById('recordedVideoPlayback');
    const startVideoCameraBtn = document.getElementById('startVideoCameraBtn');
    const startRecordingBtn = document.getElementById('startRecordingBtn');
    const stopRecordingBtn = document.getElementById('stopRecordingBtn');
    const retakeVideoBtn = document.getElementById('retakeVideoBtn');
    const videoFileField = document.getElementById('videoFile');
    const videoError = document.getElementById('videoError');
    const videoStatus = document.getElementById('videoStatus');
    let videoStream; // Renamed from 'stream' to avoid conflict
    let mediaRecorder;
    let recordedBlobs;
    let chosenMimeType = '';

    // --- Common Elements ---
    const submitFormBtn = document.getElementById('submitFormBtn');
    const textToReadContent = document.getElementById('textToReadContent');


    // === Selfie Camera Logic ===
    async function initSelfieCamera() {
        try {
            const constraints = { audio: false, video: { facingMode: "user", width: { ideal: 640 }, height: {ideal: 480} } };
            selfieStream = await navigator.mediaDevices.getUserMedia(constraints);
            liveSelfieFeed.srcObject = selfieStream;
            liveSelfieFeed.style.display = 'block';
            capturedSelfiePreview.style.display = 'none';
            selfieError.textContent = '';
            startSelfieCameraBtn.style.display = 'none';
            captureSelfieBtn.style.display = 'inline-block';
            retakeSelfieBtn.style.display = 'none';
            selfieStatus.textContent = 'دوربین سلفی فعال شد. چهره خود را در کادر قرار دهید.';
        } catch (e) {
            console.error('navigator.getUserMedia error (selfie):', e);
            selfieError.textContent = 'خطا در دسترسی به دوربین سلفی: ' + e.message;
            startSelfieCameraBtn.style.display = 'inline-block';
            captureSelfieBtn.style.display = 'none';
        }
    }

    function captureSelfie() {
        if (!selfieStream || !selfieStream.active) {
            selfieError.textContent = 'دوربین سلفی فعال نیست.';
            return;
        }
        selfieCanvas.width = liveSelfieFeed.videoWidth;
        selfieCanvas.height = liveSelfieFeed.videoHeight;
        selfieCanvas.getContext('2d').drawImage(liveSelfieFeed, 0, 0, selfieCanvas.width, selfieCanvas.height);
        
        // Try to get JPEG, fallback to PNG
        let imageFormat = 'image/jpeg';
        let imageExtension = 'jpg';
        let dataUrl = selfieCanvas.toDataURL(imageFormat, 0.9); // 0.9 quality for JPEG

        if (!dataUrl || dataUrl.length < 100) { // Basic check if toDataURL failed for JPEG
            console.warn('Failed to capture as JPEG, trying PNG.');
            imageFormat = 'image/png';
            imageExtension = 'png';
            dataUrl = selfieCanvas.toDataURL(imageFormat);
        }

        capturedSelfiePreview.src = dataUrl;
        capturedSelfiePreview.style.display = 'block';
        liveSelfieFeed.style.display = 'none'; // Hide live feed after capture

        // Convert dataURL to File object
        dataUrlToBlob(dataUrl).then(blob => {
            const selfieFileName = `captured_selfie_${Date.now()}.${imageExtension}`;
            const selfieFile = new File([blob], selfieFileName, { type: imageFormat });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(selfieFile);
            selfieFileField.files = dataTransfer.files;
            console.log(`Selfie captured: ${selfieFileName}, Type: ${imageFormat}, Size: ${selfieFile.size} bytes`);
            selfieStatus.textContent = `عکس سلفی با فرمت ${imageExtension.toUpperCase()} گرفته شد.`;
            captureSelfieBtn.style.display = 'none';
            retakeSelfieBtn.style.display = 'inline-block';
        });
    }

    function retakeSelfie() {
        selfieFileField.value = ''; // Clear the file input
        capturedSelfiePreview.style.display = 'none';
        capturedSelfiePreview.src = '#';
        liveSelfieFeed.style.display = 'block';
        captureSelfieBtn.style.display = 'inline-block';
        retakeSelfieBtn.style.display = 'none';
        selfieStatus.textContent = 'برای گرفتن مجدد عکس، روی "گرفتن عکس سلفی" کلیک کنید.';
        if (!selfieStream || !selfieStream.active) { // Re-init if stream was lost
            initSelfieCamera();
        }
    }

    function dataUrlToBlob(dataUrl) {
        return fetch(dataUrl).then(res => res.blob());
    }


    // === Video Recording Logic (adapted) ===
    async function initVideoCamera() {
        try {
            const constraints = {
                audio: true,
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: "user" }
            };
            videoStream = await navigator.mediaDevices.getUserMedia(constraints);
            liveVideoFeed.srcObject = videoStream; // Assuming same video element for preview
            videoError.textContent = '';
            startVideoCameraBtn.style.display = 'none';
            startRecordingBtn.style.display = 'inline-block';
            videoStatus.textContent = 'دوربین ویدیو فعال شد.';
        } catch (e) {
            console.error('navigator.getUserMedia error (video):', e);
            videoError.textContent = 'خطا در دسترسی به دوربین یا میکروفون برای ویدیو: ' + e.message;
            startVideoCameraBtn.style.display = 'inline-block';
            startRecordingBtn.style.display = 'none';
        }
    }

    function startRecording() {
        if (!videoStream || !videoStream.active) {
            videoError.textContent = 'دوربین ویدیو فعال نیست.';
            initVideoCamera();
            return;
        }
        recordedBlobs = [];
        const mimeTypesToTry = [
            'video/mp4;codecs=avc1.42E01E,mp4a.40.2', 'video/mp4;codecs=h264,aac', 'video/mp4',
            'video/webm;codecs=vp8,opus', 'video/webm;codecs=vp9,opus', 'video/webm', ''
        ];
        chosenMimeType = '';
        for (const mimeType of mimeTypesToTry) {
            if (MediaRecorder.isTypeSupported(mimeType) || mimeType === '') {
                chosenMimeType = mimeType; break;
            }
        }
        console.log('Using mimeType for video recording:', chosenMimeType);

        try {
            mediaRecorder = new MediaRecorder(videoStream, { mimeType: chosenMimeType });
        } catch (e) {
            console.error('Exception creating MediaRecorder (video):', e);
            videoError.textContent = `خطا در ایجاد MediaRecorder ویدیو: ${e.toString()}`; return;
        }

        videoStatus.textContent = 'در حال ضبط ویدیو... متن را بخوانید. فرمت: ' + (chosenMimeType || 'پیش‌فرض');
        startRecordingBtn.style.display = 'none';
        stopRecordingBtn.style.display = 'inline-block';
        retakeVideoBtn.style.display = 'none';
        recordedVideoPlayback.style.display = 'none';
        liveVideoFeed.style.display = 'block'; // Ensure correct video feed is shown

        mediaRecorder.onstop = (event) => {
            const actualMimeType = chosenMimeType || mediaRecorder.mimeType || 'video/webm';
            const fileExtension = actualMimeType.includes('mp4') ? 'mp4' : 'webm';
            const superBuffer = new Blob(recordedBlobs, { type: actualMimeType });
            recordedVideoPlayback.src = window.URL.createObjectURL(superBuffer);
            recordedVideoPlayback.style.display = 'block';
            liveVideoFeed.style.display = 'none';

            const videoFileName = `recorded_video_${Date.now()}.${fileExtension}`;
            const videoFile = new File([superBuffer], videoFileName, { type: actualMimeType });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(videoFile);
            videoFileField.files = dataTransfer.files;
            console.log(`Video file created: ${videoFileName}, Type: ${actualMimeType}, Size: ${videoFile.size} bytes`);
            videoStatus.textContent = `ویدیو با فرمت ${fileExtension.toUpperCase()} ضبط شد.`;
        };
        mediaRecorder.ondataavailable = (event) => { if (event.data && event.data.size > 0) recordedBlobs.push(event.data); };
        mediaRecorder.start();
        console.log('Video MediaRecorder started with mimeType:', chosenMimeType, mediaRecorder);
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state !== "inactive") {
            mediaRecorder.stop();
            stopRecordingBtn.style.display = 'none';
            retakeVideoBtn.style.display = 'inline-block';
            videoStatus.textContent = 'ضبط ویدیو متوقف شد.';
        }
    }

    function retakeVideo() {
        recordedBlobs = [];
        videoFileField.value = '';
        recordedVideoPlayback.style.display = 'none'; recordedVideoPlayback.src = '';
        liveVideoFeed.style.display = 'block';
        startRecordingBtn.style.display = 'inline-block';
        stopRecordingBtn.style.display = 'none';
        retakeVideoBtn.style.display = 'none';
        videoStatus.textContent = 'برای ضبط مجدد ویدیو، روی "شروع ضبط ویدیو" کلیک کنید.';
        if (!videoStream || !videoStream.active) initVideoCamera();
    }

    // --- Event Listeners ---
    if (startSelfieCameraBtn) startSelfieCameraBtn.addEventListener('click', initSelfieCamera);
    if (captureSelfieBtn) captureSelfieBtn.addEventListener('click', captureSelfie);
    if (retakeSelfieBtn) retakeSelfieBtn.addEventListener('click', retakeSelfie);

    if (startVideoCameraBtn) startVideoCameraBtn.addEventListener('click', initVideoCamera);
    if (startRecordingBtn) startRecordingBtn.addEventListener('click', startRecording);
    if (stopRecordingBtn) stopRecordingBtn.addEventListener('click', stopRecording);
    if (retakeVideoBtn) retakeVideoBtn.addEventListener('click', retakeVideo);
    
    // Disable buttons if textToRead is not available
    if (!textToReadContent || textToReadContent.textContent.trim() === '') {
        if (startSelfieCameraBtn) startSelfieCameraBtn.disabled = true;
        if (startVideoCameraBtn) startVideoCameraBtn.disabled = true;
        if (submitFormBtn) submitFormBtn.disabled = true;
    }


    // Form submission check
    const form = document.getElementById('farashenasaForm');
    if(form && submitFormBtn){
        form.addEventListener('submit', function(event){
            let hasError = false;
            if(selfieFileField.files.length === 0){
                selfieError.textContent = 'لطفاً ابتدا عکس سلفی را بگیرید.';
                hasError = true;
            } else {
                selfieError.textContent = '';
            }
            if(videoFileField.files.length === 0){
                videoError.textContent = 'لطفاً ابتدا ویدیو را ضبط کنید.';
                hasError = true;
            } else {
                videoError.textContent = '';
            }

            if(hasError){
                event.preventDefault(); 
                return false;
            }
            
            submitFormBtn.disabled = true;
            selfieStatus.textContent = ''; // Clear status messages
            videoStatus.textContent = 'در حال ارسال اطلاعات...';
        });
    }

</script>
</body>
</html>

```

نکات مهم

uniqueKey: این کلید باید برای هر تراکنش احراز هویت جدید، یکتا باشد و در هر دو مرحله (دریافت متن و ارسال داده‌ها) یکسان استفاده شود.

مدیریت خطا: همیشه پاسخ‌های دریافتی از متدهای initiateLivenessCheck و verifyLiveness را بررسی کنید ($response->isSuccessful() و $response->getMessage() و $response->getData()).

فایل‌ها: مطمئن شوید که فایل‌های سلفی و ویدیو به درستی توسط کاربر تهیه شده و با فرمت و حجم مجاز ارسال می‌شوند.

تجربه کاربری: به کاربر بازخورد مناسب در طول فرآیند (مثلاً نمایش وضعیت ضبط، پیام‌های خطا) ارائه دهید.