<?php
use PHPUnit\Framework\TestCase;
use Typecho\Date;
use Typecho\Timezone;

/**
 * Covers the time zone handling introduced together with the fix for #1206.
 *
 * The site time zone is now stored as an IANA name so that the daylight
 * saving offset can be resolved for the date being published or rendered,
 * instead of a single fixed offset that is wrong for half of the year.
 */
final class TimezoneTest extends TestCase
{
    /**
     * The server time zone is pinned so the results do not depend on the
     * machine the suite runs on.
     *
     * A non-zero offset is the point: on a UTC host the server offset is
     * always 0, and every mistake that confuses the server local domain
     * with the UTC domain stays invisible.
     */
    private string $originalTimezone;

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Shanghai');

        // Date keeps static state; reset so tests stay order-independent
        Date::$timezoneOffset = 0;
        Date::$timezoneName = '';
        Date::$serverTimezoneOffset = 0;
        Date::$serverTimeStamp = 0;
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);

        // Date keeps static state; reset so tests stay order-independent
        Date::$timezoneOffset = 0;
        Date::$timezoneName = '';
        Date::$serverTimezoneOffset = 0;
        Date::$serverTimeStamp = 0;
    }

    public function testLegacyOffsetIsNotTreatedAsAName(): void
    {
        $this->assertFalse(Timezone::isName('-28800'));
        $this->assertFalse(Timezone::isName('28800'));
        $this->assertFalse(Timezone::isName('0'));
        $this->assertFalse(Timezone::isName(''));
    }

    public function testIanaNameIsRecognised(): void
    {
        $this->assertTrue(Timezone::isName('America/Los_Angeles'));
        $this->assertTrue(Timezone::isName('Asia/Shanghai'));
        $this->assertTrue(Timezone::isName('UTC'));
    }

    /** 旧数值配置保留固定偏移语义，不凭数值推断地区。 */
    public function testLegacyOffsetsRetainTheirFixedSemantics(): void
    {
        for ($hours = -12; $hours <= 12; $hours++) {
            $offset = $hours * 3600;
            $raw = (string) $offset;
            $this->assertFalse(Timezone::isName($raw));
            foreach (['2021-01-15 12:34:56 UTC', '2021-07-15 12:34:56 UTC'] as $literal) {
                $instant = strtotime($literal);
                $this->assertSame($offset, Timezone::offsetAt($raw, $instant));
                $this->assertSame($instant + $offset, Timezone::wallTimestamp($instant, $raw));
                $wall = gmdate('Y-m-d H:i:s', $instant + $offset);
                $this->assertSame($instant, Timezone::wallToTimestamp($wall, $raw));
            }
        }
    }

    public function testOffsetDependsOnTheMoment(): void
    {
        $winter = strtotime('2021-01-04 12:00:00 UTC');
        $summer = strtotime('2021-07-04 12:00:00 UTC');

        $this->assertSame(-28800, Timezone::offsetAt('America/Los_Angeles', $winter));
        $this->assertSame(-25200, Timezone::offsetAt('America/Los_Angeles', $summer));
    }

    public function testTimezoneWithoutDaylightSavingHasAConstantOffset(): void
    {
        $winter = strtotime('2021-01-04 12:00:00 UTC');
        $summer = strtotime('2021-07-04 12:00:00 UTC');

        $this->assertSame(28800, Timezone::offsetAt('Asia/Shanghai', $winter));
        $this->assertSame(28800, Timezone::offsetAt('Asia/Shanghai', $summer));
    }

    public function testSouthernHemisphereRunsTheOtherWay(): void
    {
        $january = strtotime('2021-01-04 12:00:00 UTC');
        $july = strtotime('2021-07-04 12:00:00 UTC');

        // Sydney is inside DST in January and outside it in July
        $this->assertSame(39600, Timezone::offsetAt('Australia/Sydney', $january));
        $this->assertSame(36000, Timezone::offsetAt('Australia/Sydney', $july));
    }

    public function testWallClockIsConvertedToTheRightInstant(): void
    {
        // 12:00 in Los Angeles during summer time is 19:00 UTC
        $timestamp = Timezone::wallToTimestamp('2021-07-04 12:00', 'America/Los_Angeles');

        $this->assertSame('2021-07-04 19:00', gmdate('Y-m-d H:i', $timestamp));
    }

    public function testWallClockConversionRespectsTheStandardOffset(): void
    {
        // 12:00 in Los Angeles during winter time is 20:00 UTC
        $timestamp = Timezone::wallToTimestamp('2021-01-04 12:00', 'America/Los_Angeles');

        $this->assertSame('2021-01-04 20:00', gmdate('Y-m-d H:i', $timestamp));
    }

    public function testLocalTimestampRoundTrips(): void
    {
        /**
         * The server offset is the real one, not a hard coded zero.
         *
         * The local timestamp is a wall clock the way the server reads it,
         * so the server offset is part of the value. Pinning it to zero
         * would only ever exercise the UTC case.
         */
        $serverOffset = idate('Z');
        $instant = strtotime('2021-07-04 19:00:00 UTC');

        $local = Timezone::toLocalTimestamp($instant, 'America/Los_Angeles', $serverOffset);
        $back = Timezone::fromLocalTimestamp($local, 'America/Los_Angeles', $serverOffset);

        $this->assertSame($instant, $back);
    }

    public function testLocalTimestampRoundTripsOnTheDaylightSavingSwitch(): void
    {
        $serverOffset = idate('Z');

        // 2021-03-14 is the day the clocks move forward in Los Angeles
        $instant = strtotime('2021-03-14 10:00:00 UTC');

        $local = Timezone::toLocalTimestamp($instant, 'America/Los_Angeles', $serverOffset);
        $back = Timezone::fromLocalTimestamp($local, 'America/Los_Angeles', $serverOffset);

        $this->assertSame($instant, $back);
    }

    public function testLegacyOffsetIsStillHonouredWhenNoNameIsGiven(): void
    {
        $serverOffset = idate('Z');
        $instant = strtotime('2021-07-04 19:00:00 UTC');
        $siteOffset = -28800;

        $local = Timezone::toLocalTimestamp($instant, (string) $siteOffset, $serverOffset);

        /**
         * The legacy branch is the same conversion as the named one: the
         * server local wall clock of that instant, read with the fixed
         * offset of the site.
         *
         * It used to be "$instant + offset - $serverOffset" with the
         * server offset of the **request moment**. On a server whose own
         * zone observes daylight saving that is one hour off across the
         * seasons, which is the very defect this change is about.
         *
         * The site wall clock is the instant plus the site offset, read as
         * a wall clock in the server zone.
         */
        $siteWall = gmdate('Y-m-d H:i:s', $instant + $siteOffset);
        $expected = (int) (new DateTime(
            $siteWall,
            new DateTimeZone(date_default_timezone_get())
        ))->getTimestamp();

        $this->assertSame($expected, $local);
    }

    /**
     * The legacy branch must not depend on the season the request happens
     * in, the same way the named branch does not.
     */
    public function testTheLegacyOffsetDoesNotShiftWithTheRequestSeason(): void
    {
        $original = date_default_timezone_get();

        try {
            /** A server whose own zone observes daylight saving */
            date_default_timezone_set('America/New_York');

            /**
             * One instant, rendered while the server's clock is in a
             * different season.
             *
             * The server's own daylight saving switch is 2021-03-14, so the
             * server offset is -18000 in January and -14400 in July. The
             * legacy branch renders with the server's clock, so the point of
             * the test is that the rendered wall clock must not depend on
             * the season the request happened to arrive in.
             *
             * The rendering reads the server's offset **at the instant being
             * rendered**, not at the moment of the request. So the two
             * requests have to render the same instant, and the way to make
             * the server's own clock land in a different season is to move
             * the server's *current* date, not the instant.
             */
            $instant = strtotime('2021-01-15 12:00:00 UTC');

            /**
             * Render the same instant while the server's own clock says it
             * is January and again while it says it is July.
             */
            $january = $this->renderWithServerDate('2021-01-15 09:00:00', $instant);
            $july = $this->renderWithServerDate('2021-07-15 09:00:00', $instant);

            $this->assertSame(
                $january,
                $july,
                'the request season must not move the rendered time'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * 在服务器自己的时钟处于指定日期时渲染一个瞬间
     *
     * 服务器的偏移取决于**被渲染那一刻**的服务器日期, 所以这里算出
     * 那个日期对应的 epoch, 再读它的服务器偏移 (date('Z', $epoch))。
     */
    private function renderWithServerDate(string $serverDate, int $instant): string
    {
        $serverTimestamp = strtotime($serverDate . ' America/New_York');
        $serverOffset = (int) date('Z', $serverTimestamp);

        /**
         * 这个日期必须真的落在预期的服务器偏移上, 否则这个测试比较的
         * 是同一个值, 任何实现都能通过。
         */
        $expected = substr($serverDate, 5, 2) === '01' ? -18000 : -14400;

        $this->assertSame(
            $expected,
            $serverOffset,
            "the server offset for $serverDate must really be $expected seconds, "
            . 'otherwise this test proves nothing'
        );

        return gmdate(
            'Y-m-d H:i:s',
            Timezone::toLocalTimestamp($instant, '-28800', $serverOffset)
        );
    }

    public function testInvalidTimezoneFallsBackToNoOffset(): void
    {
        $this->assertSame(0, Timezone::offsetAt('Not/AZone', strtotime('2021-07-04 12:00:00 UTC')));
        $this->assertSame(0, Timezone::offsetAt('', strtotime('2021-07-04 12:00:00 UTC')));
    }

    public function testDateRendersInTheConfiguredTimezone(): void
    {
        $instant = strtotime('2021-07-04 19:00:00 UTC');

        Date::setTimezone('America/Los_Angeles');
        $date = new Date($instant);

        $this->assertSame('2021-07-04 12:00', $date->format('Y-m-d H:i'));
    }

    public function testDateRendersDifferentlyAcrossSeasons(): void
    {
        Date::setTimezone('America/Los_Angeles');

        $summer = new Date(strtotime('2021-07-04 19:00:00 UTC'));
        $winter = new Date(strtotime('2021-01-04 20:00:00 UTC'));

        $this->assertSame('2021-07-04 12:00', $summer->format('Y-m-d H:i'));
        $this->assertSame('2021-01-04 12:00', $winter->format('Y-m-d H:i'));
    }

    /**
     * A time zone name that is not a name must not fall back to UTC.
     *
     * An offset with no exact region match keeps its value when it is
     * migrated, so the site time zone can be the string '12345'. That is
     * not a zone name, and DateTimeZone('12345') raises -- which used to
     * send the renderer down the gmdate() fallback. That fallback prints
     * a +00:00 designator and makes 'U' return the wall clock instead of
     * the instant, so a feed or a JSON-LD block would carry a wrong time.
     *
     * The fixed offset is the right answer for that value, so it must be
     * used.
     */
    public function testATimeZoneNameThatIsNotANameUsesTheFixedOffset(): void
    {
        $instant = strtotime('2021-07-04 12:00:00 UTC');

        $originalName = Date::$timezoneName;
        $originalOffset = Date::$timezoneOffset;

        try {
            /**
             * What the migration leaves behind for an offset with no exact
             * region match.
             */
            Date::setTimezone('12345');

            $date = new Date($instant);

            $this->assertSame('2021-07-04 15:25:45', $date->format('Y-m-d H:i:s'));
            $this->assertSame(12345, Date::$timezoneOffset);
            // 秒偏移无法用 RFC 分钟标签表达，整个带标签的格式使用真实 UTC。
            $this->assertSame('+0000', $date->format('O'));
            $this->assertSame('+00:00', $date->format('P'));
            $this->assertSame(gmdate('c', $instant), $date->format('c'));
            $this->assertSame(gmdate('r', $instant), $date->format('r'));

            $this->assertSame(
                $instant,
                (int) $date->format('U'),
                'U must be the instant, not the wall clock'
            );
        } finally {
            Date::$timezoneName = $originalName;
            Date::$timezoneOffset = $originalOffset;
        }
    }

    /**
     * The relative time wording ("3 hours ago") is computed against the
     * current moment, so both moments have to be expressed in the same zone.
     * When the site time zone is an IANA name the fixed offset no longer
     * describes it, so the reference moment has to be converted as well.
     */
    public function testRelativeTimeUsesTheSameZoneAsTheTimestamp(): void
    {
        Date::setTimezone('America/Los_Angeles');

        /**
         * The reference moment inside Date::word() is the current time, so
         * the post has to sit three hours before it. A fixed timestamp would
         * make the expectation depend on the wall clock, so the offset is
         * derived from the current time instead - but the assertion stays
         * stable across a daylight saving switch.
         */
        /**
         * The reference moment is pinned, so the wording cannot change
         * because the suite happened to run close to midnight.
         */
        $now = strtotime('2021-07-04 15:00:00 UTC');
        Date::$serverTimeStamp = $now;

        $date = new Date($now - 3 * 3600);

        $this->assertSame('3小时前', $date->word());
    }

    public function testRelativeTimeIsCorrectJustAfterMidnightInTheSiteZone(): void
    {
        Date::setTimezone('Asia/Shanghai');

        /**
         * 30 minutes ago in UTC is already the next day in Shanghai, and
         * the wording must not become "yesterday" there. The reference
         * moment is pinned so the test does not depend on when it runs.
         */
        $now = strtotime('2021-07-04 16:30:00 UTC');
        Date::$serverTimeStamp = $now;

        $date = new Date($now - 30 * 60);

        $this->assertSame('30分钟前', $date->word());
    }

    /**
     * The elapsed time must be the real one, not the difference of two wall
     * clock readings.
     *
     * On the day the clocks move forward in Los Angeles the wall clock skips
     * 02:00-03:00, so 01:30 and 03:30 are one hour apart in reality but two
     * hours apart on the wall clock. Using the wall clock difference reports
     * "two hours ago" for something that happened an hour ago.
     */
    public function testElapsedTimeIsTheRealOneAcrossADaylightSavingSwitch(): void
    {
        Date::setTimezone('America/Los_Angeles');

        /** 2021-03-14 10:30 UTC is 03:30 PDT, one hour after 01:30 PST */
        $now = strtotime('2021-03-14 10:30:00 UTC');
        $oneHourEarlier = strtotime('2021-03-14 09:30:00 UTC');

        Date::$serverTimeStamp = $now;

        try {
            $date = new Date($oneHourEarlier);

            $this->assertSame(
                '一小时前',
                $date->word(),
                'one real hour must not be reported as two hours'
            );
        } finally {
            Date::$serverTimeStamp = 0;
        }
    }

    /**
     * The same moment must still be recognised as "today" by the calendar,
     * even when the wall clock difference says otherwise.
     */
    public function testCalendarDayIsStillJudgedByTheWallClock(): void
    {
        Date::setTimezone('America/Los_Angeles');

        /** 2021-03-14 01:30 PST, still the same calendar day */
        $now = strtotime('2021-03-14 09:30:00 UTC');
        $sameDay = strtotime('2021-03-14 08:30:00 UTC');

        Date::$serverTimeStamp = $now;

        try {
            $date = new Date($sameDay);

            $this->assertSame('一小时前', $date->word());
        } finally {
            Date::$serverTimeStamp = 0;
        }
    }

    /**
     * Around a daylight saving switch the two moments can fall on different
     * calendar days, which changes the wording. Pin the reference moment by
     * checking a date that is far away from any switch.
     */
    /**
     * The relative time across a daylight saving switch is measured in
     * real elapsed time, not in wall clock difference.
     *
     * On the day the clocks move forward in Los Angeles, the wall clock
     * skips an hour: two posts an hour apart in real time are two hours
     * apart on the wall clock. The wording has to say "1 hour ago" for
     * both, because that is how much time really passed.
     */
    public function testRelativeTimeAcrossADaylightSavingSwitch(): void
    {
        $originalStamp = Date::$serverTimeStamp;
        $originalOffset = Date::$timezoneOffset;
        $originalName = Date::$timezoneName;

        try {
            Date::setTimezone('America/Los_Angeles');

            /** 2021-03-14 is the day the clocks move forward in Los Angeles */
            $switch = strtotime('2021-03-14 10:00:00 UTC');

            /**
             * The reference moment is pinned, because the wording is
             * computed against the current moment.
             */
            Date::$serverTimeStamp = $switch;

            /**
             * The clocks move forward at 10:00 UTC in Los Angeles. The
             * instant just before it and the instant just after it are one
             * real hour apart, but two hours apart on the wall clock:
             * 01:30 PST becomes 03:30 PDT.
             */
            $before = new Date($switch - 3600);
            $after = new Date($switch);

            $this->assertSame(
                3600,
                $after->format('U') - $before->format('U'),
                'the two instants are one real hour apart'
            );

            /**
             * The wall clock difference is two hours.
             */
            $wallBefore = $before->format('Y-m-d H:i:s');
            $wallAfter = $after->format('Y-m-d H:i:s');

            $this->assertSame(
                7200,
                strtotime($wallAfter . ' UTC') - strtotime($wallBefore . ' UTC'),
                'the wall clock difference is two hours'
            );

            /**
             * The wording has to follow the real elapsed time: the post is
             * one real hour old, so it says an hour ago -- not two hours
             * ago, which is what the wall clock difference would say.
             */
            $this->assertSame(
                '一小时前',
                $before->word(),
                'the wording must be measured in real elapsed time'
            );

            $this->assertStringNotContainsString(
                '2',
                $before->word(),
                'the wall clock difference of two hours must not show up'
            );

            /**
             * The instant that is "now" says so.
             */
            $this->assertSame(
                '刚刚',
                $after->word(),
                'the instant of the reference moment is just now'
            );
        } finally {
            Date::$serverTimeStamp = $originalStamp;
            Date::$timezoneOffset = $originalOffset;
            Date::$timezoneName = $originalName;
        }
    }

    public function testLegacyOffsetStillRendersTheOldWay(): void
    {
        $instant = strtotime('2021-07-04 19:00:00 UTC');

        Date::setTimezoneOffset(-28800);
        $date = new Date($instant);

        // A fixed offset cannot follow daylight saving, so it stays one hour behind
        $this->assertSame('2021-07-04 11:00', $date->format('Y-m-d H:i'));
    }

    /**
     * The XML-RPC GMT literal is read as GMT, and the result never depends
     * on the server time zone.
     *
     * The old reading added the request moment's server offset to a value
     * that mktime() had already interpreted with the offset of the parsed
     * date. On a server whose own zone observes daylight saving the two
     * differ by an hour across the seasons.
     */
    public function testTheGmtLiteralIsReadAsGmt(): void
    {
        $original = date_default_timezone_get();

        try {
            $serverZones = [
                'UTC',
                'Europe/Berlin',
                'America/Los_Angeles',
                'Australia/Sydney',
            ];

            /** The expected instants, straight from the platform */
            $expectations = [
                '20210314T01:30:00Z'        => 1615685400,
                '20210314T01:30:00'         => 1615685400,
                '20210314T01:30:00+08:00'   => 1615656600,
                '20210314T01:30:00-05:00'   => 1615703400,
            ];

            foreach ($serverZones as $serverZone) {
                date_default_timezone_set($serverZone);

                foreach ($expectations as $literal => $expected) {
                    $this->assertSame(
                        $expected,
                        Timezone::gmtLiteralToTimestamp($literal),
                        sprintf(
                            '%s must be read the same way on a %s server',
                            $literal,
                            $serverZone
                        )
                    );
                }
            }

            /** A shape that is not a date at all */
            $this->assertNull(Timezone::gmtLiteralToTimestamp('not a date'));
            $this->assertNull(Timezone::gmtLiteralToTimestamp(''));
        } finally {
            date_default_timezone_set($original);
        }
    }

}
