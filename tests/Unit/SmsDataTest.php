<?php

declare(strict_types=1);

namespace Parvion\Msg91\Tests\Unit;

use InvalidArgumentException;
use Parvion\Msg91\DTOs\SmsData;
use Parvion\Msg91\Enums\SmsRoute;
use Parvion\Msg91\Tests\TestCase;

/**
 * Unit tests for SmsData DTO.
 */
class SmsDataTest extends TestCase
{
    public function test_constructor_creates_valid_instance(): void
    {
        $sms = new SmsData(
            mobile:  '919876543210',
            message: 'Hello World',
        );

        $this->assertSame('919876543210', $sms->mobile);
        $this->assertSame('Hello World', $sms->message);
        $this->assertSame(SmsRoute::Transactional, $sms->route);
        $this->assertNull($sms->senderId);
        $this->assertFalse($sms->unicode);
        $this->assertFalse($sms->flash);
    }

    public function test_from_array_with_route_enum(): void
    {
        $sms = new SmsData(
            mobile:  '919876543210',
            message: 'Promo offer!',
            route:   SmsRoute::Promotional,
        );

        $this->assertSame(SmsRoute::Promotional, $sms->route);
    }

    public function test_from_array_with_route_int(): void
    {
        $sms = new SmsData(
            mobile:  '919876543210',
            message: 'International',
            route:   SmsRoute::International,
        );

        $this->assertSame(SmsRoute::International, $sms->route);
    }

    public function test_is_bulk_returns_true_for_array_mobile(): void
    {
        $sms = new SmsData(
            mobile:  ['919876543210', '918765432109'],
            message: 'Bulk',
        );

        $this->assertTrue($sms->isBulk());
    }

    public function test_is_bulk_returns_false_for_string_mobile(): void
    {
        $sms = new SmsData(
            mobile:  '919876543210',
            message: 'Single',
        );

        $this->assertFalse($sms->isBulk());
    }

    public function test_get_recipients_always_returns_array(): void
    {
        $sms = new SmsData(
            mobile:  '919876543210',
            message: 'Test',
        );

        $this->assertSame(['919876543210'], $sms->getRecipients());
    }

    public function test_throws_on_empty_mobile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('mobile');

        new SmsData(mobile: '', message: 'Test');
    }

    public function test_throws_on_empty_message(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('message');

        new SmsData(mobile: '919876543210', message: '');
    }

    public function test_to_array_generates_sms_array_with_message(): void
    {
        $sms = new SmsData(
            mobile:    '919876543210',
            message:   'Hello {{name}}',
            route:     SmsRoute::Transactional,
            variables: [['name' => 'Alice']],
        );

        $payload = $sms->toArray();

        $this->assertSame(4, $payload['route']);
        $this->assertArrayHasKey('sms', $payload);
        $this->assertCount(1, $payload['sms']);
        $this->assertSame('Hello Alice', $payload['sms'][0]['message']);
    }
}
