<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | MSG91 Authentication Key
    |--------------------------------------------------------------------------
    | Your MSG91 Auth Key. Never commit this directly — use the .env variable.
    |
    */
    'auth_key' => env('MSG91_AUTH_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    | The MSG91 REST API base URL. Override only if MSG91 changes their endpoint.
    |
    */
    'base_url' => env('MSG91_BASE_URL', 'https://api.msg91.com/api/v5/'),

    /*
    |--------------------------------------------------------------------------
    | Default Sender ID
    |--------------------------------------------------------------------------
    | The default 6-character Sender ID registered with MSG91.
    | Can be overridden per-request via SmsData.
    |
    */
    'sender_id' => env('MSG91_SENDER_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    | Used by PhoneNumberFormatter when no country code is present in the number.
    | Example: '91' for India, '1' for USA.
    |
    */
    'default_country_code' => env('MSG91_COUNTRY_CODE', '91'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    | Number of seconds before an API request times out.
    |
    */
    'timeout' => (int) env('MSG91_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    | Retry transient failures with exponential back-off.
    | Set attempts to 1 to disable retries.
    |
    */
    'retry' => [
        'attempts'           => (int) env('MSG91_RETRY_ATTEMPTS', 3),
        'sleep_milliseconds' => (int) env('MSG91_RETRY_SLEEP_MS', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttle Configuration
    |--------------------------------------------------------------------------
    | Local rate limiting applied before requests hit the MSG91 API.
    | Uses the default Laravel Cache driver.
    |
    */
    'throttle' => [
        'max_attempts'   => (int) env('MSG91_THROTTLE_MAX', 60),
        'decay_seconds'  => (int) env('MSG91_THROTTLE_DECAY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Driver
    |--------------------------------------------------------------------------
    | Controls how (and whether) MSG91 activity is recorded.
    |
    | Supported drivers:
    |   'null'     - Disable all logging (default, zero overhead)
    |   'log'      - Write to a Laravel log channel only
    |   'database' - Persist to the msg91_logs table only
    |   'stack'    - Both log channel AND database table
    |
    | channel : Which Laravel log channel to write to (null = app default).
    | level   : Log level for channel writes (debug|info|warning|error).
    |
    */
    'logging' => [
        'driver'  => env('MSG91_LOG_DRIVER', 'null'),   // null | log | database | stack
        'channel' => env('MSG91_LOG_CHANNEL', null),
        'level'   => env('MSG91_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    | Granularly enable or disable individual MSG91 channels.
    | Disabled channels will throw a FeatureDisabledException if called.
    |
    */
    'features' => [
        'otp'       => (bool) env('MSG91_FEATURE_OTP', true),
        'sms'       => (bool) env('MSG91_FEATURE_SMS', true),
        'email'     => (bool) env('MSG91_FEATURE_EMAIL', true),
        'whatsapp'  => (bool) env('MSG91_FEATURE_WHATSAPP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire Integration
    |--------------------------------------------------------------------------
    | register_components : Auto-register built-in Livewire components.
    | otp_component_name  : Blade tag name for the OTP verification component.
    |                       e.g. <livewire:msg91-otp-verification />
    | auto_send_on_mount  : If true, sendOtp() is called automatically when
    |                       the OTP component mounts.
    | resend_cooldown     : Seconds the user must wait before requesting a
    |                       resend (enforced in the Livewire component).
    |
    */
    'livewire' => [
        'register_components' => (bool) env('MSG91_LIVEWIRE_COMPONENTS', true),
        'otp_component_name'  => 'msg91-otp-verification',
        'auto_send_on_mount'  => (bool) env('MSG91_OTP_AUTO_SEND', true),
        'resend_cooldown'     => (int) env('MSG91_OTP_RESEND_COOLDOWN', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Defaults
    |--------------------------------------------------------------------------
    | Default OTP template ID and configuration.
    |
    */
    'otp' => [
        'template_id' => env('MSG91_OTP_TEMPLATE_ID', ''),
        'otp_length'  => (int) env('MSG91_OTP_LENGTH', 6),
        'otp_expiry'  => (int) env('MSG91_OTP_EXPIRY', 10), // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Defaults
    |--------------------------------------------------------------------------
    |
    */
    'sms' => [
        'route'       => env('MSG91_SMS_ROUTE', 4), // 4 = transactional
        'unicode'     => (bool) env('MSG91_SMS_UNICODE', false),
        'flash'       => (bool) env('MSG91_SMS_FLASH', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    | Queue connection and name used by SendSmsJob.
    | Set to null to use the default queue connection.
    |
    */
    'queue' => [
        'connection' => env('MSG91_QUEUE_CONNECTION', null),
        'queue'      => env('MSG91_QUEUE_NAME', 'default'),
    ],

];
