# parvion/laravel-msg91

[![Tests](https://github.com/parvion/laravel-msg91/actions/workflows/ci.yml/badge.svg)](https://github.com/parvion/laravel-msg91/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/parvion/laravel-msg91.svg)](https://packagist.org/packages/parvion/laravel-msg91)
[![Total Downloads](https://img.shields.io/packagist/dt/parvion/laravel-msg91.svg)](https://packagist.org/packages/parvion/laravel-msg91)
[![PHP Version](https://img.shields.io/packagist/php-v/parvion/laravel-msg91.svg)](https://packagist.org/packages/parvion/laravel-msg91)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Enterprise-grade **MSG91 API wrapper** for Laravel — OTP, SMS, Email & WhatsApp with a clean Facade API, strongly-typed DTOs, Laravel Events, queued Jobs, and a built-in Fake for testing.

---

## Requirements

| Dependency | Version  |
|------------|----------|
| PHP        | ^8.1     |
| Laravel    | ^10.0 \| ^11.0 |

---

## Installation

```bash
composer require parvion/laravel-msg91
```

The package is auto-discovered by Laravel. No manual provider registration required.

### Publish Config

```bash
php artisan vendor:publish --tag=msg91-config
```

### Publish & Run Migration _(optional — for activity logging)_

```bash
php artisan vendor:publish --tag=msg91-migrations
php artisan migrate
```

---

## Configuration

Add the following variables to your `.env` file:

```dotenv
MSG91_AUTH_KEY=your-msg91-auth-key
MSG91_SENDER_ID=SENDER
MSG91_COUNTRY_CODE=91
MSG91_OTP_TEMPLATE_ID=your-template-id

# Optional
MSG91_LOG_REQUESTS=false
MSG91_LOG_ACTIVITY=false
MSG91_LOG_CHANNEL=null
MSG91_RETRY_ATTEMPTS=3
MSG91_THROTTLE_MAX=60
```

---

## Usage

> **Note:** Full usage examples will be added as each phase is completed.

### OTP

```php
use Parvion\Msg91\Facades\Msg91;
use Parvion\Msg91\DTOs\OtpData;

// Send OTP
Msg91::sendOtp(OtpData::fromArray([
    'mobile'      => '919876543210',
    'template_id' => 'your-template-id',
]));

// Verify OTP
Msg91::verifyOtp('919876543210', '123456');

// Resend OTP
Msg91::resendOtp('919876543210');

// Retry via voice call
use Parvion\Msg91\Enums\OtpRetryType;
Msg91::retryOtp('919876543210', OtpRetryType::Voice);
```

### SMS

```php
use Parvion\Msg91\DTOs\SmsData;
use Parvion\Msg91\Enums\SmsRoute;

Msg91::sendSms(SmsData::fromArray([
    'mobile'  => '919876543210',
    'message' => 'Your order has been shipped.',
    'route'   => SmsRoute::Transactional,
]));
```

### Testing

```php
use Parvion\Msg91\Facades\Msg91;

// In your test setUp()
Msg91::fake();

// ... trigger code that calls Msg91 ...

Msg91::assertOtpSent('919876543210');
Msg91::assertSmsSent('919876543210');
Msg91::assertNothingSent();
```

---

## Events

| Event           | Fired When                              |
|-----------------|-----------------------------------------|
| `OtpSent`       | An OTP is successfully sent via MSG91   |
| `MessageFailed` | Any API call fails (all channels)       |

---

## Architecture

```
Msg91 Facade
    └── Msg91 (main class)
            ├── ManagesOtp    (trait)
            ├── ManagesSms    (trait)
            ├── ManagesEmail  (trait)
            ├── ManagesWhatsApp (trait)
            ├── FakesMsg91    (behavior trait)
            ├── WithRetries   (behavior trait)
            ├── ManagesThrottling (behavior trait)
            └── InteractsWithConfig (behavior trait)
                    └── Msg91Client (HTTP layer)
                            └── MSG91 REST API
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for version history.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
