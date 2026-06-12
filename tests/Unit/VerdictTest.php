<?php

namespace Tests\Unit;

use App\Enums\Verdict;
use PHPUnit\Framework\TestCase;

class VerdictTest extends TestCase
{
    public function test_zero_malicious_is_safe(): void
    {
        $this->assertSame(Verdict::Safe, Verdict::fromStats(0, 70));
    }

    public function test_one_malicious_out_of_seventy_is_suspicious(): void
    {
        $this->assertSame(Verdict::Suspicious, Verdict::fromStats(1, 70));
    }

    public function test_three_malicious_is_still_suspicious(): void
    {
        $this->assertSame(Verdict::Suspicious, Verdict::fromStats(3, 70));
    }

    public function test_four_malicious_with_low_ratio_is_malicious(): void
    {
        $this->assertSame(Verdict::Malicious, Verdict::fromStats(4, 70));
    }

    public function test_count_threshold_short_circuits_high_ratio(): void
    {
        // 2/5 = 0.4 ratio is technically "high" but the low malicious count (≤ 3)
        // keeps it in the Suspicious bucket. The count check fires first.
        $this->assertSame(Verdict::Suspicious, Verdict::fromStats(2, 5));
    }

    public function test_zero_total_with_zero_malicious_is_safe(): void
    {
        $this->assertSame(Verdict::Safe, Verdict::fromStats(0, 0));
    }

    public function test_zero_total_with_malicious_count_is_malicious(): void
    {
        $this->assertSame(Verdict::Malicious, Verdict::fromStats(2, 0));
    }

    public function test_label_and_color_mapping(): void
    {
        $this->assertSame('Aman', Verdict::Safe->label());
        $this->assertSame('Waspada', Verdict::Suspicious->label());
        $this->assertSame('Bahaya', Verdict::Malicious->label());

        $this->assertSame('green', Verdict::Safe->color());
        $this->assertSame('yellow', Verdict::Suspicious->color());
        $this->assertSame('red', Verdict::Malicious->color());
    }
}
