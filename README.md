# Laravel MSG91 Integration

A robust, enterprise-grade, API-first Laravel wrapper for MSG91 (v5). This package is strictly designed for API-driven architectures (like headless CMSs), providing comprehensive coverage of MSG91's OTP, SMS, Email, and WhatsApp endpoints without the bloat of frontend UI components. Supports **Laravel 10, 11, 12, and 13** with **PHP 8.1+**.

---

## 📑 Table of Contents

1. [🚀 Installation](#-installation)
2. [⚙️ Configuration & Logging](#️-configuration--logging)
3. [☎️ Phone Number Formatting](#️-phone-number-formatting)
4. [📱 OTP Features](#-otp-features)
   - [Send an OTP](#send-an-otp)
   - [Verify an OTP](#verify-an-otp)
   - [Resend & Retry (Voice/Text)](#resend--retry)
   - [Smart Fallback](#smart-fallback)
   - [Analytics & Logs](#otp-analytics--logs)
5. [✉️ SMS Features](#️-sms-features)
   - [Send a Single SMS](#send-a-single-sms)
   - [Send Bulk SMS](#send-bulk-sms)
   - [Background Queue Jobs](#dispatch-background-job-highly-recommended)
   - [Schedule SMS](#schedule-sms-for-the-future)
   - [Trigger Campaign Flow](#trigger-msg91-campaign-flow)
   - [Check Delivery & Analytics](#sms-delivery-status--analytics)
6. [📧 Email Features](#-email-features)
   - [Send a Single Email](#send-a-single-email)
   - [Send Bulk Email](#send-bulk-email)
   - [Strict Validation](#email-with-strict-validation)
   - [Send via CSV Upload](#massive-csv-bulk-send)
   - [Programmatic Templates](#create--fetch-templates)
   - [Email Logs](#email-logs)
7. [🟢 WhatsApp Features](#-whatsapp-features)
   - [Standard Text Message](#send-a-standard-whatsapp-message)
   - [Template Messages](#send-an-approved-whatsapp-template)
8. [🪝 Webhooks (DLR)](#-webhooks-delivery-receipts---dlr)
9. [🧪 Testing (Fakes)](#-testing)

---

## 🚀 Installation

Install the package via composer:

```bash
composer require parvion/laravel-msg91
```

Publish the configuration file (optional, but highly recommended):

```bash
php artisan vendor:publish --tag="msg91-config"
```

Publish the database migration for Activity Logging (Optional):

```bash
php artisan vendor:publish --tag="msg91-migrations"
php artisan migrate
```

---

## ⚙️ Configuration & Logging

The package is designed to be entirely driven by your `.env` file. You don't need to pass templates or sender IDs in your code if you configure them here:

```dotenv
# Core Settings
MSG91_AUTH_KEY="your-real-msg91-auth-key"
MSG91_SENDER_ID="YOURID"
MSG91_COUNTRY_CODE="91"

# Feature Toggles (Turn off channels you don't use to save memory)
MSG91_FEATURE_OTP=true
MSG91_FEATURE_SMS=true
MSG91_FEATURE_EMAIL=true
MSG91_FEATURE_WHATSAPP=true

# Logging (Database & File)
MSG91_LOG_DRIVER="database" # Options: null, log, database, stack

# Granular Logging Control (Only log the channels you want!)
MSG91_LOG_OTP=true
MSG91_LOG_SMS=false     # Disable SMS logs to save database space
MSG91_LOG_EMAIL=true
MSG91_LOG_WHATSAPP=true
```

---

## ☎️ Phone Number Formatting

MSG91 requires numbers to include the country code (e.g., `919876543210` for India). 

However, **you can pass either 10-digit or 12-digit numbers to this package.** 
The built-in formatter automatically checks your `MSG91_COUNTRY_CODE` setting:
- Pass 10 digits (`9876543210`) -> Auto-prepends country code (`919876543210`).
- Pass 12 digits or plus sign (`+919876543210`) -> Formatted correctly for the API (`919876543210`).

---

## 📱 OTP Features

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
use Parvion\Msg91\Exceptions\InvalidOtpException;

try {
    Msg91::verifyOtp('919876543210', '123456');
} catch (InvalidOtpException $e) {
    if ($e->isExpired()) {
        // Tell frontend to show "Resend" button
    } elseif ($e->isIncorrect()) {
        // Tell frontend "Wrong code"
    }
}
```

### Resend & Retry
```php
// Standard text resend
Msg91::resendOtp('919876543210');

// Retry via Voice Call (Perfect for users who didn't receive the text)
use Parvion\Msg91\Enums\OtpRetryType;
Msg91::retryOtp('919876543210', OtpRetryType::Voice);
```

### Smart Fallback
```php
// Attempts SMS, if fails tries WhatsApp, then finally Email automatically!
Msg91::fallbackOtp('919876543210', ['sms', 'whatsapp', 'email']);
```

### OTP Analytics & Logs
```php
$analytics = Msg91::getOtpAnalytics(now()->subMonth(), now());
$logs      = Msg91::getOtpLogs(now()->subDays(7), now());
```

---

## ✉️ SMS Features

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

### Send Bulk SMS
```php
$recipients = ['919876543210', '919876543211', '919876543212'];

Msg91::sendBulkSms($recipients, SmsData::fromArray([
    'message' => 'HUGE SALE! Everything 50% off today only!',
    'route'   => SmsRoute::Promotional,
]));
```

### Dispatch Background Job (Highly Recommended)
Never make your users wait for an HTTP request to finish.
```php
use Parvion\Msg91\Jobs\SendSmsJob;

// Queues the SMS to be sent by a background worker instantly.
SendSmsJob::dispatch(SmsData::fromArray([
    'mobile'  => '919876543210',
    'message' => 'Your order is confirmed!',
]));
```

### Schedule SMS for the Future
```php
// Sends exactly 2 days from now
Msg91::scheduleSms($smsData, now()->addDays(2));
```

### Trigger MSG91 Campaign Flow
```php
// Trigger a visual Flow built in your MSG91 dashboard
Msg91::triggerFlow('flow_id_abc123', ['customer_name' => 'John Doe'], '919876543210');
```

### SMS Delivery Status & Analytics
```php
$status    = Msg91::checkDeliveryStatus('msg91-request-id-12345');
$analytics = Msg91::getSmsAnalytics(now()->subMonth(), now());
```

---

## 📧 Email Features

### Send a Single Email
```php
use Parvion\Msg91\DTOs\EmailData;

Msg91::sendEmail(EmailData::fromArray([
    'to'         => ['john@example.com'],
    'subject'    => 'Welcome to the platform!',
    'template_id'=> 'your-email-template-id',
    'variables'  => ['name' => 'John'], // Dynamic template injection
]));
```

### Send Bulk Email
```php
$emails = ['user1@example.com', 'user2@example.com', 'user3@example.com'];

Msg91::sendBulkEmail($emails, EmailData::fromArray([
    'subject'    => 'System Maintenance Notice',
    'template_id'=> 'maintenance-template-id',
]));
```

### Email with Strict Validation
```php
// Enforces strict deliverability validation on the MSG91 side
Msg91::sendEmailWithValidation($emailData);
```

### Massive CSV Bulk Send
```php
// Send thousands of emails instantly by uploading a CSV
Msg91::sendEmailWithCsv(storage_path('app/contacts.csv'), $emailData);
```

### Create & Fetch Templates
```php
// Create a new HTML template programmatically
Msg91::createEmailTemplate('Monthly Newsletter', '<h1>Hello World</h1>');

// Fetch all existing templates
$templates = Msg91::getEmailTemplates(['per_page' => 50]);
```

### Email Logs
```php
$logs = Msg91::getEmailLogs(now()->subWeek(), now());
```

---

## 🟢 WhatsApp Features

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
    'variables'   => ['name' => 'John'], 
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

The package provides a built-in `Msg91Fake` for flawless TDD. You will never accidentally spend money or hit real API limits while testing.

```php
use Parvion\Msg91\Facades\Msg91;

public function test_user_registration_sends_otp()
{
    // 1. Swap the real API with our in-memory Fake
    Msg91::fake();

    // 2. Trigger your application logic
    $this->postJson('/api/register', ['phone' => '919876543210']);

    // 3. Assert the exact API call was made!
    Msg91::assertOtpSent('919876543210');
    Msg91::assertSmsNotSent();
    Msg91::assertEmailNotSent();
}
```

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
