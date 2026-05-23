<?php

declare(strict_types=1);

namespace Parvion\Msg91\Tests\Unit;

use Parvion\Msg91\Logging\LogDriverManager;
use Parvion\Msg91\Tests\TestCase;

/**
 * Unit tests for LogDriverManager.
 */
class LogDriverManagerTest extends TestCase
{
    private LogDriverManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make(LogDriverManager::class);
    }

    public function test_manager_can_be_resolved_from_container(): void
    {
        $this->assertInstanceOf(LogDriverManager::class, $this->manager);
    }

    public function test_log_success_does_not_throw_with_null_driver(): void
    {
        // Default driver is 'null' — should silently discard the log
        $this->app['config']->set('msg91.logging.driver', 'null');

        $this->manager->logSuccess(
            channel: 'otp',
            action: 'send_otp',
            recipient: '919876543210',
            request: ['mobile' => '919876543210'],
            response: ['type' => 'success'],
            httpStatus: 200,
            durationMs: 150,
        );

        // No exception = pass
        $this->assertTrue(true);
    }

    public function test_log_failure_does_not_throw_with_null_driver(): void
    {
        $this->app['config']->set('msg91.logging.driver', 'null');

        $this->manager->logFailure(
            channel: 'sms',
            action: 'send_sms',
            recipient: '919876543210',
            request: ['mobile' => '919876543210'],
            exception: new \RuntimeException('Connection timeout'),
            httpStatus: null,
            durationMs: 5000,
        );

        $this->assertTrue(true);
    }

    public function test_log_success_with_channel_driver(): void
    {
        $this->app['config']->set('msg91.logging.driver', 'log');

        // Recreate to pick up new config
        $manager = new LogDriverManager;

        $manager->logSuccess(
            channel: 'otp',
            action: 'verify_otp',
            recipient: '919876543210',
            request: ['mobile' => '919876543210', 'otp' => '123456'],
            response: ['type' => 'success', 'message' => 'OTP verified'],
            httpStatus: 200,
            durationMs: 85,
        );

        $this->assertTrue(true);
    }
}
