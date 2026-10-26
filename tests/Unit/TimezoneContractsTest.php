<?php

use PHPUnit\Framework\TestCase;
use Typecho\Date;
use Typecho\I18n;
use Widget\Options\General;

/** 时区改动须保持已有配置和公共接口的可观察行为。 */
class TimezoneContractsTest extends TestCase
{
    private array $saved = [];
    private string $serverZone;

    protected function setUp(): void
    {
        $this->serverZone = date_default_timezone_get();
        foreach (['serverTimeStamp', 'serverTimezoneOffset', 'timezoneOffset', 'timezoneName'] as $name) {
            $this->saved[$name] = Date::$$name;
        }
        Date::$serverTimeStamp = 1625140800;
        Date::setTimezoneOffset(0);
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $name => $value) {
            Date::$$name = $value;
        }
        date_default_timezone_set($this->serverZone);
    }

    public function testCurrentLegalAliasesAndOffsetsRemainSelectable(): void
    {
        foreach (['+05:45', 'CEST', '19800'] as $value) {
            $this->assertArrayHasKey($value, General::getTimezoneList($value));
        }
        $this->assertArrayNotHasKey('Not/AZone', General::getTimezoneList('Not/AZone'));
    }

    public function testPaddedZoneSerializesTheRealInstant(): void
    {
        $instant = 1636276200;
        foreach (['UTC', 'Asia/Shanghai', 'Europe/Berlin'] as $server) {
            date_default_timezone_set($server);
            foreach ([' America/New_York ', "\tAsia/Kathmandu\n"] as $raw) {
                $expected = (new DateTimeImmutable('@' . $instant))->setTimezone(new DateTimeZone(trim($raw)));
                Date::setTimezone($raw);
                $actual = new Date($instant);
                foreach (['c', 'r', 'U'] as $format) {
                    $this->assertSame($expected->format($format), $actual->format($format));
                }
            }
        }
    }

    /**
     * 固定偏移站点的公开载体也必须使用目标日期的服务器规则。
     * @dataProvider oppositeServerSeasons
     */
    public function testFixedOffsetDateCarrierUsesTheTargetServerSeason(string $utc, string $requestUtc): void
    {
        date_default_timezone_set('Europe/Berlin');
        $server = new DateTimeZone('Europe/Berlin');
        $request = (new DateTimeImmutable($requestUtc))->setTimezone($server);
        $instant = (new DateTimeImmutable($utc))->getTimestamp();
        $siteDate = (new DateTimeImmutable('@' . $instant))->setTimezone(new DateTimeZone('+08:00'));
        $nativeCarrier = new DateTimeImmutable($siteDate->format('Y-m-d H:i:s'), $server);

        Date::$serverTimeStamp = $request->getTimestamp();
        Date::setTimezoneOffset(28800);
        // 模拟请求时刻缓存的服务器偏移；目标日期处于相反季节。
        Date::$serverTimezoneOffset = $request->getOffset();
        $this->assertNotSame($request->getOffset(), $nativeCarrier->getOffset());

        $date = new Date($instant);
        $this->assertSame($nativeCarrier->getTimestamp(), $date->timeStamp);
        $this->assertSame($siteDate->format('Y-m-d H:i:s'), date('Y-m-d H:i:s', $date->timeStamp));
        $this->assertSame($instant, $date->utcTimeStamp);
        $this->assertSame($siteDate->format('c'), $date->format('c'));
        $this->assertSame($instant, (int) $date->format('U'));
    }

    public static function oppositeServerSeasons(): array
    {
        return [
            'summer target, winter request' => ['2021-07-04T12:34:56Z', '2021-01-04T12:00:00Z'],
            'winter target, summer request' => ['2021-01-04T12:34:56Z', '2021-07-04T12:00:00Z'],
        ];
    }

    public function testPublicTwoArgumentWordingKeepsTheServerCalendar(): void
    {
        date_default_timezone_set('Asia/Shanghai');
        $this->assertSame('昨天 23:50', I18n::dateWord(1625154600, 1625155800));
        $this->assertSame('20分钟前', I18n::dateWord(1609458600, 1609459800));
    }

    public function testFuturePostInRepeatedHourDoesNotBecomePast(): void
    {
        Date::$serverTimeStamp = strtotime('2021-11-07T08:50:00Z');
        Date::setTimezone('America/Los_Angeles');
        $future = new Date(strtotime('2021-11-07T09:10:00Z'));
        $this->assertSame('11月7日', $future->word());
    }

    public function testElapsedMinutesSurviveTheRepeatedHour(): void
    {
        Date::$serverTimeStamp = strtotime('2021-11-07T09:10:00Z');
        Date::setTimezone('America/Los_Angeles');
        $past = new Date(strtotime('2021-11-07T08:50:00Z'));
        $this->assertSame('20分钟前', $past->word());
    }

    /** 服务器跨午夜而站点仍在同一天，内部文案必须使用站点日历。 */
    public function testSiteCalendarWinsWhenTheServerCrossesMidnight(): void
    {
        date_default_timezone_set('Etc/GMT+9');
        $past = strtotime('2021-11-07T08:50:00Z');
        $now = strtotime('2021-11-07T09:10:00Z');
        $this->assertSame('2021-11-06 23:50', date('Y-m-d H:i', $past));
        $this->assertSame('2021-11-07 00:10', date('Y-m-d H:i', $now));

        Date::$serverTimeStamp = $now;
        Date::setTimezone('America/Los_Angeles');
        $this->assertSame('20分钟前', (new Date($past))->word());
        // 两参数公共接口仍保留服务器日历，和内部真实 elapsed 入口各有契约。
        $this->assertSame('昨天 23:50', I18n::dateWord($past, $now));

        Date::$serverTimeStamp = $past;
        $this->assertSame('11月7日', (new Date($now))->word());
    }

    public function testCalendarDaysRemainCalendarDaysAcrossTheSpringGap(): void
    {
        $from = strtotime('2021-03-13T12:00:00Z');
        $now = strtotime('2021-03-16T12:00:00Z');
        $this->assertSame('3天前', I18n::dateWord($from, $now, 71 * 3600));
    }

    public function testSiteYesterdayCrossesALeapYearBoundary(): void
    {
        $from = strtotime('2020-12-31T23:50:00Z');
        $now = strtotime('2021-01-01T00:10:00Z');
        $this->assertSame('昨天 23:50', I18n::dateWord($from, $now, 1200));
    }

    public function testFinalArchiveDayKeepsTheFixedQuarterHourOffset(): void
    {
        [$from, $to] = \Widget\Archive::periodWindow(9999, 12, 31, 12, 31, '20700');
        $this->assertSame(86400, $to - $from);
        $this->assertSame('10000-01-01 00:00:00', gmdate('Y-m-d H:i:s', $to + 20700));
    }

    public function testArchiveYearsCannotBeRewrittenToAnotherCentury(): void
    {
        $this->assertFalse(\Widget\Archive::isValidArchiveYear(70));
        $this->assertFalse(\Widget\Archive::isValidArchiveYear(100));
        $this->assertTrue(\Widget\Archive::isValidArchiveYear(101));
        $this->assertTrue(\Widget\Archive::isValidArchiveYear(9999));
    }

    /** @dataProvider serverGapCases */
    public function testLegacyCarriersNormalizeLikePhpWhileUtcRenderingStaysExact(
        string $server, string $wall, string $normalized
    ): void {
        date_default_timezone_set($server);
        $utc = new DateTimeZone('UTC');
        $instant = (new DateTimeImmutable($wall, $utc))->getTimestamp();
        $native = new DateTimeImmutable($wall, new DateTimeZone($server));
        $this->assertSame($normalized, $native->format('Y-m-d H:i:s'));

        foreach (['0', 'UTC'] as $site) {
            $carrier = \Typecho\Timezone::toLocalTimestamp($instant, $site, 0);
            $this->assertSame($native->getTimestamp(), $carrier);
            $this->assertSame($normalized, date('Y-m-d H:i:s', $carrier));
        }

        // 兼容载体无精确解时会归一化；真实时间渲染和发布解析必须保留原瞬间。
        Date::setTimezone('UTC');
        $actual = new Date($instant);
        $this->assertSame($wall, $actual->format('Y-m-d H:i:s'));
        $this->assertSame((string) $instant, $actual->format('U'));
        $this->assertSame($instant, \Typecho\Timezone::wallToTimestamp($wall, 'UTC'));
    }

    public static function serverGapCases(): array
    {
        return [
            ['Europe/Berlin', '2021-03-28 02:30:00', '2021-03-28 03:30:00'],
            ['America/New_York', '2021-03-14 02:30:00', '2021-03-14 03:30:00'],
            ['Australia/Lord_Howe', '2021-10-03 02:15:00', '2021-10-03 02:45:00'],
            ['Pacific/Apia', '2011-12-30 12:34:56', '2011-12-31 12:34:56'],
        ];
    }
}
