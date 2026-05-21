# Laravel MSG91 Integration

A robust, enterprise-grade, API-first Laravel wrapper for MSG91 (v5). This package is strictly designed for API-driven architectures (like headless CMSs), providing comprehensive coverage of MSG91's OTP, SMS, Email, and WhatsApp endpoints without the bloat of frontend UI components. Supports **Laravel 10, 11, 12, and 13** with **PHP 8.1+**.

## Features
- **API First:** No Livewire, no Blade views. Pure backend logic.
- **Full Coverage:** Send OTPs, SMS (Transactional/Promotional), and Emails natively.
- **Advanced Features:** SMS Scheduling, Cross-channel OTP fallback, Email Validation, and CSV Bulk uploading.
- **Webhooks (DLR):** Native controller to handle MSG91 Delivery Receipts automatically via Laravel Events.
- **Intelligent:** Built-in throttle guards, exponential backoff retries, and phone number standardisation.
- **Testable:** Built-in `Msg91Fake` for painless TDD and PHPUnit testing.

---

## 🚀 Installation

Install the package via composer:

```bash
composer require parvion/laravel-msg91
```

Publish the configuration file (optional, but recommended):

```bash
php artisan vendor:publish --tag="msg91-config"
```

## ⚙️ Configuration (The Full Flow)

The package is designed to be entirely driven by your `.env` file. You don't need to pass templates or sender IDs in your code if you configure them here:

```dotenv
# Core Settings
MSG91_AUTH_KEY="your-real-msg91-auth-key"
MSG91_SENDER_ID="YOURID"
MSG91_COUNTRY_CODE="91"

# Feature Toggles (Turn off channels you don't use)
MSG91_FEATURE_OTP=true
MSG91_FEATURE_SMS=true
MSG91_FEATURE_EMAIL=true
MSG91_FEATURE_WHATSAPP=true

# Defaults
MSG91_OTP_TEMPLATE_ID="your-default-otp-template-id"
MSG91_OTP_LENGTH=6

# Logging
MSG91_LOG_DRIVER="null" # Change to 'log' or 'database' to track all activity
```

## ☎️ Phone Number Formatting (10 vs 12 Digits)

MSG91 requires numbers to include the country code (e.g., `919876543210` for India). 

However, **you can pass either 10-digit or 12-digit numbers to this package.** 
The package uses a built-in `PhoneNumberFormatter` that checks your `MSG91_COUNTRY_CODE` setting:
- If you pass 10 digits (`9876543210`), it automatically prepends your default country code (`919876543210`).
- If you pass 12 digits or use a plus sign (`+919876543210`), it automatically formats it correctly for the API (`919876543210`).

This means you never have to worry about cleaning user input before sending an SMS!

---

## 📱 1. OTP Examples

### Send an OTP
```php
use Parvion\Msg91\Facades\Msg91;
use Parvion\Msg91\DTOs\OtpData;

$response = Msg91::sendOtp(OtpData::fromArray([
    'mobile' => '919876543210', 
    // 'template_id' => '...' (Optional: automatically pulls from config)
]));
```

### Verify an OTP
```php
// Returns the MSG91 array on success, throws an InvalidOtpException on failure
Msg91::verifyOtp('919876543210', '123456');
```

### Resend / Retry / Fallback
```php
// Standard text resend
Msg91::resendOtp('919876543210');

// Retry via Voice Call
use Parvion\Msg91\Enums\OtpRetryType;
Msg91::retryOtp('919876543210', OtpRetryType::Voice);

// Smart Fallback (Attempts SMS, if fails tries WhatsApp, then Email)
Msg91::fallbackOtp('919876543210', ['sms', 'whatsapp', 'email']);
```

### OTP Analytics & Logs
```php
$analytics = Msg91::getOtpAnalytics(now()->subMonth(), now());
$logs      = Msg91::getOtpLogs(now()->subDays(7), now());
```

---

## ✉️ 2. SMS Examples

### Send a Single SMS
```php
use Parvion\Msg91\DTOs\SmsData;
use Parvion\Msg91\Enums\SmsRoute;

Msg91::sendSms(SmsData::fromArray([
    'mobile'  => '919876543210',
    'message' => 'Your order #12345 is confirmed!',
    'route'   => SmsRoute::Transactional,
]));
```

### Dispatch Background Job (Highly Recommended)
Never make your users wait for an HTTP request to finish.
```php
use Parvion\Msg91\Jobs\SendSmsJob;

SendSmsJob::dispatch(SmsData::fromArray([
    'mobile'  => '919876543210',
    'message' => 'Your order is confirmed!',
]));
```

### Schedule SMS for the Future
```php
Msg91::scheduleSms($smsData, now()->addDays(2));
```

### Trigger MSG91 Campaign Flow
```php
// Trigger a visual flow built in the MSG91 dashboard
Msg91::triggerFlow('flow_id_abc123', ['customer_name' => 'John Doe'], '919876543210');
```

### Check Delivery & Analytics
```php
$status    = Msg91::checkDeliveryStatus('msg91-request-id-12345');
$analytics = Msg91::getSmsAnalytics(now()->subMonth(), now());
```

---

## 📧 3. Email Examples

### Send Standard Email
```php
use Parvion\Msg91\DTOs\EmailData;

Msg91::sendEmail(EmailData::fromArray([
    'to'         => ['john@example.com'],
    'subject'    => 'Welcome!',
    'template_id'=> 'email-template-id',
    'variables'  => ['name' => 'John'],
]));
```

### Advanced Email Features
```php
// 1. Strict Deliverability Validation
Msg91::sendEmailWithValidation($emailData);

// 2. Massive CSV Bulk Send
Msg91::sendEmailWithCsv(storage_path('app/contacts.csv'), $emailData);

// 3. Programmatic Template Creation
Msg91::createEmailTemplate('Monthly Newsletter', '<h1>Hello World</h1>');

// 4. Fetch Templates & Logs
$templates = Msg91::getEmailTemplates(['per_page' => 50]);
$logs      = Msg91::getEmailLogs(now()->subWeek(), now());
```

## 🟢 4. WhatsApp Examples

### Send a Standard WhatsApp Message
```php
use Parvion\Msg91\DTOs\WhatsAppData;

Msg91::sendWhatsApp(WhatsAppData::fromArray([
    'mobile'  => '919876543210',
    'message' => 'Hello from WhatsApp!',
]));
```

### Send an Approved WhatsApp Template
```php
Msg91::sendWhatsAppTemplate(WhatsAppData::fromArray([
    'mobile'      => '919876543210',
    'template_id' => 'your_approved_whatsapp_template_id',
    'variables'   => ['name' => 'John'], // Dynamic template variables
]));
```

---

## 🪝 Webhooks (Delivery Receipts - DLR)

Instead of polling MSG91 to see if an SMS or OTP was delivered, let MSG91 tell your application automatically.

**1. Register the route** in your `routes/api.php`:
```php
use Parvion\Msg91\Http\Controllers\Msg91WebhookController;

Route::post('/msg91/webhook', [Msg91WebhookController::class, 'handle']);
```

**2. Listen for the Laravel Events:**
When MSG91 pings your webhook, the package will dispatch native events. Create an Event Listener in your app to handle them:

```php
namespace App\Listeners;

use Parvion\Msg91\Events\MessageDeliveryStatusChanged;

class UpdateMessageStatus
{
    public function handle(MessageDeliveryStatusChanged $event)
    {
        // $event->requestId
        // $event->status ('delivered', 'failed', 'sent')
        // $event->rawPayload (The full JSON from MSG91)
        
        // Example: DB::table('messages')->where('request_id', $event->requestId)->update(['status' => $event->status]);
    }
}
```

---

## 🧪 Testing

The package provides a built-in `Msg91Fake` for flawless TDD.

```php
use Parvion\Msg91\Facades\Msg91;

public function test_user_registration_sends_otp()
{
    Msg91::fake();

    // Trigger your application logic
    $this->post('/api/register', ['phone' => '919876543210']);

    // Assert the exact API call was made, without actually hitting MSG91
    Msg91::assertOtpSent('919876543210');
    Msg91::assertSmsNotSent();
}
```

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
