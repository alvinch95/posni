<?php

namespace Tests\Unit\Website;

use App\Services\Website\PhoneNumber;
use PHPUnit\Framework\TestCase;

// Pure function, no framework or database: a plain PHPUnit TestCase.
class PhoneNumberTest extends TestCase
{
    public function test_leading_zero_becomes_country_code_and_punctuation_is_stripped(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa('0812-3456-7890'));
    }

    public function test_plus_and_spaces_are_stripped(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa('+62 812 3456 7890'));
    }

    public function test_leading_eight_gets_country_code(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa('81234567890'));
    }

    public function test_already_normalized_number_is_unchanged(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa('6281234567890'));
    }

    public function test_country_code_followed_by_trunk_zero_is_collapsed(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa('+62 0812-3456-7890'));
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa('62 0812 3456 7890'));
    }

    public function test_international_dialing_prefix_is_dropped(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa('0062 812 3456 7890'));
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa('00 62 0812 3456 7890'));
    }

    public function test_bare_country_code_prefixes_are_rejected(): void
    {
        $this->assertNull(PhoneNumber::normalizeWa('620'));
        $this->assertNull(PhoneNumber::normalizeWa('62'));
        $this->assertNull(PhoneNumber::normalizeWa('00'));
        $this->assertNull(PhoneNumber::normalizeWa('0'));
    }

    public function test_surrounding_whitespace_and_brackets_are_ignored(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalizeWa("  (0812) 3456 7890\n"));
    }

    public function test_garbage_and_empty_input_give_null(): void
    {
        $this->assertNull(PhoneNumber::normalizeWa('12345'));
        $this->assertNull(PhoneNumber::normalizeWa(''));
        $this->assertNull(PhoneNumber::normalizeWa(null));
        $this->assertNull(PhoneNumber::normalizeWa('   '));
        $this->assertNull(PhoneNumber::normalizeWa('abc'));
        $this->assertNull(PhoneNumber::normalizeWa('+1 415 555 0132'));
    }

    public function test_length_boundaries(): void
    {
        // 62 + 8 digits is the shortest accepted, 62 + 13 the longest.
        $this->assertSame('6281234567', PhoneNumber::normalizeWa('081234567'));
        $this->assertNull(PhoneNumber::normalizeWa('08123456'));
        $this->assertSame('628123456789012', PhoneNumber::normalizeWa('08123456789012'));
        $this->assertNull(PhoneNumber::normalizeWa('081234567890123'));
    }
}
