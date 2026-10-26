<?php

use PHPUnit\Framework\TestCase;
use Typecho\Date;
use Typecho\Timezone;
use Widget\Options;

/** 数字偏移不包含地区信息；只有明确选择的地区才应用其夏令时规则。 */
final class TimezoneMigrationKeepsTheClockTest extends TestCase
{
    private string $serverZone;
    private array $dateState = [];

    protected function setUp(): void
    {
        $this->serverZone = date_default_timezone_get();
        foreach (['timezoneName', 'timezoneOffset', 'serverTimezoneOffset', 'serverTimeStamp'] as $name) {
            $this->dateState[$name] = Date::$$name;
        }
        date_default_timezone_set('UTC');
        Date::$serverTimeStamp = 1625140800;
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->serverZone);
        foreach ($this->dateState as $name => $value) {
            Date::$$name = $value;
        }
    }

    public function testAllLegacyOffsetsKeepTheirClockThroughoutTheYear(): void
    {
        for ($hours = -12; $hours <= 12; $hours++) {
            $offset = $hours * 3600;
            $raw = (string) $offset;
            $options = (new ReflectionClass(Options::class))->newInstanceWithoutConstructor();
            $options->push(['timezone' => $raw]);
            Date::setTimezoneOffset($offset);
            foreach ([2021, 2026] as $year) {
                for ($month = 1; $month <= 12; $month++) {
                    $instant = strtotime(sprintf('%d-%02d-15 12:34:56 UTC', $year, $month));
                    $this->assertSame($offset, Timezone::offsetAt($raw, $instant));
                    $this->assertSame($offset, $options->timezoneOffsetAt($instant));
                    $this->assertSame($instant + $offset, Timezone::wallTimestamp($instant, $raw));
                    $this->assertSame(gmdate('Y-m-d H:i:s', $instant + $offset),
                        (new Date($instant))->format('Y-m-d H:i:s'));
                    $this->assertSame($raw, (string) $options->timezoneName);
                }
            }
        }
    }

    public function testTheSameNumericOffsetDoesNotChooseBetweenPhoenixAndDenver(): void
    {
        $summer = strtotime('2021-07-04 12:00:00 UTC');
        $this->assertSame(-25200, Timezone::offsetAt('-25200', $summer));
        $this->assertSame(-25200, Timezone::offsetAt('America/Phoenix', $summer));
        $this->assertSame(-21600, Timezone::offsetAt('America/Denver', $summer));
    }

    public function testExplicitRegionalChoiceAppliesWinterAndSummerRules(): void
    {
        foreach (['America/Los_Angeles', 'America/Denver', 'America/New_York', 'Australia/Sydney'] as $name) {
            $zone = new DateTimeZone($name);
            $offsets = [];
            foreach (['2021-01-15 12:00:00 UTC', '2021-07-15 12:00:00 UTC'] as $literal) {
                $native = new DateTimeImmutable($literal);
                $expected = $zone->getOffset($native);
                $offsets[] = $expected;
                $this->assertSame($expected, Timezone::offsetAt($name, $native->getTimestamp()));
                $this->assertSame($native->getTimestamp() + $expected,
                    Timezone::wallTimestamp($native->getTimestamp(), $name));
            }
            $this->assertNotSame($offsets[0], $offsets[1], $name . ' must follow the explicit regional choice');
        }
    }
}
