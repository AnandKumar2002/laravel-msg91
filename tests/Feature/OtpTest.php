<?php

declare(strict_types=1);

namespace Parvion\Msg91\Tests\Feature;

use Parvion\Msg91\DTOs\OtpData;
use Parvion\Msg91\Enums\OtpRetryType;
use Parvion\Msg91\Exceptions\FeatureDisabledException;
use Parvion\Msg91\Facades\Msg91;
use Parvion\Msg91\Testing\Msg91Fake;
use Parvion\Msg91\Tests\TestCase;

/**
 * Feature tests for the OTP service (ManagesOtp trait) via Msg91Fake.
 */
class OtpTest extends TestCase
{
    private Msg91Fake $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = Msg91::fake();
    }

    // ── sendOtp ────────────────────────────────────────────────────────────────

    public function test_send_otp_records_the_call(): void
    {
        $otp = OtpData::fromArray([
            'mobile' => '919876543210',
            'template_id' => 'tpl_test_123',
        ]);

        $response = Msg91::sendOtp($otp);

        $this->fake->assertOtpSent('919876543210');
        $this->assertSame('success', $response['type']);
    }

    public function test_send_otp_records_multiple_sends(): void
    {
        Msg91::sendOtp(OtpData::fromArray(['mobile' => '919876543210', 'template_id' => 'tpl_1']));
        Msg91::sendOtp(OtpData::fromArray(['mobile' => '918765432109', 'template_id' => 'tpl_2']));

        $this->fake->assertOtpSentTimes(2);
        $this->fake->assertOtpSent('919876543210');
        $this->fake->assertOtpSent('918765432109');
    }

    public function test_send_otp_not_sent_when_not_called(): void
    {
        $this->fake->assertOtpNotSent();
        $this->fake->assertNothingSent();
    }

    public function test_send_otp_throws_when_feature_disabled(): void
    {
        // Re-create fake with real implementation to test feature guard
        $this->app['config']->set('msg91.features.otp', false);

        // Rebind real Msg91 to test the feature guard
        $this->app->forgetInstance(\Parvion\Msg91\Msg91::class);
        $real = $this->app->make(\Parvion\Msg91\Msg91::class);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('[otp]');

        $real->sendOtp(OtpData::fromArray(['mobile' => '919876543210', 'template_id' => 'tpl_1']));
    }

    // ── verifyOtp ──────────────────────────────────────────────────────────────

    public function test_verify_otp_records_the_call(): void
    {
        $response = Msg91::verifyOtp('919876543210', '123456');

        $this->fake->assertOtpVerified('919876543210');
        $this->assertSame('success', $response['type']);
    }

    // ── resendOtp ──────────────────────────────────────────────────────────────

    public function test_resend_otp_records_the_call(): void
    {
        $response = Msg91::resendOtp('919876543210');

        $this->fake->assertOtpResent('919876543210');
        $this->assertSame('success', $response['type']);
    }

    // ── retryOtp ───────────────────────────────────────────────────────────────

    public function test_retry_otp_via_voice_records_the_call(): void
    {
        $response = Msg91::retryOtp('919876543210', OtpRetryType::Voice);

        $this->fake->assertOtpRetried('919876543210', OtpRetryType::Voice);
        $this->assertSame('success', $response['type']);
    }

    public function test_retry_otp_via_text_records_the_call(): void
    {
        Msg91::retryOtp('919876543210', OtpRetryType::Text);

        $this->fake->assertOtpRetried('919876543210', OtpRetryType::Text);
    }

    // ── Custom response ────────────────────────────────────────────────────────

    public function test_fake_returns_custom_response(): void
    {
        $this->fake->respondWith([
            'type' => 'success',
            'message' => 'OTP sent to 919876543210',
            'data' => ['request_id' => 'req_abc'],
        ]);

        $response = Msg91::sendOtp(OtpData::fromArray([
            'mobile' => '919876543210',
            'template_id' => 'tpl_1',
        ]));

        $this->assertSame('req_abc', $response['data']['request_id']);
    }

    // ── Reset ──────────────────────────────────────────────────────────────────

    public function test_reset_clears_all_recorded_calls(): void
    {
        Msg91::sendOtp(OtpData::fromArray(['mobile' => '919876543210', 'template_id' => 'tpl_1']));
        $this->fake->assertOtpSent();

        $this->fake->reset();
        $this->fake->assertNothingSent();
    }
}
