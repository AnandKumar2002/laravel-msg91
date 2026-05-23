<?php

declare(strict_types=1);

namespace Parvion\Msg91\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Parvion\Msg91\DTOs\SmsData;
use Parvion\Msg91\Enums\SmsRoute;
use Parvion\Msg91\Exceptions\FeatureDisabledException;
use Parvion\Msg91\Facades\Msg91;
use Parvion\Msg91\Jobs\SendSmsJob;
use Parvion\Msg91\Testing\Msg91Fake;
use Parvion\Msg91\Tests\TestCase;

/**
 * Feature tests for the SMS service (ManagesSms trait) via Msg91Fake.
 */
class SmsTest extends TestCase
{
    private Msg91Fake $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = Msg91::fake();
    }

    // ── sendSms ────────────────────────────────────────────────────────────────

    public function test_send_sms_records_the_call(): void
    {
        $sms = SmsData::fromArray([
            'mobile' => '919876543210',
            'message' => 'Your order has shipped.',
        ]);

        $response = Msg91::sendSms($sms);

        $this->fake->assertSmsSent();
        $this->assertSame('success', $response['type']);
    }

    public function test_send_sms_records_with_callback_assertion(): void
    {
        $sms = SmsData::fromArray([
            'mobile' => '919876543210',
            'message' => 'Hello World',
            'route' => SmsRoute::Transactional,
        ]);

        Msg91::sendSms($sms);

        $this->fake->assertSmsSent(function (SmsData $data) {
            return $data->route === SmsRoute::Transactional;
        });
    }

    public function test_send_sms_not_sent_when_not_called(): void
    {
        $this->fake->assertSmsNotSent();
    }

    public function test_send_sms_throws_when_feature_disabled(): void
    {
        $this->app['config']->set('msg91.features.sms', false);
        $this->app->forgetInstance(\Parvion\Msg91\Msg91::class);
        $real = $this->app->make(\Parvion\Msg91\Msg91::class);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('[sms]');

        $real->sendSms(SmsData::fromArray([
            'mobile' => '919876543210',
            'message' => 'test',
        ]));
    }

    // ── sendBulkSms ────────────────────────────────────────────────────────────

    public function test_send_bulk_sms_records_the_call(): void
    {
        $sms = SmsData::fromArray([
            'mobile' => '919876543210', // overridden by $recipients
            'message' => 'Bulk message',
        ]);

        $recipients = ['919876543210', '918765432109', '917654321098'];

        Msg91::sendBulkSms($recipients, $sms);

        $this->fake->assertBulkSmsSent(3);
    }

    // ── checkDeliveryStatus ────────────────────────────────────────────────────

    public function test_check_delivery_status_records_the_call(): void
    {
        Msg91::checkDeliveryStatus('req_abc123');

        $this->fake->assertDeliveryStatusChecked('req_abc123');
    }

    // ── SendSmsJob ─────────────────────────────────────────────────────────────

    public function test_send_sms_job_can_be_dispatched(): void
    {
        Queue::fake();

        $sms = SmsData::fromArray([
            'mobile' => '919876543210',
            'message' => 'Queued message',
        ]);

        SendSmsJob::dispatch($sms);

        Queue::assertPushed(SendSmsJob::class);
    }

    public function test_send_sms_job_with_bulk_recipients(): void
    {
        Queue::fake();

        $sms = SmsData::fromArray([
            'mobile' => '919876543210',
            'message' => 'Bulk queued',
        ]);

        $recipients = ['919876543210', '918765432109'];

        SendSmsJob::dispatch($sms, $recipients);

        Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) {
            return $job->recipients !== null && count($job->recipients) === 2;
        });
    }

    // ── Reset ──────────────────────────────────────────────────────────────────

    public function test_reset_clears_sms_records(): void
    {
        Msg91::sendSms(SmsData::fromArray(['mobile' => '919876543210', 'message' => 'test']));
        $this->fake->assertSmsSent();

        $this->fake->reset();
        $this->fake->assertSmsNotSent();
    }
}
