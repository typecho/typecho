<?php

use PHPUnit\Framework\TestCase;
use Typecho\Date;
use Typecho\Timezone;
use Widget\Options;

/** 验收实际配置、选项转换与 XML-RPC 字面；不约束源码拼写或自动地区猜测。 */
final class TimezoneIntegrationTest extends TestCase
{
    private string $serverZone;
    private array $dateState = [];

    protected function setUp(): void
    {
        $this->serverZone = date_default_timezone_get();
        foreach (['timezoneName', 'timezoneOffset', 'serverTimezoneOffset', 'serverTimeStamp'] as $name) {
            $this->dateState[$name] = Date::$$name;
        }
        date_default_timezone_set('Asia/Shanghai');
        Date::$serverTimeStamp = 1625140800;
        Date::setTimezoneOffset(0);
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->serverZone);
        foreach ($this->dateState as $name => $value) {
            Date::$$name = $value;
        }
    }

    private function options(string $raw): Options
    {
        $options = (new ReflectionClass(Options::class))->newInstanceWithoutConstructor();
        $options->push(['timezone' => $raw]);
        return $options;
    }

    public function testAllTwentyFiveLegacyValuesRemainSelectable(): void
    {
        $list = Options\General::getTimezoneList();
        for ($hours = -12; $hours <= 12; $hours++) {
            $this->assertArrayHasKey((string) ($hours * 3600), $list);
        }
    }

    public function testEveryLegalCurrentValueRemainsSelectable(): void
    {
        foreach (['12345', '1800', '19800', '20700', '+05:45', 'CEST', 'Etc/GMT+12', 'America/Los_Angeles'] as $raw) {
            $this->assertArrayHasKey($raw, Options\General::getTimezoneList($raw), $raw);
        }
        $this->assertArrayNotHasKey('Not/AZone', Options\General::getTimezoneList('Not/AZone'));
    }

    public function testSettingsListIncludesRegionalAndFixedIdentifiers(): void
    {
        $list = Options\General::getTimezoneList();
        foreach (['America/Los_Angeles', 'Asia/Shanghai', 'Europe/London', 'Etc/GMT+12', 'Etc/GMT-8'] as $name) {
            $this->assertArrayHasKey($name, $list);
        }
    }

    public function testLabelsPreserveNonWholeHourOffsets(): void
    {
        $list = Options\General::getTimezoneList('1800');
        $this->assertStringContainsString('05:30', $list['Asia/Kolkata']);
        $this->assertStringContainsString('05:45', $list['Asia/Kathmandu']);
        $this->assertStringContainsString('00:30', $list['1800']);
    }

    public function testSettingsListIsSortedByActualOffset(): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $previous = PHP_INT_MIN;
        foreach (Options\General::getTimezoneList() as $key => $label) {
            $raw = (string) $key;
            $offset = is_numeric($raw) ? (int) $raw : (new DateTimeZone($raw))->getOffset($now);
            $this->assertGreaterThanOrEqual($previous, $offset, $raw);
            $previous = $offset;
        }
    }

    public function testOptionsConvertersExecuteBothDirectionsAndPreserveSeconds(): void
    {
        foreach (['America/Los_Angeles', '20700', 'UTC'] as $raw) {
            $options = $this->options($raw);
            $zone = new DateTimeZone($raw === '20700' ? '+05:45' : $raw);
            $expected = (new DateTimeImmutable('2021-07-04 12:34:56', $zone))->getTimestamp();
            $serverWall = mktime(12, 34, 56, 7, 4, 2021);
            $this->assertSame($expected, $options->timezoneToTimestamp($serverWall), $raw);
            $this->assertSame('2021-07-04 12:34:56',
                date('Y-m-d H:i:s', $options->timestampToTimezone($expected)), $raw);
            $this->assertSame(56, (int) gmdate('s', $expected));
        }
    }

    public function testOptionsResolveTheRequestedInstantRatherThanTheCurrentOffset(): void
    {
        $options = $this->options('America/Los_Angeles');
        $this->assertSame(-28800, $options->timezoneOffsetAt(strtotime('2021-01-15 12:00:00 UTC')));
        $this->assertSame(-25200, $options->timezoneOffsetAt(strtotime('2021-07-15 12:00:00 UTC')));
        $this->assertSame('America/Los_Angeles', $options->timezoneName);
        $this->assertSame(-25200, (int) $options->timezone);
    }

    public function testLegacySubHourWallClocksAreNotTruncated(): void
    {
        foreach (['20700' => '+05:45', '19800' => '+05:30', '1800' => '+00:30'] as $raw => $name) {
            $native = new DateTimeImmutable('2021-07-04 12:34:56', new DateTimeZone($name));
            $actual = Timezone::wallToTimestamp('2021-07-04 12:34:56', (string) $raw);
            $this->assertSame($native->getTimestamp(), $actual);
            $this->assertSame('2021-07-04 12:34:56',
                gmdate('Y-m-d H:i:s', Timezone::wallTimestamp($actual, (string) $raw)));
        }
    }

    public function testNamedWallClockUsesTheNativeZoneRules(): void
    {
        foreach (['2021-03-14 02:30:45', '2021-11-07 01:30:45', '2021-07-04 12:34:56'] as $wall) {
            $expected = new DateTimeImmutable($wall, new DateTimeZone('America/Los_Angeles'));
            $this->assertSame($expected->getTimestamp(), Timezone::wallToTimestamp($wall, 'America/Los_Angeles'));
        }
    }

    public function testGmtLiteralsRetainTheInstantAcrossServerZones(): void
    {
        $expected = strtotime('2021-07-04 12:34:56 UTC');
        foreach (['UTC', 'Asia/Shanghai', 'America/New_York', 'Europe/Berlin'] as $server) {
            date_default_timezone_set($server);
            foreach (['20210704T12:34:56', '20210704T12:34:56Z', '2021-07-04T12:34:56Z',
                '2021-07-04T20:34:56+08:00', '2021-07-04T12:34:56.987Z'] as $literal) {
                $this->assertSame($expected, Timezone::gmtLiteralToTimestamp($literal), $server . ': ' . $literal);
            }
        }
        $this->assertSame(strtotime('2021-07-04 12:34:00 UTC'),
            Timezone::gmtLiteralToTimestamp('2021-07-04T12:34Z'));
        $this->assertNull(Timezone::gmtLiteralToTimestamp('not-a-date'));
    }

    public function testXmlRpcLocalLiteralIsInterpretedInTheChosenSiteZone(): void
    {
        $literal = (new \IXR\Date('2021-03-28T02:30:45'))->getIso();
        $expected = strtotime('2021-03-28 02:30:45 UTC');
        foreach (['UTC', 'Europe/Berlin', 'Asia/Shanghai'] as $server) {
            date_default_timezone_set($server);
            $this->assertSame($expected, Timezone::wallLiteralToTimestamp($literal, 'UTC'));
        }
    }

    public function testNumericAndNamedValuesHaveDistinctSemantics(): void
    {
        foreach (['28800', '+28800', '28800.0', '-25200', '0', ''] as $raw) {
            $this->assertFalse(Timezone::isName($raw), $raw);
        }
        foreach (['America/Los_Angeles', 'UTC', '+05:45', 'CEST'] as $raw) {
            $this->assertTrue(Timezone::isName($raw), $raw);
        }
        $this->assertFalse(Timezone::isName('Asia/Shangai'));
        $this->assertFalse(Timezone::isName('not a zone'));
    }

    public function testInvalidDatesAreRejectedWithoutAnEpochFallback(): void
    {
        foreach (['UTC', 'America/Los_Angeles', '28800'] as $raw) {
            $this->assertNull(Timezone::wallToTimestamp('2021-13-45 25:00:00', $raw));
        }
    }
}
