# Changelog

All notable changes to `parvion/laravel-msg91` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

---

## [1.0.0] - 2026-05-21

### Added

#### Core Infrastructure
- `Msg91` main class with Facade support and auto-discovery
- `Msg91ServiceProvider` with full container bindings and conditional event registration
- `Msg91Client` HTTP layer with `GET`/`POST`, auth headers, response normalisation, and duration tracking
- `InteractsWithConfig` trait with 17 typed config accessors
- `WithRetries` trait with exponential backoff and retryable/non-retryable classification
- `ManagesThrottling` trait with sliding-window rate limiting via Laravel Cache

#### DTOs & Enums
- `OtpData` — immutable DTO with constructor validation, `fromArray()`, `toArray()`
- `SmsData` — single/bulk SMS with `SmsRoute` enum, variable interpolation, Flow API payload builder
- `EmailData` — single/bulk email with from/reply-to/attachments support
- `WhatsAppData` — text/template/media messages with validation
- `OtpRetryType` enum — `Voice`, `Text`
- `SmsRoute` enum — `Promotional` (1), `Transactional` (4), `International` (7)

#### OTP Service
- `ManagesOtp` trait — `sendOtp()`, `verifyOtp()`, `resendOtp()`, `retryOtp()`
- Feature flag guard, per-mobile throttle, E.164 formatting, retry wrapping
- `InvalidOtpException` with named constructors: `expired()`, `incorrect()`, `alreadyUsed()`, `fromResponse()`

#### SMS Service
- `ManagesSms` trait — `sendSms()`, `sendBulkSms()`, `checkDeliveryStatus()`
- MSG91 Flow API v5 payload builder with batch recipient formatting
- `SendSmsJob` — queueable with 3 retries, linear backoff, Horizon `tags()`/`displayName()`

#### Email Service
- `ManagesEmail` trait — `sendEmail()`, `sendBulkEmail()` with recipient override

#### WhatsApp Service
- `ManagesWhatsApp` trait — `sendWhatsApp()`, `sendWhatsAppTemplate()` with template_id validation

#### Livewire Integration
- `OtpVerification` Livewire component — 3-state UI (send → verify → success)
- `HandlesOtpVerification` trait — 7 actions: send, verify, resend, retryVoice, autoSend, tickCountdown, reset
- Polished Blade view with CSS custom properties, automatic dark mode, Alpine.js countdown, full ARIA accessibility

#### Events
- `OtpSent` event — readonly `OtpData`, response, formatted mobile, UTC timestamp
- `MessageFailed` event — `summary()`, `isRateLimited()`, `isFeatureDisabled()` helpers

#### Logging System
- `LogDriverManager` — factory with `null`/`log`/`database`/`stack` driver resolution
- `NullLogDriver` — zero overhead (default)
- `ChannelLogDriver` — structured context logging with configurable channel/level and payload truncation
- `DatabaseLogDriver` — `msg91_logs` table insert with graceful `QueryException` handling
- `StackLogDriver` — fault-isolated fan-out to multiple drivers
- `LogMsg91Activity` listener — handles `OtpSent` + `MessageFailed` events
- Conditional event→listener registration (no overhead when driver is `null`)

#### Exceptions
- `Msg91ApiException` — `fromResponse()` factory, `getStatusCode()`, `getResponseBody()`
- `Msg91RateLimitException` — `fromResponse()`, `localThrottle()`, `getRetryAfter()`
- `FeatureDisabledException` — `make()` with `.env` hint in message

#### Testing
- `Msg91Fake` — in-memory call store for all 6 channels with 18 assertion methods
- `FakesMsg91` trait — `Msg91::fake()` swaps 3 container bindings in one call
- 64 tests, 134 assertions across 7 test suites

#### Support
- `PhoneNumberFormatter` — `format()`, `tryFormat()`, `isValid()`, `formatMany()`, E.164 rules
- `Msg91Logger` — `success()`/`failure()` wrappers with recursive sensitive-field masking
- Publishable config, views, and migration stubs

[Unreleased]: https://github.com/parvion/laravel-msg91/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/parvion/laravel-msg91/releases/tag/v1.0.0
