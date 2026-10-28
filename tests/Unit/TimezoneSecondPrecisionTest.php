<?php

use PHPUnit\Framework\TestCase;
use Typecho\Date;
use Typecho\Timezone;

/** 旧整数秒语义与标准时间序列化的独立行为契约。 */
final class TimezoneSecondPrecisionTest extends TestCase
{
    public function testLegacySecondsAndEscapesAcrossServerCalendars(): void
    {
        $server = date_default_timezone_get();
        $state = [Date::$timezoneOffset, Date::$timezoneName, Date::$serverTimezoneOffset, Date::$serverTimeStamp];
        try {
            foreach (['UTC', 'Europe/Berlin', 'America/New_York'] as $zone) {
                date_default_timezone_set($zone);
                foreach ([12345, -12345, 45, -45, 20700, -12600] as $offset) {
                    Date::setTimezone((string) $offset);
                    foreach ([1609459200, 1616898645, -1] as $instant) {
                        $date = new Date($instant);
                        $wall = gmdate('Y-m-d H:i:s', $instant + $offset);
                        $this->assertSame($offset, Date::$timezoneOffset);
                        $this->assertSame($offset, Timezone::normalizeLegacyOffset($offset));
                        $this->assertSame($offset, Timezone::offsetAt((string) $offset, $instant));
                        $this->assertSame($instant + $offset, Timezone::wallTimestamp($instant, (string) $offset));
                        $this->assertSame($wall, $date->format('Y-m-d H:i:s'));
                        $this->assertSame($instant, Timezone::wallToTimestamp($wall, (string) $offset));
                        $this->assertSame($instant . ' ' . $offset . ' ' . gmdate('B', $instant) . ' 0', $date->format('U Z B I'));
                        if ($offset % 60 === 0) {
                            $this->assertSame($instant, (new DateTimeImmutable($date->format('c')))->getTimestamp());
                            continue;
                        }
                        $slash = chr(92);
                        foreach (['c', 'r', 'Y-m-d H:i:s P', 'Y-m-d\TH:i:sO', 'c | r | U',
                            $slash . $slash . 'P Y-m-d H:i:s'] as $format) {
                            $this->assertSame(gmdate($format, $instant), $date->format($format));
                        }
                        foreach (['c', 'r'] as $format) {
                            $this->assertSame($instant, (new DateTimeImmutable($date->format($format)))->getTimestamp());
                        }
                        foreach ([$slash . 'c ' . $slash . 'r ' . $slash . 'P Y-m-d H:i:s',
                            $slash . $slash . $slash . 'P Y-m-d H:i:s', 'Y-m-d H:i:s' . $slash] as $format) {
                            $this->assertSame(gmdate($format, $instant + $offset), $date->format($format));
                        }
                        $format = 'Y-m-d H:i:s p';
                        $this->assertSame(gmdate($format, PHP_VERSION_ID >= 80000 ? $instant : $instant + $offset), $date->format($format));
                    }
                }
            }
            // 10000年边界不用五位年字符串解析：预期由已知UTC纪元与整数秒确定。
            foreach ([12345, -12345] as $offset) {
                $wall = '10000-01-01 00:00:00';
                $this->assertSame(253402300800 - $offset, Timezone::wallToTimestamp($wall, (string) $offset));
            }
        } finally {
            date_default_timezone_set($server);
            [Date::$timezoneOffset, Date::$timezoneName, Date::$serverTimezoneOffset, Date::$serverTimeStamp] = $state;
        }
    }

    public function testNamedHistoricalSecondsPreserveNativeDstAndSerializeTheRealInstant(): void
    {
        $server = date_default_timezone_get();
        $state = [Date::$timezoneOffset, Date::$timezoneName, Date::$serverTimezoneOffset, Date::$serverTimeStamp];
        try {
            date_default_timezone_set('America/New_York');
            $instant = strtotime('1935-07-01 12:34:56 UTC');
            $native = (new DateTimeImmutable('@' . $instant))->setTimezone(new DateTimeZone('Europe/Amsterdam'));
            $this->assertTrue($native->getOffset() % 60 !== 0);
            $this->assertSame('1', $native->format('I'));
            Date::setTimezone('Europe/Amsterdam');
            $date = new Date($instant);
            foreach (['Y-m-d H:i:s U Z B I', '\c \r \P Y-m-d H:i:s I'] as $format) {
                $this->assertSame($native->format($format), $date->format($format));
            }
            foreach (['c', 'r', 'Y-m-d H:i:s P', 'c | r | U I'] as $format) {
                $this->assertSame(gmdate($format, $instant), $date->format($format));
            }
        } finally {
            date_default_timezone_set($server);
            [Date::$timezoneOffset, Date::$timezoneName, Date::$serverTimezoneOffset, Date::$serverTimeStamp] = $state;
        }
    }

    public function testAnExistingDateUsesTheCurrentSecondPrecisionConfiguration(): void
    {
        $state = [Date::$timezoneOffset, Date::$timezoneName, Date::$serverTimezoneOffset, Date::$serverTimeStamp];
        try {
            $instant = 1609459200;
            Date::setTimezoneOffset(12345);
            $date = new Date($instant);
            Date::setTimezoneOffset(-45);
            $this->assertSame(gmdate('Y-m-d H:i:s', $instant - 45), $date->format('Y-m-d H:i:s'));
            $this->assertSame($instant . ' -45', $date->format('U Z'));
            $this->assertSame(gmdate('c', $instant), $date->format('c'));
        } finally {
            [Date::$timezoneOffset, Date::$timezoneName, Date::$serverTimezoneOffset, Date::$serverTimeStamp] = $state;
        }
    }
}
