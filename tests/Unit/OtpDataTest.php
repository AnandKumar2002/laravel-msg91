<?php

declare(strict_types=1);

namespace Parvion\Msg91\Tests\Unit;

use InvalidArgumentException;
use Parvion\Msg91\DTOs\OtpData;
use Parvion\Msg91\Tests\TestCase;

/**
 * Unit tests for OtpData DTO.
 */
class OtpDataTest extends TestCase
{
    public function test_constructor_creates_valid_instance(): void
    {
        $otp = new OtpData(
            mobile:     '919876543210',
            templateId: 'tpl_123',
            otpLength:  6,
            otpExpiry:  10,
        );

        $this->assertSame('919876543210', $otp->mobile);
        $this->assertSame('tpl_123', $otp->templateId);
        $this->assertSame(6, $otp->otpLength);
        $this->assertSame(10, $otp->otpExpiry);
        $this->assertNull($otp->otp);
        $this->assertSame([], $otp->variables);
    }

    public function test_from_array_creates_instance_with_defaults(): void
    {
        $otp = OtpData::fromArray([
            'mobile'      => '919876543210',
            'template_id' => 'tpl_abc',
        ]);

        $this->assertSame('919876543210', $otp->mobile);
        $this->assertSame('tpl_abc', $otp->templateId);
        $this->assertSame(6, $otp->otpLength);
    }

    public function test_from_array_with_all_fields(): void
    {
        $otp = OtpData::fromArray([
            'mobile'      => '919876543210',
            'template_id' => 'tpl_abc',
            'otp_length'  => 4,
            'otp_expiry'  => 5,
            'otp'         => '1234',
            'variables'   => ['name' => 'Alice'],
        ]);

        $this->assertSame(4, $otp->otpLength);
        $this->assertSame(5, $otp->otpExpiry);
        $this->assertSame('1234', $otp->otp);
        $this->assertSame(['name' => 'Alice'], $otp->variables);
    }

    public function test_to_array_generates_correct_payload(): void
    {
        $otp = new OtpData(
            mobile:     '919876543210',
            templateId: 'tpl_123',
            otpLength:  6,
            otpExpiry:  10,
            otp:        '654321',
        );

        $payload = $otp->toArray();

        $this->assertSame('919876543210', $payload['mobile']);
        $this->assertSame('tpl_123', $payload['template_id']);
        $this->assertSame(6, $payload['otp_length']);
        $this->assertSame(10, $payload['otp_expiry']);
        $this->assertSame('654321', $payload['otp']);
    }

    public function test_to_array_excludes_null_otp(): void
    {
        $otp = new OtpData(
            mobile:     '919876543210',
            templateId: 'tpl_123',
        );

        $this->assertArrayNotHasKey('otp', $otp->toArray());
    }

    public function test_to_array_includes_extra_param_for_variables(): void
    {
        $otp = new OtpData(
            mobile:     '919876543210',
            templateId: 'tpl_123',
            variables:  ['name' => 'Bob'],
        );

        $payload = $otp->toArray();
        $this->assertArrayHasKey('extra_param', $payload);
        $this->assertSame('{"name":"Bob"}', $payload['extra_param']);
    }

    public function test_throws_on_empty_mobile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('mobile');

        new OtpData(mobile: '', templateId: 'tpl_123');
    }

    public function test_throws_on_empty_template_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('template_id');

        new OtpData(mobile: '919876543210', templateId: '');
    }

    public function test_throws_on_invalid_otp_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('otp_length');

        new OtpData(mobile: '919876543210', templateId: 'tpl_123', otpLength: 3);
    }
}
