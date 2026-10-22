<?php
use PHPUnit\Framework\TestCase;
use Typecho\Date;

final class DateTest extends TestCase
{
    protected function tearDown(): void
    {
        // Date keeps static state; reset so tests stay order-independent
        Date::$timezoneOffset = 0;
        Date::$serverTimezoneOffset = 0;
    }

    public function testFormatAppliesTimezoneOffset(): void
    {
        Date::$timezoneOffset = 8 * 3600;   // Asia/Shanghai
        Date::$serverTimezoneOffset = 0;    // bootstrap pins UTC

        $date = new Date(1700000000);       // 2023-11-14 22:13:20 UTC
        $this->assertSame('2023-11-15 06:13:20', $date->format('Y-m-d H:i:s'));
        $this->assertSame(['2023', '11', '15'], [$date->year, $date->month, $date->day]);
    }
}
