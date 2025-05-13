# مجموعه اعتبارسنجی ایرانی برای لاراول (Iranian Validation Suite for Laravel)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/saman9074/iranian-validation-suite.svg?style=flat-square)](https://packagist.org/packages/saman9074/iranian-validation-suite)
[![Total Downloads](https://img.shields.io/packagist/dt/saman9074/iranian-validation-suite.svg?style=flat-square)](https://packagist.org/packages/saman9074/iranian-validation-suite)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)

این پکیج لاراول، مجموعه‌ای از قوانین اعتبارسنجی آفلاین برای داده‌های رایج ایرانی و همچنین ابزارهای کمکی مرتبط را فراهم می‌کند. هدف اصلی این پکیج، ساده‌سازی فرآیند اعتبارسنجی داده‌های ایرانی در پروژه‌های لاراول و کمک به توسعه‌دهندگان برای اطمینان از صحت داده‌های ورودی است.

**فاز فعلی: اعتبارسنجی آفلاین**

در حال حاضر، این پکیج بر روی ارائه قوانین اعتبارسنجی آفلاین تمرکز دارد که نیازی به اتصال به سرویس‌های آنلاین ندارند.

## ویژگی‌های اصلی (فاز اول)

* مجموعه‌ای از قوانین اعتبارسنجی آماده برای:
    * کد ملی ایران (`iranian_national_id`)
    * شماره کارت بانکی (`iranian_bank_card`) - بر اساس الگوریتم لان
    * شماره شبا (`iranian_sheba`) - بر اساس استاندارد IBAN و Mod 97-10
    * کد پستی ۱۰ رقمی ایران (`iranian_postal_code`)
    * شماره موبایل ایران (`iranian_mobile_number`) - با بررسی پیش‌شماره‌های معتبر
* پیام‌های خطای قابل ترجمه (فارسی و انگلیسی به صورت پیش‌فرض).
* یک Facade کمکی (`IranianValidator`) برای اعتبارسنجی مستقیم مقادیر در کد.
* نصب و راه‌اندازی آسان با استفاده از Composer و قابلیت auto-discovery لاراول.
* سازگار با لاراول ۱۰ و ۱۱ و ۱۲ (نیازمند PHP 8.2 به بالا).

## ویژگی‌های اصلی (فاز دوم)
* به زودی در فاز دوم پروژه با اتصال به وب سرویس های شرکت های معتبر امکان استعلام های مختلف به بسته اضافه می گردد.
    * از جمله میتوان به سرویس های زیر اشاره نمود:
        * استعلام کد ملی و شماره همراه (سامانه شاهکار)
        * استعلام شماره کارت، تاریخ تولد و کد ملی
        * استعلام کد ملی و شماره حساب یا شبا
        * انطباق ویدئو و تصویر کارت ملی
        * و....

## نصب
روش اول 
(در حال حاضر به علت تکمیل نبودن و عدم انتشار استفاده از این روش امکان پذیر نیست لطفا از روش دوم استفاده کنید)
برای نصب پکیج از طریق Composer، دستور زیر را اجرا کنید:

```bash
composer require saman9074/iranian-validation-suite
```

روش دوم
اگر در حال حاضر امکان استفاده از روش اول وجود ندارد یا می خواهید در توسعه این بسته کمک نمایید. لطفا از این روش برای نصب استفاده نمایید.

در ابتدا کد زیر را در فایل composer.json قرار دهید: 
```bash
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/saman9074/iranian-validation-suite.git"
    }
],
```

همچنین در قسمت require-dev در فایل  composer.json خط زیر را اضافه نمایید.

```bash
"saman9074/iranian-validation-suite" : "@dev"
```

سپس دستور زیر را در محیط ترمینال وارد نمایید.

```bash
composer update
or
composer update saman9074/iranian-validation-suite
```
این پکیج از قابلیت auto-discovery لاراول پشتیبانی می‌کند، بنابراین Service Provider و Facade ها به طور خودکار ثبت می‌شوند.

## راه‌اندازی

# ۱. انتشار فایل‌های زبان (اختیاری):

اگر می‌خواهید پیام‌های پیش‌فرض اعتبارسنجی را سفارشی کنید، می‌توانید فایل‌های زبان پکیج را منتشر کنید:
```bash
php artisan vendor:publish --provider="Saman9074\IranianValidationSuite\IranianValidationSuiteServiceProvider" --tag="iranian-validation-suite-lang"
```
فایل‌های زبان در مسیر resources/lang/vendor/iranian-validation-suite (یا lang/vendor/iranian-validation-suite در نسخه‌های جدیدتر لاراول) در پروژه شما کپی خواهند شد.

# ۲. انتشار فایل پیکربندی (اختیاری برای فاز اول):

فایل پیکربندی این پکیج (iranian-validation-suite.php) در حال حاضر بیشتر برای تنظیمات مربوط به فاز دوم (خدمات KYC آنلاین) کاربرد دارد. با این حال، اگر می‌خواهید آن را منتشر کنید:
```bash
php artisan vendor:publish --provider="Saman9074\IranianValidationSuite\IranianValidationSuiteServiceProvider" --tag="iranian-validation-suite-config"
```
فایل پیکربندی در مسیر config/iranian-validation-suite.php در پروژه شما کپی خواهد شد.

## نحوه استفاده
استفاده از قوانین اعتبارسنجی در Validator لاراول

شما می‌توانید از این قوانین اعتبارسنجی مانند سایر قوانین داخلی لاراول در آرایه $rules کنترلرها یا Form Request های خود استفاده کنید:
```bash
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// ...

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'national_id' => 'required|iranian_national_id',
        'card_number' => 'nullable|iranian_bank_card',
        'sheba' => 'required|iranian_sheba',
        'postal_code' => 'required|iranian_postal_code',
        'mobile' => 'required|iranian_mobile_number',
        'home_phone' => 'nullable|iranian_landline_phone',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // ادامه پردازش داده‌های معتبر
}
```
# استفاده از Facade برای اعتبارسنجی مستقیم

پکیج یک Facade به نام IranianValidator ارائه می‌دهد که می‌توانید از آن برای بررسی مستقیم اعتبار یک مقدار استفاده کنید:
```bash
use Saman9074\IranianValidationSuite\Facades\IranianValidator;

// ...

$nationalId = '0012345678';
if (IranianValidator::isNationalIdValid($nationalId)) {
    // کد ملی معتبر است
} else {
    // کد ملی نامعتبر است
}

$mobile = '09121112233';
if (IranianValidator::isMobileNumberValid($mobile)) {
    // شماره موبایل معتبر است
}

// سایر متدهای موجود در Facade:
// IranianValidator::isBankCardValid($value)
// IranianValidator::isShebaValid($value)
// IranianValidator::isPostalCodeValid($value)
// IranianValidator::isLandlinePhoneNumberValid($value)
```
## لیست قوانین اعتبارسنجی آفلاین

   * iranian_national_id:
       * اعتبارسنجی کد ملی ۱۰ رقمی ایران.
   * iranian_bank_card:
       * اعتبارسنجی شماره کارت بانکی ۱۶ رقمی ایران (الگوریتم لان).
   * iranian_sheba:
       * اعتبارسنجی شماره شبا ۲۴ رقمی (بدون IR) یا ۲۶ کاراکتری (با IR) ایران.
   * iranian_postal_code:
       * اعتبارسنجی کد پستی ۱۰ رقمی ایران (با یا بدون خط تیره).
            * کدهای تماماً صفر، تماماً یکسان، 1234567890 و 9876543210 نامعتبر در نظر گرفته می‌شوند.
   * iranian_mobile_number:
       *    اعتبارسنجی شماره موبایل ایران (۱۱ رقمی با پیش‌شماره 09 یا ۱۰ رقمی با پیش‌شماره 9). پیش‌شماره‌های رایج اپراتورها بررسی می‌شوند.

## بومی‌سازی (Localization)

پیام‌های خطا به طور پیش‌فرض برای زبان‌های فارسی (fa) و انگلیسی (en) ارائه شده‌اند. شما می‌توانید با انتشار فایل‌های زبان (همانطور که در بخش راه‌اندازی توضیح داده شد) این پیام‌ها را ویرایش کرده یا زبان‌های دیگری را اضافه کنید.
## مشارکت

از مشارکت شما در توسعه این پکیج استقبال میگردد. جهت همکاری لطفا به آدرس ایمیل abdi9074@gmail.com پیام خود را ارسال کنید.