<?php

declare(strict_types=1);

namespace Parvion\Msg91\Tests\Unit;

use InvalidArgumentException;
use Parvion\Msg91\Support\PhoneNumberFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PhoneNumberFormatter.
 */
class PhoneNumberFormatterTest extends TestCase
{
    private PhoneNumberFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new PhoneNumberFormatter('91');
    }

    // ── format() ───────────────────────────────────────────────────────────────

    public function test_format_prepends_country_code_to_local_number(): void
    {
        $this->assertSame('919876543210', $this->formatter->format('9876543210'));
    }

    public function test_format_strips_plus_sign(): void
    {
        $this->assertSame('919876543210', $this->formatter->format('+919876543210'));
    }

    public function test_format_strips_leading_zero(): void
    {
        $this->assertSame('919876543210', $this->formatter->format('09876543210'));
    }

    public function test_format_passthrough_already_formatted(): void
    {
        $this->assertSame('919876543210', $this->formatter->format('919876543210'));
    }

    public function test_format_with_dashes_and_spaces(): void
    {
        $this->assertSame('919876543210', $this->formatter->format('+91-987-654-3210'));
    }

    public function test_format_with_custom_country_code(): void
    {
        $this->assertSame('14155551234', $this->formatter->format('4155551234', '1'));
    }

    public function test_format_throws_on_too_short_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid format');

        $this->formatter->format('123');
    }

    // ── tryFormat() ────────────────────────────────────────────────────────────

    public function test_try_format_returns_null_on_invalid(): void
    {
        $this->assertNull($this->formatter->tryFormat('123'));
    }

    public function test_try_format_returns_formatted_on_valid(): void
    {
        $this->assertSame('919876543210', $this->formatter->tryFormat('9876543210'));
    }

    // ── isValid() ──────────────────────────────────────────────────────────────

    public function test_is_valid_returns_true_for_valid_number(): void
    {
        $this->assertTrue($this->formatter->isValid('9876543210'));
    }

    public function test_is_valid_returns_false_for_invalid_number(): void
    {
        $this->assertFalse($this->formatter->isValid('12'));
    }

    // ── stripCountryCode() ─────────────────────────────────────────────────────

    public function test_strip_country_code(): void
    {
        $this->assertSame('9876543210', $this->formatter->stripCountryCode('919876543210'));
    }

    public function test_strip_country_code_from_plus_format(): void
    {
        $this->assertSame('9876543210', $this->formatter->stripCountryCode('+919876543210'));
    }

    // ── prependCountryCode() ───────────────────────────────────────────────────

    public function test_prepend_country_code_when_absent(): void
    {
        $this->assertSame('919876543210', $this->formatter->prependCountryCode('9876543210'));
    }

    public function test_prepend_country_code_when_already_present(): void
    {
        $this->assertSame('919876543210', $this->formatter->prependCountryCode('919876543210'));
    }

    // ── formatMany() ───────────────────────────────────────────────────────────

    public function test_format_many_filters_invalid_numbers(): void
    {
        $result = $this->formatter->formatMany([
            '9876543210',
            '12',           // invalid — too short
            '+918765432109',
        ]);

        $this->assertCount(2, $result);
        $this->assertSame('919876543210', $result[0]);
        $this->assertSame('918765432109', $result[1]);
    }

    public function test_format_many_returns_empty_for_all_invalid(): void
    {
        $result = $this->formatter->formatMany(['1', '2', '3']);

        $this->assertSame([], $result);
    }
}
