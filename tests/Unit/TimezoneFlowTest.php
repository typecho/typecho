<?php
use PHPUnit\Framework\TestCase;
use Typecho\Config;
use Typecho\Date;
use Typecho\Request as HttpRequest;
use Typecho\Response as HttpResponse;
use Typecho\Widget\Request;
use Typecho\Widget\Response;
use Typecho\Timezone;
use Widget\Options;

/**
 * These tests drive the real widgets, not a copy of their logic.
 *
 * The publish date path and the option converters are the two places where a
 * wrong time zone interpretation silently shifts a post, so they are covered
 * end to end.
 */
final class TimezoneFlowTest extends TestCase
{
    /**
     * The server offset lives in Date::$serverTimezoneOffset, which the
     * production code sets through Date::setTimezone(). Every test starts
     * from a clean state so the tests stay order independent.
     *
     * 【为什么固定一个非零偏移的时区】服务器偏移是这个类里最容易出错的
     * 那个域。测试如果跑在 UTC 主机上, 偏移恒为 0, 任何把服务器本地语义
     * 和 UTC 语义搞混的错误都看不出来。所以这里固定成 Asia/Shanghai
     * (偏移 +28800), 让偏移**存在且有意义**。
     *
     * 固定之后, 整个类与运行测试的机器无关 —— 结果不再取决于 CI 跑在
     * 哪个时区。
     */
    private string $originalTimezone;
    private array $originalDateState = [];

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
        foreach (['timezoneOffset', 'timezoneName', 'serverTimezoneOffset', 'serverTimeStamp'] as $name) {
            $this->originalDateState[$name] = Date::$$name;
        }
        date_default_timezone_set('Asia/Shanghai');

        Date::$timezoneOffset = 0;
        Date::$timezoneName = '';
        Date::$serverTimezoneOffset = 0;
        Date::$serverTimeStamp = 0;
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        foreach ($this->originalDateState as $name => $value) {
            Date::$$name = $value;
        }
    }

    /**
     * Build the epoch of a wall clock the way the application does.
     *
     * mktime() produces the epoch of the wall clock **in the server time
     * zone**, and that is what the converters receive in production. A
     * $this->wallEpoch($wall) would be a UTC epoch instead, which is a
     * different domain and only happens to agree when the server runs in
     * UTC - which is exactly what the test bootstrap pins.
     *
     * @return integer
     */
    private function wallEpoch(string $wall): int
    {
        return mktime(
            (int) substr($wall, 11, 2),
            (int) substr($wall, 14, 2),
            0,
            (int) substr($wall, 5, 2),
            (int) substr($wall, 8, 2),
            (int) substr($wall, 0, 4)
        );
    }

    /**
     * Build a Widget\Options instance holding a fixed time zone.
     *
     * The real Options widget reads its row from the database, so the test
     * uses a subclass that skips the database and takes the row directly.
     * The converters under test are the real ones.
     *
     * The server offset is not taken from the row: Widget\Options exposes it
     * through the ___serverTimezone() accessor, which returns the value
     * Date::$serverTimezoneOffset holds. The test therefore goes through
     * Date::setTimezone(), the same way the application does.
     */
    private function options(string $timezone): Options
    {
        Date::setTimezone($timezone);

        $options = new TestOptions(
            new Request(new HttpRequest()),
            new Response(new HttpRequest(), new HttpResponse()),
            new Config()
        );

        $options->push(['timezone' => $timezone]);

        return $options;
    }

    /**
     * The converters must round trip: a wall clock in the site zone becomes
     * a UTC instant, and rendering that instant gives the wall clock back.
     */
    public function testConvertersRoundTrip(): void
    {
        $cases = [
            ['America/Los_Angeles', '2021-01-15 12:00', '2021-01-15 20:00'],
            ['America/Los_Angeles', '2021-07-04 12:00', '2021-07-04 19:00'],
            ['Europe/Berlin', '2021-01-15 12:00', '2021-01-15 11:00'],
            ['Europe/Berlin', '2021-07-04 12:00', '2021-07-04 10:00'],
            ['Asia/Shanghai', '2021-07-04 12:00', '2021-07-04 04:00'],
        ];

        foreach ($cases as [$zone, $wall, $expectedUtc]) {
            $options = $this->options($zone);

            $wallEpoch = $this->wallEpoch($wall);
            $utc = $options->timezoneToTimestamp($wallEpoch);

            $this->assertSame(
                $expectedUtc,
                gmdate('Y-m-d H:i', $utc),
                sprintf('%s %s must become %s UTC', $zone, $wall, $expectedUtc)
            );

            /**
             * timestampToTimezone() returns the epoch of the wall clock the
             * way the server reads it, so date() renders that wall clock.
             */
            $this->assertSame(
                $wall,
                date('Y-m-d H:i', $options->timestampToTimezone($utc)),
                sprintf('%s %s must render back to the same wall clock', $zone, $wall)
            );
        }
    }

    /**
     * The offset used by the XML-RPC layer is the site offset at that
     * instant, not a fixed value.
     */
    public function testTimezoneOffsetFollowsDaylightSaving(): void
    {
        $options = $this->options('America/Los_Angeles');

        /**
         * 【采样当前年份】1 月和 7 月只是"标准季节"和"夏令时季节"的
         * 代表, 写死某一年的话, 一个改了起止日期的规则会让 1 月或 7 月
         * 不再落在该季节里。
         */
        $winter = strtotime(date('Y') . '-01-15 12:00:00 UTC');
        $summer = strtotime(date('Y') . '-07-15 12:00:00 UTC');

        $this->assertSame(-28800, $options->timezoneOffsetAt($winter));
        $this->assertSame(-25200, $options->timezoneOffsetAt($summer));
    }

    /**
     * A legacy numeric time zone keeps the old arithmetic.
     */
    public function testLegacyOffsetKeepsTheOldArithmetic(): void
    {
        $options = $this->options('-28800');

        /** The wall clock epoch, the way the application builds it */
        $wallEpoch = $this->wallEpoch('2021-07-04 12:00');
        $utc = $options->timezoneToTimestamp($wallEpoch);

        $this->assertSame('2021-07-04 20:00', gmdate('Y-m-d H:i', $utc));
        $this->assertSame(-28800, $options->timezoneOffsetAt($utc));
    }

    /**
     * The wall clock in the publish form is a site wall clock, so the
     * instant must not depend on the time zone of the machine that happens
     * to submit the form.
     *
     * This is the regression that a browser time zone field would cause:
     * an author in Tokyo editing a Los Angeles site would have the post
     * shifted by the difference between the two zones on every save.
     */
    public function testPublishDateDoesNotDependOnTheSubmittingMachine(): void
    {
        $options = $this->options('America/Los_Angeles');

        $wall = '2021-07-04 12:00';

        /**
         * 【真的切换进程时区, 而且走发布路径】
         *
         * 之前这两个值是**同一个调用**的两次赋值 ——
         * `$fromTokyo` 和 `$fromBerlin` 用的是同一个
         * `timezoneToTimestamp($wallEpoch)`, 中间没有换过进程时区, 所以
         * 断言它们相等是恒真的: 无论实现对错都会通过。
         *
         * 【为什么改走发布路径而不是 timezoneToTimestamp()】
         *
         * timezoneToTimestamp() 的入参是**服务器本地语义**的墙钟时间戳
         * (mktime 的结果), 它的契约就是"按服务器时区读出来是站点墙钟"。
         * 换掉进程时区就换掉了那个数的含义, 结果不同是**正确**的 ——
         * 所以拿它来测"与提交机器无关"是测错了对象。
         *
         * 要测的性质是"作者填的墙上时间按**站点**时区解释, 与作者所在的
         * 机器无关"。那条路是 getCreatedFromDate(), 它按站点时区解析墙钟
         * 文本, 不该看进程时区一眼。
         */
        $original = date_default_timezone_get();

        try {
            date_default_timezone_set('Asia/Tokyo');
            $fromTokyo = $this->createdFromDate($options, $wall, 0);

            date_default_timezone_set('Europe/Berlin');
            $fromBerlin = $this->createdFromDate($options, $wall, 0);
        } finally {
            date_default_timezone_set($original);
        }

        $this->assertSame(
            $fromTokyo,
            $fromBerlin,
            'the publish time must not depend on the zone of the machine that submitted it'
        );

        $this->assertSame('2021-07-04 19:00', gmdate('Y-m-d H:i', $fromTokyo));
    }

    /**
     * The publish form must not submit a browser time zone. The wall clock
     * it carries is a site wall clock, so interpreting it in the browser
     * zone would shift every post by the difference between the two zones.
     */
    public function testThePublishFormDoesNotSubmitABrowserTimeZone(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/admin/write-js.php'
        );

        /**
         * 【断言的目标必须是真正被删掉的东西】
         *
         * 之前这里断言的是 'browserTimezone' 和 'resolvedOptions().timeZone'
         * —— 而这两个标识符在**修复前**也不存在, 所以这个测试在未修复的
         * 基线上同样会通过, 守不住任何东西。
         *
         * 真正被删掉的是这三个: 隐藏的 dst 字段、隐藏的 timezone 字段,
         * 以及往 timezone 字段里写的 getTimezoneOffset()。断言它们。
         *
         * 【为什么这一组是"钉拼写"却仍然该保留】因为它们是**负向**断言:
         * 这些字符串在**基线上确实存在** (基线的 write-js.php 里有 4 处
         * 匹配), 所以把其中任何一处加回来, 这个测试就会红。它守的是
         * "别把删掉的东西加回来", 而那正是这个改动要防的回归。
         *
         * 和上面那条被删掉的正向断言不同: 正向断言钉的是实现的写法,
         * 负向断言钉的是"某个东西不存在"。
         */
        $this->assertStringNotContainsString(
            'name="dst"',
            $source,
            'the publish form must not submit the dst offset any more'
        );

        $this->assertStringNotContainsString(
            'name="timezone"',
            $source,
            'the publish form must not submit a browser time zone any more'
        );

        $this->assertStringNotContainsString(
            'getTimezoneOffset()',
            $source,
            'the publish form must not derive an offset from the browser clock'
        );
    }

    /**
     * The publish date path must interpret the wall clock in the site zone.
     *
     * 【这条断言被删了, 因为它钉的是拼写而不是行为】
     *
     * 它原来断言源码里出现 'Timezone::wallToTimestamp($wall, $siteTimezone)'
     * —— 变量名改了、参数顺序换了, 这个断言就红, 而行为一点没变。反过来,
     * 它也满足于一段注释里的同样文字。
     *
     * 真正要守的性质是"作者填的墙钟按**站点**时区解释, 与作者所在的
     * 机器无关", 那条由 testPublishDateDoesNotDependOnTheSubmittingMachine
     * 守 —— 它真的换掉进程时区, 而且把转换改成用进程时区就会失败。
     *
     * 同理, 'request->get('browserTimezone'' 这条负向断言也被删了: 那个
     * 标识符在**基线上也不存在**, 所以它守不住任何东西。
     */

    /**
     * The wall clock conversion must agree with the platform, including on
     * the day the clocks move forward, where a local time does not exist.
     */
    public function testWallClockConversionMatchesThePlatform(): void
    {
        $cases = [
            ['America/Los_Angeles', '2021-01-15 12:00'],
            ['America/Los_Angeles', '2021-07-04 12:00'],
            ['America/Los_Angeles', '2021-03-14 02:30'],
            ['America/Los_Angeles', '2021-11-07 01:30'],
            ['Europe/Berlin', '2021-03-28 02:30'],
            ['Europe/Berlin', '2021-10-31 02:30'],
        ];

        foreach ($cases as [$zone, $wall]) {
            $native = (new DateTime($wall, new DateTimeZone($zone)))->getTimestamp();

            $this->assertSame(
                $native,
                Timezone::wallToTimestamp($wall, $zone),
                sprintf('%s %s must match the platform', $zone, $wall)
            );
        }
    }

    /**
     * The conversion must converge: a wall clock read back after the
     * conversion has to give the same value.
     */
    public function testTheConversionConverges(): void
    {
        $cases = [
            ['America/Los_Angeles', '2021-01-15 12:00'],
            ['America/Los_Angeles', '2021-07-04 12:00'],
            ['Europe/Berlin', '2021-07-04 12:00'],
            ['Asia/Shanghai', '2021-07-04 12:00'],
        ];

        foreach ($cases as [$zone, $wall]) {
            $options = $this->options($zone);

            $wallEpoch = $this->wallEpoch($wall);
            $utc = $options->timezoneToTimestamp($wallEpoch);

            $this->assertSame(
                $wall,
                date('Y-m-d H:i', $options->timestampToTimezone($utc)),
                sprintf('%s %s must survive the round trip', $zone, $wall)
            );
        }
    }

    /**
     * A local time that does not exist must be normalised the same way the
     * platform does it, and must not send the conversion into a loop.
     */
    public function testALocalTimeThatDoesNotExistIsNormalised(): void
    {
        $zone = 'America/Los_Angeles';
        $wall = '2021-03-14 02:30';

        $options = $this->options($zone);
        $wallEpoch = $this->wallEpoch($wall);
        $utc = $options->timezoneToTimestamp($wallEpoch);

        $this->assertSame(
            (new DateTime($wall, new DateTimeZone($zone)))->getTimestamp(),
            $utc,
            'the skipped local time must be normalised like the platform does'
        );
    }

    /**
     * The XML-RPC input side has to deal with two different literals.
     *
     * IXR\Date::getTimestamp() reads the literal with mktime(), so it
     * produces the epoch of the same wall clock in the **server** time
     * zone - with the server offset of the parsed date baked in.
     *
     * - date_created_gmt carries a **GMT** literal. Adding a server offset
     *   back is wrong, because the offset has to be the one of the parsed
     *   date, and the only scalar at hand (the request moment's offset)
     *   differs from it across the seasons. The literal is read as UTC
     *   instead, which is what the field means.
     * - dateCreated carries a **site local** literal, and the value is
     *   already the wall clock timestamp the converter expects, so the
     *   server offset must not be touched.
     */
    public function testXmlRpcInputUndoesTheServerLocalInterpretation(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/var/Widget/XmlRpc.php'
        );

        /**
         * 【只取 date_created_gmt 所在的那一块】
         *
         * 原来这三个正则带着 /s 和惰性的 .*?, 所以 'date_created_gmt' 之后
         * **文件里任何地方**的 getIso() 或 ' UTC' 都能满足它们 —— 输出侧
         * 就有这两样东西。于是这个断言实际上什么都没钉住: 把输入侧改成
         * 坏的, 它照样是绿的。
         *
         * 所以这里按大括号切出输入侧的那一块, 断言只在那一块里成立。
         */
        /**
         * 标记必须是**输入侧**那一个。文件里 date_created_gmt 出现了十几次,
         * 大部分在输出侧 ('date_created_gmt' => new Date(...)), 第一个出现
         * 就是输出侧 —— 用它当标记会切到完全无关的块。
         *
         * 输入侧是读请求的那一段: isset($struct['date_created_gmt'])。
         */
        $inputLines = self::linesContaining($source, "isset(\$struct['date_created_gmt'])");

        $this->assertCount(
            1,
            $inputLines,
            'the input side must be found exactly once'
        );

        /**
         * 【断言落在**那几行**上, 而不是整个文件】
         *
         * 整个文件里 getIso() 和 ' UTC' 都在输出侧出现过, 所以对着整个
         * 文件断言等于什么都没钉住。
         *
         * 这里取的是**含有那个输入侧标记的所有行** —— 匹配出来之后要
         * 在**这些行**里找 getIso(), 而不是跳到后面的某个大括号块。
         * 跳块的办法要靠偏移量去原文里定位, 而偏移量会因为注释被剪掉
         * 而对不上; 按行取就没有这个问题。
         */
        /**
         * 那一行只是入口, 真正读字面的代码在它下面几行 —— 所以取它
         * 往后的一小段窗口。
         */
        $inputSide = implode("\n", self::windowAfter($source, $inputLines[0], 20));

        $this->assertMatchesRegularExpression(
            '/getIso\(\)/',
            $inputSide,
            'a GMT literal is read as UTC, so the literal itself is used'
        );

        $this->assertMatchesRegularExpression(
            "/' UTC'/",
            $inputSide,
            'the GMT literal has to be interpreted as UTC'
        );

        /**
         * Adding a server offset back would use the offset of the request
         * moment, while mktime() used the offset of the parsed date.
         */
        $this->assertDoesNotMatchRegularExpression(
            '/getTimestamp\(\).*?\+ \$this->options->serverTimezone/s',
            $inputSide,
            'the request moment offset must not be added to a GMT literal'
        );

        /**
         * A site local literal must not go through mktime() any more.
         *
         * mktime() builds a server-local instant, so a literal that falls
         * in the server's own daylight saving gap comes back an hour
         * late. The literal is a site wall clock -- it never needs the
         * server's time zone.
         */
        /**
         * The block that reads the site local literal -- not the whole
         * file: the word "dateCreated" appears in the output side too,
         * and a greedy pattern would reach across unrelated code.
         *
         * 【为什么必须收窄】对着整个文件断言, 输出侧那句
         * 'dateCreated' => new Date(...) 就足以满足正向断言, 所以输入
         * 侧改坏了也不会被发现。
         */
        $dateLines = self::linesContaining($source, "isset(\$content['dateCreated'])");

        $this->assertCount(
            1,
            $dateLines,
            'the site local literal must be found exactly once'
        );

        $dateBlock = implode("\n", self::windowAfter($source, $dateLines[0], 20));

        $this->assertDoesNotMatchRegularExpression(
            "/\\\$content\['dateCreated'\]->getTimestamp\(\)/",
            $dateBlock,
            'a site local literal must not be read through mktime()'
        );

        /**
         * 【输入侧和输出侧要分开断言】
         *
         * 输入侧 (读客户端的字面) 用 wallLiteralToTimestamp(), 输出侧
         * (写给客户端的字面) 用 timezoneOffsetAt()。两段代码里各有一个
         * dateCreated, 所以对着**整个文件**断言时, 一侧的代码就能满足
         * 另一侧的断言 —— 改坏了一侧也不会被发现。
         *
         * 所以两块分开找, 分开断言。
         */
        $this->assertMatchesRegularExpression(
            '/wallLiteralToTimestamp\(/',
            $dateBlock,
            'a site local literal is parsed as a wall clock in the site time zone'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/->getTimestamp\(\)/',
            $dateBlock,
            'the input side must not read the literal as an instant'
        );

        /**
         * 输出侧是数组里的元素, 不是大括号块, 所以按大括号配平切出来的是
         * 后面整个方法。这里要的是**每一行** dateCreated 的输出。
         *
         * 【为什么是"每一行"】文件里有五处 dateCreated 输出 (页面、文章、
         * 列表、文章列表……)。只查其中一行的话, 别的行改坏了不会被发现 ——
         * 所以这里把每一行都拿出来, 每一行都得满足同样的条件。
         */
        /**
         * 只取**输出**的那些行: 键名后面跟着 =>。输入侧那一行是
         * isset($content['dateCreated']), 不是这样的写法。
         */
        $outputLines = self::linesContaining($source, "'dateCreated'");

        $outputLines = array_values(array_filter(
            $outputLines,
            function (string $line): bool {
                return strpos($line, "'dateCreated'") < strpos($line, '=>');
            }
        ));

        $this->assertGreaterThan(
            0,
            count($outputLines),
            'the output side must be found'
        );

        foreach ($outputLines as $line) {
            $this->assertMatchesRegularExpression(
                '/timezoneOffsetAt\(/',
                $line,
                'the output side writes the literal in the site time zone: ' . trim($line)
            );

            $this->assertDoesNotMatchRegularExpression(
                '/serverTimezone/',
                $line,
                'the server offset must not appear in the output side: ' . trim($line)
            );
        }
    }

    /**
     * The GMT literal has to be read as UTC, and the test runs the
     * production code to find out.
     *
     * The source assertions above check the shape of the code. This one
     * checks what the code does, on a server whose own zone observes
     * daylight saving - the case the old reading got wrong.
     */
    public function testTheGmtLiteralIsReadAsUtcWhateverTheServerZoneIs(): void
    {
        $original = date_default_timezone_get();

        try {
            $literal = '20210314T01:30:00Z';
            $expected = strtotime($literal);

            foreach (['UTC', 'Europe/Berlin', 'America/Los_Angeles'] as $serverZone) {
                date_default_timezone_set($serverZone);

                $this->assertSame(
                    $expected,
                    Timezone::gmtLiteralToTimestamp($literal),
                    sprintf('the literal must be read as UTC on a %s server', $serverZone)
                );
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * 调用安装器自身的默认选项函数，避免执行安装页面的数据库与输出副作用。
     * 真实默认值须保留固定 UTC+8；地区规则由管理员主动选择。
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAFreshInstallKeepsItsFixedOffsetUntilARegionIsChosen(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/install.php');
        $tokens = token_get_all($source);
        $required = ['install_get_lang', 'install_get_site_url', 'install_is_cli',
            'install_get_default_routers', 'install_get_default_options'];
        $declarations = [];

        // 只提取真实函数声明；字符串及注释中的花括号不参与代码块配对。
        for ($i = 0, $length = count($tokens); $i < $length; $i++) {
            if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
                continue;
            }
            $nameIndex = $i + 1;
            while ($nameIndex < $length && is_array($tokens[$nameIndex])
                && in_array($tokens[$nameIndex][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $nameIndex++;
            }
            if ($nameIndex >= $length || !is_array($tokens[$nameIndex])
                || $tokens[$nameIndex][0] !== T_STRING
                || !in_array($tokens[$nameIndex][1], $required, true)) {
                continue;
            }
            $name = $tokens[$nameIndex][1];
            $code = '';
            $depth = 0;
            $inBody = false;
            for ($end = $i; $end < $length; $end++) {
                $token = $tokens[$end];
                $code .= is_array($token) ? $token[1] : $token;
                if ($token === '{') {
                    $inBody = true;
                    $depth++;
                } elseif ($inBody && is_array($token)
                    && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) {
                    $depth++;
                } elseif ($inBody && $token === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $declarations[$name] = $code;
                        $i = $end;
                        break;
                    }
                }
            }
        }
        foreach ($required as $name) {
            $this->assertArrayHasKey($name, $declarations, 'the actual installer dependency must be available');
        }

        // install_get_site_url() 调用 install_is_cli()，因此一起加载真实第五个依赖。
        eval(implode("\n", $declarations));
        $_SERVER['TYPECHO_LANG'] = 'zh_CN';
        $_SERVER['TYPECHO_SITE_URL'] = 'http://example.com';
        $defaults = install_get_default_options();
        $this->assertSame('28800', $defaults['timezone'],
            'installation must preserve the original fixed-offset value and its string type');
    }

    /**
     * The conversion must not depend on the season the request happens in.
     *
     * mktime() embeds the server offset of the **parsed date**, while
     * idate('Z') gives the offset of the current moment. On a server whose
     * own zone observes daylight saving the two differ by an hour across
     * the seasons, so a conversion that adds one of them to the other
     * counts the server's own daylight saving twice.
     *
     * The test pins the request moment to the opposite season from the
     * parsed date, which is the case that used to be off by an hour.
     */
    public function testTheConversionDoesNotDependOnTheRequestSeason(): void
    {
        $original = date_default_timezone_get();
        date_default_timezone_set('America/Los_Angeles');

        try {
            /**
             * The server zone observes daylight saving itself - checked
             * against a fixed pair of dates, not against the current one.
             *
             * Comparing with idate('Z') would make the test depend on the
             * season it runs in, and it would fail every year from
             * November to March.
             */
            $winter = (new DateTime('2021-01-15', new DateTimeZone('America/Los_Angeles')))->getOffset();
            $summer = (new DateTime('2021-07-15', new DateTimeZone('America/Los_Angeles')))->getOffset();

            $this->assertNotSame(
                $winter,
                $summer,
                'this test needs a server zone that observes daylight saving'
            );

            $options = $this->options('America/Los_Angeles');

            /**
             * 【要真的把"请求季节"这个变量操控起来】
             *
             * 之前这里只把 Date::$serverTimezoneOffset 设成 -28800 就断言,
             * 但没有任何东西证明转换器**看到**了这个值 —— 如果实现改成
             * 忽略它, 照样通过。而且 mktime() 在 7 月 4 日用的是夏令时偏移
             * (-25200), 所以"请求在冬天"这个前提并没有被建立。
             *
             * 现在跑两遍: 一次请求在冬天 (-28800), 一次在夏天 (-25200),
             * 断言结果**相同**。这才是"不依赖请求季节"这个性质本身。
             */
            $wallEpoch = mktime(12, 30, 0, 7, 4, 2021);

            Date::$serverTimezoneOffset = -28800;
            $inWinter = $options->timezoneToTimestamp($wallEpoch);

            Date::$serverTimezoneOffset = -25200;
            $inSummer = $options->timezoneToTimestamp($wallEpoch);

            $this->assertSame(
                $inWinter,
                $inSummer,
                'the publish time must not shift with the season the request happens in'
            );

            /**
             * 而且结果必须等于按**站点时区**解释那个墙钟文本得到的时间 ——
             * 不管请求发生在哪个季节。
             */
            $viaDate = Timezone::wallToTimestamp('2021-07-04 12:30', 'America/Los_Angeles');

            $this->assertSame(
                $viaDate,
                $inWinter,
                'the summer publish date must be read in the site zone'
            );

            $this->assertSame(
                $viaDate,
                $inSummer,
                'the summer publish date must be read in the site zone'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The rendering side must not shift with the season the request happens
     * in.
     *
     * date() formats an epoch with the server offset **at that epoch**,
     * while idate('Z') gives the offset at the moment of the request. On a
     * server whose own zone observes daylight saving the two differ by an
     * hour across the seasons, so subtracting the request offset lands on
     * the wrong hour for half of the year.
     *
     * The oracle here is independent of the implementation: the site wall
     * clock of an instant is what the platform itself reports for that
     * instant in the site zone.
     */
    public function testTheRenderingSideDoesNotShiftWithTheRequestSeason(): void
    {
        $original = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $siteZones = [
                'Asia/Shanghai',
                'America/Los_Angeles',
                'Australia/Sydney',
                'Europe/Berlin',
                'UTC',
            ];

            $instants = [
                '2021-01-15 12:00:00',
                '2021-07-15 12:00:00',
            ];

            foreach ($siteZones as $siteZone) {
                $options = $this->options($siteZone);

                foreach ($instants as $instant) {
                    $utc = strtotime($instant . ' UTC');

                    /** The platform's own answer for that instant */
                    $wall = (new DateTime('@' . $utc))
                        ->setTimezone(new DateTimeZone($siteZone))
                        ->format('Y-m-d H:i');

                    $local = $options->timestampToTimezone($utc);

                    $this->assertSame(
                        $wall,
                        date('Y-m-d H:i', $local),
                        sprintf(
                            '%s must render %s in the site zone, whatever the request season is',
                            $siteZone,
                            $wall
                        )
                    );
                }
            }
        } finally {
            date_default_timezone_set($original);
        }
    }


    /**
     * Both converters must agree with the platform, on every server zone
     * and across the daylight saving switch.
     *
     * The oracle is the platform itself: the site wall clock of an instant
     * is what the platform reports for that instant in the site zone, and
     * a wall clock is turned into an instant by handing it to DateTime.
     *
     * The two switch days are the interesting ones:
     *
     * - spring forward: a stretch of wall clock does not exist, and PHP
     *   pushes it to the time after the switch
     * - fall back: a stretch of wall clock happens twice, and PHP takes
     *   the first occurrence
     */
    public function testBothConvertersAgreeWithThePlatform(): void
    {
        $original = date_default_timezone_get();

        try {
            $serverZones = [
                'UTC',
                'Europe/Berlin',
                'America/Los_Angeles',
                'Australia/Sydney',
                'Asia/Shanghai',
            ];

            $siteZones = [
                'Asia/Shanghai',
                'America/Los_Angeles',
                'Australia/Sydney',
                'Europe/Berlin',
                'UTC',
            ];

            /** Every third hour of a whole year, plus the switch days */
            $instants = [];

            for ($day = 0; $day < 365; $day += 7) {
                for ($hour = 0; $hour < 24; $hour += 3) {
                    $instants[] = strtotime('2021-01-01 00:00:00 UTC')
                        + $day * 86400 + $hour * 3600;
                }
            }

            foreach ([
                '2021-03-14 09:00:00',
                '2021-03-14 10:00:00',
                '2021-11-07 08:00:00',
                '2021-11-07 09:00:00',
            ] as $instant) {
                $instants[] = strtotime($instant . ' UTC');
            }

            foreach ($serverZones as $serverZone) {
                date_default_timezone_set($serverZone);

                foreach ($siteZones as $siteZone) {
                    $options = $this->options($siteZone);

                    foreach ($instants as $utc) {
                        $wall = (new DateTime('@' . $utc))
                            ->setTimezone(new DateTimeZone($siteZone))
                            ->format('Y-m-d H:i');

                        /** The platform's own reading of that wall clock */
                        $expected = (int) (new DateTime(
                            $wall,
                            new DateTimeZone($siteZone)
                        ))->getTimestamp();

                        $this->assertSame(
                            $expected,
                            Timezone::wallToTimestamp($wall, $siteZone),
                            sprintf('%s %s must match the platform', $serverZone, $siteZone)
                        );

                        $this->assertSame(
                            $expected,
                            $options->timezoneToTimestamp(
                                (int) (new DateTime(
                                    $wall,
                                    new DateTimeZone($serverZone)
                                ))->getTimestamp()
                            ),
                            sprintf(
                                '%s %s must survive the round trip',
                                $serverZone,
                                $siteZone
                            )
                        );

                        $this->assertSame(
                            $wall,
                            date('Y-m-d H:i', $options->timestampToTimezone($utc)),
                            sprintf(
                                '%s %s must render %s in the site zone',
                                $serverZone,
                                $siteZone,
                                $wall
                            )
                        );
                    }
                }
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The legacy numeric time zone still accepts the dst parameter the old
     * front end sent, and that parameter is in **minutes**.
     *
     * The old code subtracted it as if it were seconds, which is where the
     * 59 minute error came from. A site that has not migrated keeps the
     * fixed offset path, so the minutes have to be applied as minutes.
     */
    /**
     * The legacy dst parameter is in minutes, not seconds.
     *
     * 【为什么这里只做负向检查】
     *
     * 这条断言是"源码里不能出现把分钟当秒减的写法" —— 也就是
     * `$created - $dstOffset;`。正向的行为由下面那条
     * (testTheLegacyDstParameterMovesTheInstantByMinutes) 走真实的生产
     * 代码来验: 提交 60 分钟, 瞬间必须正好移动一小时。
     *
     * 原来这里还有一条 `$this->assertSame(3600, $dst * 60)`, 那是对测试
     * 自己的字面量做算术 —— 无论实现对错都会通过, 等于没有断言。
     */
    public function testTheLegacyDstParameterIsNotSubtractedAsSeconds(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/var/Widget/Contents/EditTrait.php'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\$created - \$dstOffset;/',
            $source,
            'subtracting the minutes as if they were seconds is the original bug'
        );
    }

    /**
     * The legacy dst parameter has to move the instant by minutes, and the
     * test calls the production code to find out.
     *
     * The assertion above only reads the source text. That is not enough:
     * a change that keeps the text and alters the behaviour slips past it.
     * Here the real method runs, with the request the old front end would
     * have sent.
     */
    public function testTheLegacyDstParameterMovesTheInstantByMinutes(): void
    {
        /** A site that still has the old fixed offset */
        $siteTimezone = '-28800';

        Date::setTimezoneOffset((int) $siteTimezone);

        $options = new TestOptions(
            new Request(new HttpRequest()),
            new Response(new HttpRequest(), new HttpResponse()),
            new Config()
        );

        $options->push(['timezone' => $siteTimezone]);

        /** 60 minutes of daylight saving, the way the old front end sent it */
        $withoutDst = $this->createdFromDate($options, '2021-07-04 12:00:00', 0);
        $withDst = $this->createdFromDate($options, '2021-07-04 12:00:00', 60);

        $this->assertSame(
            3600,
            $withoutDst - $withDst,
            '60 minutes of daylight saving must move the instant by one hour'
        );

        // 独立固定偏移 oracle：12:00 -08:00 就是 20:00 UTC；dst 再减一小时。
        $nativeFixed = new DateTimeImmutable('2021-07-04 12:00:00', new DateTimeZone('-08:00'));
        $this->assertSame($nativeFixed->getTimestamp(), $withoutDst,
            'the fixed-offset wall clock must resolve to the correct absolute instant');
        $this->assertSame($nativeFixed->sub(new DateInterval('PT1H'))->getTimestamp(), $withDst,
            'the legacy dst adjustment must preserve the absolute UTC baseline');
    }

    /**
     * A migrated site ignores the legacy dst parameter.
     *
     * The old front end sent `dst` because the server could not express
     * daylight saving. A site that has migrated stores an IANA name, so the
     * server already knows the offset for the publish date and must not
     * apply the client's number a second time.
     *
     * This matters for a browser that still holds a cached copy of the old
     * page: it keeps sending `dst` while the server has already moved on.
     */
    public function testAMigratedSiteIgnoresTheLegacyDstParameter(): void
    {
        /** A site that has been migrated to an IANA name */
        $siteTimezone = 'America/Los_Angeles';

        $originalOffset = Date::$timezoneOffset;
        $originalName = Date::$timezoneName;

        try {
            Date::setTimezone($siteTimezone);

            $options = new TestOptions(
                new Request(new HttpRequest()),
                new Response(new HttpRequest(), new HttpResponse()),
                new Config()
            );

            $options->push(['timezone' => $siteTimezone]);

            $withoutDst = $this->createdFromDate($options, '2021-07-04 12:00:00', 0);
            $withDst = $this->createdFromDate($options, '2021-07-04 12:00:00', 60);

            $this->assertSame(
                $withoutDst,
                $withDst,
                'a migrated site must not apply the legacy dst parameter'
            );

            /**
             * And the instant is the site wall clock read in the site zone.
             */
            $this->assertSame(
                Timezone::wallToTimestamp('2021-07-04 12:00:00', $siteTimezone),
                $withoutDst,
                'the publish time is the site wall clock read in the site zone'
            );
        } finally {
            /**
             * 【必须还原】Date::$timezoneName / $timezoneOffset 是**全局
             * 静态量**, 不还原的话后面的测试会看到一个被改过的站点
             * 时区 —— 那会让与本测试无关的测试变红, 而且很难查。
             */
            Date::$timezoneOffset = $originalOffset;
            Date::$timezoneName = $originalName;
        }
    }

    /**
     * The date field is free text, so a month or a day without the leading
     * zero has to work.
     *
     * The code this replaced passed the raw text to strtotime(), which is
     * lenient about the padding. Requiring '\d{4}-\d{2}-\d{2}' made
     * '2021-7-1' look like a malformed date, and the author's date was
     * then silently ignored and the post kept its old publish time.
     */
    public function testTheDateFieldAcceptsAMonthOrDayWithoutTheLeadingZero(): void
    {
        Date::setTimezoneOffset(28800);

        $options = new TestOptions(
            new Request(new HttpRequest()),
            new Response(new HttpRequest(), new HttpResponse()),
            new Config()
        );

        $options->push(['timezone' => '28800']);

        $padded = $this->createdFromDate($options, '2021-07-04 12:00:00', 0);
        $unpadded = $this->createdFromDate($options, '2021-7-4 12:00:00', 0);

        $this->assertSame(
            $padded,
            $unpadded,
            'a date without the leading zeros must give the same instant'
        );

        /**
         * And a date that is really invalid must still be rejected.
         *
         * Rejected means "fall back": an edit keeps its publish time and
         * a new post uses the current time. It must never be 0 — that is
         * 1970.
         */
        $impossible = $this->createdFromDate($options, '2021-13-45 12:00:00', 0);

        $this->assertNotSame(
            0,
            $impossible,
            'an impossible date must not become 1970'
        );

        $this->assertEqualsWithDelta(
            time(),
            $impossible,
            60,
            'an impossible date must fall back to the current time'
        );
    }

    /**
     * Call the production method with a request that carries the given
     * values.
     */
    private function createdFromDate(
        Options $options,
        string $date,
        int $dst
    ): int {
        $httpRequest = HttpRequest::getInstance();

        /** The widget request carries the parameters the front end sends */
        $widgetRequest = new Request($httpRequest);
        $widgetRequest->setParams([
            'date' => $date,
            'dst' => (string) $dst,
        ]);

        $widget = new TestCreatedFromDate(
            $widgetRequest,
            new Response($httpRequest, new HttpResponse()),
            new Config()
        );

        $widget->setTestOptions($options);

        return $widget->callCreatedFromDate();

    }

    /**
     * What is written has to come back the same when it is read.
     *
     * The server's own wall clock skips an hour in spring. A site wall
     * clock that lands in that gap has no exact epoch - the platform
     * normalises it. The write side and the read side use the same
     * platform call, so they have to agree: a post written at one instant
     * must be read back at that same instant.
     *
     * 本矩阵从墙钟字符串开始，fold 只覆盖 PHP 默认选择的那次瞬间。
     * 两个 UTC occurrences 与非零秒数由 TimezonePublishPathTest 中
     * repeatedHourPosts 的两个真实既有文章用例分别覆盖（共四个 cases）；
     * 这里不声称同一个墙钟字符串能够区分这两次 UTC。
     */
    public function testWhatIsWrittenComesBackTheSameWhenItIsRead(): void
    {
        $original = date_default_timezone_get();

        try {
            $serverZones = [
                'UTC',
                'Europe/Berlin',
                'America/New_York',
                'America/Los_Angeles',
                'Australia/Sydney',
            ];

            $siteZones = [
                'Asia/Shanghai',
                'America/Los_Angeles',
                'Europe/Berlin',
                'Australia/Sydney',
                'UTC',
            ];

            /**
             * The days the clocks move.
             *
             * The first two are the American switch days, the next two the
             * European ones. Australia/Sydney moves on 2021-04-04 and
             * 2021-10-03, and it is one of the zones under test, so its
             * days are in the list too — its daylight saving is in the
             * southern summer, the opposite of the others.
             */
            $switchDays = [
                '2021-03-14',
                '2021-11-07',
                '2021-03-28',
                '2021-10-31',
                '2021-04-04',
                '2021-10-03',
            ];

            /**
             * 【逐小时, 而且要落在空洞和重复里】
             *
             * 每三小时取一个时刻会跳过 02:30 —— 那正是洛杉矶和柏林在
             * 春季跳过的那一小时 (墙钟 02:00-02:59 根本不存在), 也跳过
             * 01:30 —— 秋季重复的那一小时 (同一个墙钟出现两次)。所以这里
             * 逐小时走一遍, 并且把这两个时刻**单独**加进来。
             */
            $wallTimes = [];

            for ($hour = 0; $hour < 24; $hour++) {
                $wallTimes[] = sprintf('%02d:30:00', $hour);
            }

            $wallTimes[] = '02:30:00';
            $wallTimes[] = '01:30:00';
            $wallTimes[] = '02:00:00';
            $wallTimes[] = '02:59:59';

            foreach ($serverZones as $serverZone) {
                date_default_timezone_set($serverZone);

                foreach ($siteZones as $siteZone) {
                    foreach ($switchDays as $day) {
                        foreach ($wallTimes as $wallTime) {
                            $wall = $day . ' ' . $wallTime;

                            /**
                             * 【这个墙钟在**服务器**时区存在吗】
                             *
                             * 往返的中是服务器本地的墙钟, 而服务器本地
                             * 的墙钟用 mktime() 解释 —— 落在服务器自己的
                             * 夏令时缺口里的那个时刻**不存在**, mktime()
                             * 把它向前归一化一小时, 往返自然差 3600 秒。
                             *
                             * 那是上游的语义 (本改动不拒绝日历上不存在的
                             * 时刻), 所以这个往返测试只管**存在**的墙钟;
                             * 缺口里的时刻由下面那条测试单独说明。
                             */
                            if (!self::wallClockExists($wall, $serverZone)) {
                                continue;
                            }

                            $instant = Timezone::wallToTimestamp($wall, $siteZone);

                            $wallEpoch = Timezone::toLocalTimestamp(
                                $instant,
                                $siteZone,
                                idate('Z')
                            );

                            $back = Timezone::fromLocalTimestamp(
                                $wallEpoch,
                                $siteZone,
                                idate('Z')
                            );

                            $this->assertSame(
                                $instant,
                                $back,
                                sprintf(
                                    '%s / %s / %s must survive the round trip',
                                    $serverZone,
                                    $siteZone,
                                    $wall
                                )
                            );
                        }
                    }
                }
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The archive groups posts by the date rendered in the site zone, so the
     * timestamp it formats must be the site wall clock.
     */
    public function testArchiveGroupingUsesTheSiteWallClock(): void
    {
        $combinations = [
            ['UTC', 'Asia/Shanghai'],
            ['America/Los_Angeles', 'UTC'],
            ['America/Los_Angeles', 'America/Los_Angeles'],
            ['Europe/Berlin', 'Asia/Shanghai'],
            ['Australia/Sydney', 'UTC'],
        ];

        $original = date_default_timezone_get();

        try {
            foreach ($combinations as [$siteZone, $serverZone]) {
                date_default_timezone_set($serverZone);

                $options = $this->options($siteZone);

                /** A post created at this instant */
                $created = strtotime('2021-07-04 19:00:00 UTC');

                $timeStamp = $options->timestampToTimezone($created);

                $this->assertSame(
                    gmdate(
                        'Y-m-d H:i:s',
                        $created + Timezone::offsetAt($siteZone, $created)
                    ),
                    date('Y-m-d H:i:s', $timeStamp),
                    sprintf(
                        'site %s on a %s host must group by the site wall clock',
                        $siteZone,
                        $serverZone
                    )
                );
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * Around a daylight saving switch the grouping key has to follow the
     * calendar, so a post before and after the switch land on different days.
     */
    public function testArchiveGroupingFollowsTheCalendarAcrossASwitch(): void
    {
        $original = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            $siteZone = 'America/Los_Angeles';
            $options = $this->options($siteZone);

            $before = $options->timestampToTimezone(
                strtotime('2021-03-13 20:00:00 UTC')
            );

            $after = $options->timestampToTimezone(
                strtotime('2021-03-14 20:00:00 UTC')
            );

            $this->assertSame('2021-03-13', date('Y-m-d', $before));
            $this->assertSame('2021-03-14', date('Y-m-d', $after));
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The publish form has two entry paths - a date field and separate
     * year/month/day fields. Both carry a site wall clock, so both must
     * produce the same instant.
     */
    public function testBothPublishPathsProduceTheSameInstant(): void
    {
        $combinations = [
            ['UTC', 'UTC'],
            ['UTC', 'Asia/Shanghai'],
            ['America/Los_Angeles', 'UTC'],
            ['America/Los_Angeles', 'America/Los_Angeles'],
            ['Europe/Berlin', 'Asia/Shanghai'],
            ['Australia/Sydney', 'UTC'],
            ['Asia/Shanghai', 'America/Los_Angeles'],
        ];

        $original = date_default_timezone_get();

        try {
            foreach ($combinations as [$siteZone, $serverZone]) {
                date_default_timezone_set($serverZone);

                $options = $this->options($siteZone);

                /** The date field carries "Y-m-d H:i" */
                $viaDate = Timezone::wallToTimestamp('2021-07-04 12:30', $siteZone);

                /** The year/month/day path builds the same wall clock with mktime() */
                $wallEpoch = mktime(12, 30, 0, 7, 4, 2021);
                $viaParts = $options->timezoneToTimestamp($wallEpoch);

                $this->assertSame(
                    $viaDate,
                    $viaParts,
                    sprintf(
                        'site %s on a %s host: both paths must agree',
                        $siteZone,
                        $serverZone
                    )
                );
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * On the day the clocks move forward the two paths must still agree with
     * the platform, because the local time does not exist.
     */
    public function testBothPublishPathsAgreeOnADaylightSavingSwitch(): void
    {
        $original = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            $siteZone = 'America/Los_Angeles';
            $options = $this->options($siteZone);

            $viaDate = Timezone::wallToTimestamp('2021-03-14 02:30', $siteZone);
            $viaParts = $options->timezoneToTimestamp(mktime(2, 30, 0, 3, 14, 2021));

            $this->assertSame($viaDate, $viaParts);
            $this->assertSame(
                (new DateTime('2021-03-14 02:30', new DateTimeZone($siteZone)))->getTimestamp(),
                $viaDate,
                'the skipped local time must be normalised like the platform does'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The archive date range is built with mktime(), which produces the
     * epoch of the wall clock **in the server time zone**. The converter has
     * to read it back as the same wall clock in the site zone.
     *
     * Getting the server offset wrong here shifts the whole range, and the
     * mistake is invisible on a UTC host, so several combinations are pinned.
     */
    public function testArchiveDateRangeKeepsTheSameWallClock(): void
    {
        $combinations = [
            ['UTC', 'Asia/Shanghai'],
            ['Asia/Shanghai', 'UTC'],
            ['America/Los_Angeles', 'America/Los_Angeles'],
            ['America/Los_Angeles', 'Asia/Shanghai'],
            ['Europe/Berlin', 'America/Los_Angeles'],
            ['Australia/Sydney', 'UTC'],
        ];

        $original = date_default_timezone_get();

        try {
            foreach ($combinations as [$siteZone, $serverZone]) {
                date_default_timezone_set($serverZone);

                /** The production code reads the offset from idate('Z') */
                Date::setTimezone($siteZone);

                $options = $this->options($siteZone);

                /** Archive: mktime(0, 0, 0, 7, 4, 2021) */
                $wallEpoch = mktime(0, 0, 0, 7, 4, 2021);
                $utc = $options->timezoneToTimestamp($wallEpoch);

                $siteWall = gmdate(
                    'Y-m-d H:i:s',
                    $utc + Timezone::offsetAt($siteZone, $utc)
                );

                $this->assertSame(
                    '2021-07-04 00:00:00',
                    $siteWall,
                    sprintf(
                        'site %s on a %s host must keep the wall clock',
                        $siteZone,
                        $serverZone
                    )
                );
            }
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The XML-RPC input side must produce the right instant on a host that
     * does not run in UTC.
     *
     * The direction of the correction is easy to get backwards, and the
     * mistake is invisible on a UTC host, so it is pinned here.
     */
    public function testXmlRpcInputIsCorrectOnANonUtcHost(): void
    {
        $original = date_default_timezone_get();
        date_default_timezone_set('Asia/Shanghai');

        try {
            $serverOffset = 28800;

            /** The client sends 12:00 GMT */
            $date = new \IXR\Date('20210704T12:00:00');

            // Common 的自动加载器同时注册旧别名，真实 XML 编码仍须识别日期。
            $value = new \IXR\Value($date);
            $this->assertStringContainsString(
                '<dateTime.iso8601>20210704T12:00:00</dateTime.iso8601>',
                $value->getXml(),
                'autoloaded IXR dates must retain their XML-RPC date encoding'
            );

            $this->assertSame(
                '2021-07-04 12:00:00',
                date('Y-m-d H:i:s', $date->getTimestamp()),
                'IXR\Date reads the literal as a server local time'
            );

            /**
             * 【GMT 字面按**它自带的**偏移解析, 不加服务器偏移】
             *
             * 之前这里断言的是"把请求时刻的服务器偏移加回去, 得到 GMT
             * 瞬间" —— 那正是本改动**移除**的做法: date_created_gmt
             * 已经带了自己的时区后缀, 再加一次服务器偏移就会把它平移。
             *
             * 所以现在走真正的解析路径: gmtLiteralToTimestamp() 按字面
             * 自带的偏移解析, 跟服务器在哪个时区无关。
             */
            $this->assertSame(
                strtotime('2021-07-04 12:00:00 UTC'),
                \Typecho\Timezone::gmtLiteralToTimestamp('20210704T12:00:00Z'),
                'a GMT literal is read as UTC, whatever the server zone is'
            );

            $this->assertSame(
                strtotime('2021-07-04 12:00:00 UTC'),
                \Typecho\Timezone::gmtLiteralToTimestamp('2021-07-04T12:00:00Z'),
                'the extended shape gives the same instant'
            );

            /**
             * 带数字偏移的字面按那个偏移解析, 同样不碰服务器偏移。
             */
            $this->assertSame(
                strtotime('2021-07-04 04:00:00 UTC'),
                \Typecho\Timezone::gmtLiteralToTimestamp('2021-07-04T12:00:00+08:00'),
                'a numeric offset in the literal is honoured'
            );

            /** The site local literal must reach the site zone conversion untouched */
            $options = $this->options('America/Los_Angeles');
            $wallClock = $date->getTimestamp();

            $this->assertSame(
                '2021-07-04 19:00:00',
                gmdate('Y-m-d H:i:s', $options->timezoneToTimestamp($wallClock)),
                'the site local literal must be read in the site zone'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * A format that carries a time zone designator must carry the site
     * offset, not UTC.
     *
     * The wall clock values are right, but gmdate() writes +00:00 into
     * every designator. A consumer that parses the result back lands on
     * a different instant -- an hour or a whole time zone away.
     *
     * The <time datetime="..."> attribute, the RSS <pubDate> and the
     * JSON-LD datePublished all go through this path.
     */
    public function testAFormatWithATimeZoneDesignatorCarriesTheSiteOffset()
    {
        $original = date_default_timezone_get();

        try {
            date_default_timezone_set('UTC');

            /**
             * 2021-07-04 15:40:00 UTC, which is 23:40 in Shanghai.
             */
            $instant = 1625413200;

            \Typecho\Date::setTimezone('Asia/Shanghai');
            $date = new \Typecho\Date($instant);

            $this->assertSame(
                '2021-07-04T23:40:00+08:00',
                $date->format('c'),
                'the designator must be the site offset'
            );

            $this->assertSame(
                $instant,
                strtotime($date->format('c')),
                'parsing the result back must land on the same instant'
            );

            $this->assertSame(
                $instant,
                strtotime($date->format('r')),
                'parsing the RFC 2822 form back must land on the same instant'
            );

            $this->assertSame(
                $instant,
                (int) $date->format('U'),
                'the unix timestamp must be the real instant, not the wall '
                . 'clock value'
            );

            $this->assertSame(
                'Asia/Shanghai',
                $date->format('e'),
                'the zone identifier must be the site zone'
            );

            /**
             * The plain formats stay the site wall clock.
             */
            $this->assertSame(
                '2021-07-04 23:40:00',
                $date->format('Y-m-d H:i:s'),
                'the wall clock must not change'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The rendered site wall clock must not depend on the server's own
     * wall clock.
     *
     * The server's clock skips an hour on its own switch day. If the
     * rendering goes through the server's date(), every instant inside
     * that skipped hour is off by one -- and the site sees a time that
     * never existed on the server.
     */
    public function testTheRenderedWallClockDoesNotFollowTheServersOwnClock()
    {
        $original = date_default_timezone_get();

        try {
            /**
             * Berlin skips 02:00-02:59 on 2021-03-28. The site is UTC.
             */
            date_default_timezone_set('Europe/Berlin');

            $instant = strtotime('2021-03-28 02:30:00 UTC');

            \Typecho\Date::setTimezone('UTC');
            $date = new \Typecho\Date($instant);

            $this->assertSame(
                '2021-03-28 02:30:00',
                $date->format('Y-m-d H:i:s'),
                'the site wall clock must be the site wall clock, whatever '
                . 'the server clock did that morning'
            );

            $this->assertSame(
                '2021-03-28',
                $date->year . '-' . $date->month . '-' . $date->day,
                'the calendar fields must come from the same wall clock'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The calendar fields and the relative wording come from the same
     * wall clock as format().
     *
     * format() is only one of the ways a site shows a time. The relative
     * wording ("yesterday 14:30") and the calendar fields have to agree
     * with it, or the site says two different things about one post.
     */
    public function testTheRelativeWordingUsesTheSameWallClock()
    {
        $original = date_default_timezone_get();

        try {
            date_default_timezone_set('Europe/Berlin');

            /**
             * 2021-03-28 02:30 UTC is inside the hour Berlin skips.
             */
            $instant = strtotime('2021-03-28 02:30:00 UTC');

            \Typecho\Date::setTimezone('UTC');

            $date = new \Typecho\Date($instant);

            /**
             * The wording is computed from the wall clock, so "yesterday"
             * and the hour must be the site's, not the server's.
             *
             * dateWord() is called with the wall clock values, so the
             * wording it produces is the site's. Yesterday's 02:30 site
             * time is "yesterday 02:30" -- with the server's clock the
             * hour would come out as 01:30.
             */
            $wall = \Typecho\Timezone::wallTimestamp($instant, 'UTC');
            $wallNow = $wall + 86400;

            $word = \Typecho\I18n::dateWord($wall, $wallNow, $wallNow - $wall);

            $this->assertSame(
                _t('昨天 %s', '02:30'),
                $word,
                'exactly one site calendar day earlier must be yesterday at 02:30'
            );

            /**
             * And the calendar fields must not disagree with format().
             */
            $this->assertSame(
                gmdate('Y-m-d', $instant),
                $date->year . '-' . $date->month . '-' . $date->day,
                'the calendar fields must be the site calendar'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The relative wording must not depend on the server's own time zone
     * either.
     *
     * dateWord() receives wall clock values, but inside it still had to
     * read the calendar fields. If those are read with the server's
     * date(), "yesterday" is decided by the server's calendar, and the
     * hour shown next to it comes from a different clock.
     */
    public function testTheRelativeWordingIsTheSameOnEveryServer()
    {
        $original = date_default_timezone_get();

        $servers = ['UTC', 'Europe/Berlin', 'America/New_York', 'Australia/Sydney', 'Pacific/Apia'];
        $bad = [];

        try {
            /**
             * Moments that land on a day boundary, where the calendar
             * fields actually decide the wording.
             */
            $moments = [];
            for ($t = strtotime('2021-01-01 00:00:00 UTC');
                 $t < strtotime('2022-01-01 00:00:00 UTC');
                 $t += 3600) {
                $moments[] = $t;
            }

            /** One table for every server, so they can be compared */
            $byServer = [];

            foreach ($servers as $server) {
                date_default_timezone_set($server);

                foreach ($moments as $moment) {
                    $wall = \Typecho\Timezone::wallTimestamp($moment, 'UTC');
                    $word = \Typecho\I18n::dateWord($wall, $wall + 3600, 3600);

                    $key = gmdate('Y-m-d H:i:s', $moment);
                    if (isset($byServer[$key]) && $byServer[$key] !== $word) {
                        $bad[] = sprintf(
                            'instant %s: server UTC gave "%s", server %s gave "%s"',
                            $key,
                            $byServer[$key],
                            $server,
                            $word
                        );
                    } else {
                        $byServer[$key] = $word;
                    }
                }
            }
        } finally {
            date_default_timezone_set($original);
        }

        $this->assertSame(
            [],
            $bad,
            "the relative wording must be the same on every server:\n" . implode("\n", array_slice($bad, 0, 10))
        );
    }

    /**
     * The archive grouping key is the site's calendar.
     *
     * A post published at 02:30 UTC on the day Berlin skips 02:00-02:59
     * belongs to 28 March in the site's calendar. If the key goes through
     * the server's date(), that one post lands in the wrong month.
     */
    public function testTheArchiveGroupingKeyIsTheSiteCalendar()
    {
        $original = date_default_timezone_get();

        $servers = ['UTC', 'Europe/Berlin', 'America/New_York', 'Australia/Sydney', 'Pacific/Apia'];
        $bad = [];

        try {
            $moments = [];
            for ($t = strtotime('2021-01-01 00:00:00 UTC');
                 $t < strtotime('2022-01-01 00:00:00 UTC');
                 $t += 3600) {
                $moments[] = $t;
            }

            /**
             * The results of every server go into ONE table, so the servers
             * can be compared with each other. Resetting it per server
             * would compare nothing.
             */
            $byServer = [];

            foreach ($servers as $server) {
                date_default_timezone_set($server);

                foreach ($moments as $moment) {
                    $key = \Widget\Contents\Post\Date::groupKey($moment, 'Y-m-d', 'UTC');
                    $month = \Widget\Contents\Post\Date::groupKey($moment, 'Y-m', 'UTC');

                    $id = gmdate('Y-m-d H:i:s', $moment);
                    $value = $key . '|' . $month;

                    if (isset($byServer[$id]) && $byServer[$id] !== $value) {
                        $bad[] = sprintf(
                            'instant %s: server UTC gave "%s", server %s gave "%s"',
                            $id,
                            $byServer[$id],
                            $server,
                            $value
                        );
                    } else {
                        $byServer[$id] = $value;
                    }
                }
            }
        } finally {
            date_default_timezone_set($original);
        }

        $this->assertSame(
            [],
            $bad,
            "the archive grouping key must be the same on every server:\n" . implode("\n", array_slice($bad, 0, 10))
        );
    }

    /**
     * The archive grouping key is the **site** calendar.
     *
     * 【为什么上面那条测试不够】上面的测试把**站点**时区一直传成 'UTC',
     * 所以它证明的是"服务器时区不影响分组键" —— 那是必要的, 但它不是
     * 这个测试的名字所说的性质。一个完全忽略站点时区参数的 groupKey()
     * (永远按 UTC 格式化) 也能通过上面那条。
     *
     * 这里换掉**站点**时区: 同一个瞬间, 站点在 UTC 和在
     * America/Los_Angeles 上属于不同的日子, 所以分组键必须不同, 而且
     * 必须等于站点当地的日期。
     */
    public function testTheArchiveGroupingKeyFollowsTheSiteCalendar(): void
    {
        /**
         * 2021-07-04 03:00 UTC —— 在洛杉矶还是 7 月 3 日。
         */
        $moment = strtotime('2021-07-04 03:00:00 UTC');

        $inUtc = \Widget\Contents\Post\Date::groupKey($moment, 'Y-m-d', 'UTC');
        $inLosAngeles = \Widget\Contents\Post\Date::groupKey($moment, 'Y-m-d', 'America/Los_Angeles');

        $this->assertSame(
            '2021-07-04',
            $inUtc,
            'the grouping key must be the site date in the site zone'
        );

        $this->assertSame(
            '2021-07-03',
            $inLosAngeles,
            'a site further west must group the post under its own date'
        );

        $this->assertNotSame(
            $inUtc,
            $inLosAngeles,
            'the grouping key must follow the site zone, not always be UTC'
        );
    }

    /**
     * The wording for a date far in the past reads the year from the
     * site's calendar.
     *
     * "1月1日" versus "2021年1月1日" is the difference between the year
     * branch and the fallback. The year has to be the site's year: on a
     * server twelve hours behind, the first hours of January are still
     * the previous year over there.
     */
    public function testTheYearInTheWordingIsTheSiteYear()
    {
        $original = date_default_timezone_get();

        $servers = ['UTC', 'Etc/GMT+12', 'Pacific/Kiritimati', 'America/New_York', 'Asia/Tokyo'];
        $bad = [];

        try {
            /**
             * Across a year boundary: the site year and the server year can
             * disagree, and the wording has to show the site's.
             */
            $from = \Typecho\Timezone::wallTimestamp(strtotime('2020-12-31 20:00:00 UTC'), 'UTC');
            $now = \Typecho\Timezone::wallTimestamp(strtotime('2021-06-01 12:00:00 UTC'), 'UTC');

            foreach ($servers as $server) {
                date_default_timezone_set($server);

                $word = \Typecho\I18n::dateWord($from, $now, $now - $from);

                if (strpos($word, '2020') === false) {
                    $bad[] = sprintf(
                        'server %s gave "%s" -- the site year 2020 is missing',
                        $server,
                        $word
                    );
                }
            }
        } finally {
            date_default_timezone_set($original);
        }

        $this->assertSame([], $bad, "the year must come from the site calendar:\n" . implode("\n", $bad));
    }

    /**
     * The archive handler refuses a year it cannot build a window for.
     *
     * The handler accepts years 101..9999. PHP's two-digit-year remapping
     * excludes 0..100. The upper boundary of 9999-12-31 is 10000-01-01;
     * native DateTime::setDate() supports that boundary in the site zone,
     * even though the ordinary wall-clock string parser cannot read it.
     */
    public function testTheArchiveHandlerRefusesAYearItCannotBuildAWindowFor(): void
    {
        /**
         * 【边界算不出来的时候必须交回 null】
         *
         * 只测 isValidArchiveYear() 是不够的 —— 那是静态谓词, 没人
         * 证明调用方真的用了它。这里直接问"边界算得出来吗"。
         */
        $this->assertNull(
            \Widget\Archive::archiveBounds(0, 1, 1),
            'year zero must not produce a window'
        );

        $this->assertNull(
            \Widget\Archive::archiveBounds(-1, 0, 0),
            'a negative year must not produce a window'
        );

        /**
         * 9999 是合法年份；五位年上界通过原生 DateTime::setDate() 构造，
         * 保留站点时区语义，不依赖字符串解析器的四位年限制。
         */
        $this->assertSame(
            [12, 31, 12, 31],
            \Widget\Archive::archiveBounds(9999, 12, 31),
            'the last year the wall clock can hold'
        );

        /**
         * 而能算出来的时候, 边界必须是对的。
         */
        $this->assertSame(
            [1, 1, 12, 31],
            \Widget\Archive::archiveBounds(2021, 0, 0),
            'a year archive runs from the first of January to the last of December'
        );

        $this->assertSame(
            [7, 4, 7, 4],
            \Widget\Archive::archiveBounds(2021, 7, 4),
            'a day archive is that day'
        );

        $this->assertSame(
            [7, 1, 7, 31],
            \Widget\Archive::archiveBounds(2021, 7, 0),
            'a month archive runs from the first to the last of that month'
        );

        $this->assertSame(
            [2, 1, 2, 29],
            \Widget\Archive::archiveBounds(2020, 2, 0),
            'February of a leap year has 29 days'
        );

        $this->assertSame(
            [2, 1, 2, 28],
            \Widget\Archive::archiveBounds(2021, 2, 0),
            'February of an ordinary year has 28 days'
        );
    }

    public function testTheArchiveYearPredicateItself(): void
    {
        /**
         * 【下界是 101】
         *
         * gmmktime() 套用 PHP 的两位年规则: 0 到 100 会按 PHP 的两位年规则映射到其它世纪。所以 gmmktime(0, 0, 0, 2, 1, 1) 给出 2001-02-01 而不是
         * 公元 1 年 —— 归档会静默显示另一个世纪。
         */
        $this->assertFalse(
            \Widget\Archive::isValidArchiveYear(69),
            'the two digit year rule would move this one into the next century'
        );

        $this->assertTrue(
            \Widget\Archive::isValidArchiveYear(101),
            'the first year the two digit year rule leaves alone'
        );

        $this->assertFalse(
            \Widget\Archive::isValidArchiveYear(1),
            'the year 1 is not a year this code can build'
        );

        $this->assertFalse(
            \Widget\Archive::isValidArchiveYear(100),
            'the year immediately below the supported lower bound is rejected'
        );

        $this->assertTrue(
            \Widget\Archive::isValidArchiveYear(2021),
            'an ordinary year is a year'
        );

        $this->assertTrue(
            \Widget\Archive::isValidArchiveYear(9999),
            'the last year the window can be built for'
        );

        $this->assertFalse(
            \Widget\Archive::isValidArchiveYear(0),
            'year zero is not a year'
        );

        $this->assertFalse(
            \Widget\Archive::isValidArchiveYear(-1),
            'a negative year is not a year'
        );

        $this->assertFalse(
            \Widget\Archive::isValidArchiveYear(10000),
            'a five digit year is not a year'
        );
    }

    /**
     * The archive window must not throw on a year the wall clock cannot
     * represent.
     *
     * The upper bound of the window is "the start of the next period", so
     * the last day of 9999 needs 10000-01-01 — a five digit year, which
     * strtotime() cannot parse (it returns false). The conversion has to
     * cope with that rather than let it become a 500.
     *
     * The handler accepts years 101..9999, so its last supported day
     * reaches this boundary through an ordinary request too. The native
     * DateTime::setDate() path must preserve the chosen site time zone.
     */
    public function testTheArchiveWindowSurvivesTheYearTheWallClockCannotHold(): void
    {
        /**
         * 【窗口必须是**一天**, 不是一个正的数字】
         *
         * 上界墙钟 '10000-01-01' 是五位年, DateTime 解析不了 —— 如果
         * 换算把它丢在 UTC 域重建, **站点的偏移就没了**, 窗口会变成 32
         * 小时 (UTC+8) 或者 19 小时 (UTC-5)。只断言"上界大于下界"对
         * 这种偏差完全免疫 —— 它仍然是正的。
         */
        foreach (['Asia/Shanghai', 'America/New_York', 'UTC', 'Australia/Sydney'] as $zone) {
            [$from, $to] = \Widget\Archive::periodWindow(9999, 12, 31, 12, 31, $zone);

            $this->assertIsInt($from);
            $this->assertIsInt($to);
            $this->assertSame(
                86400,
                $to - $from,
                sprintf('the window for %s must be exactly one day', $zone)
            );
        }

        /**
         * 而且上下界必须落在站点的墙上时间: 9999-12-31 的归档窗口是
         * 站点墙钟的 9999-12-31 00:00 到 10000-01-01 00:00。
         */
        [$from, $to] = \Widget\Archive::periodWindow(9999, 12, 31, 12, 31, 'Asia/Shanghai');

        $this->assertSame(
            '9999-12-31 00:00:00',
            gmdate('Y-m-d H:i:s', $from + 28800),
            'the lower bound must be the start of the day in the site zone'
        );

        $this->assertSame(
            '10000-01-01 00:00:00',
            gmdate('Y-m-d H:i:s', $to + 28800),
            'the upper bound must be the start of the next day in the site zone'
        );
    }

    /**
     * The archive window must contain every post of the period.
     *
     * The window used to end at 23:59:59 with an exclusive '<'. A post
     * created at exactly 23:59:59 fell out of this period, and the next
     * period starts at 00:00, so it was in no period at all.
     *
     * This calls the same code the archive uses, not a re-implementation
     * of it -- a re-implementation would not notice when the real one
     * goes wrong.
     */
    public function testTheArchiveWindowContainsEveryPostOfThePeriod()
    {
        $site = 'Asia/Shanghai';

        [$from, $to] = \Widget\Archive::periodWindow(2021, 7, 1, 7, 31, $site);

        /**
         * The last second of the period.
         */
        $lastSecond = \Typecho\Timezone::wallToTimestamp('2021-07-31 23:59:59', $site);

        $this->assertTrue(
            $lastSecond >= $from && $lastSecond < $to,
            'a post at 23:59:59 must be inside the period'
        );

        /**
         * The first instant of the next period.
         */
        $firstOutside = \Typecho\Timezone::wallToTimestamp('2021-08-01 00:00:00', $site);

        $this->assertFalse(
            $firstOutside >= $from && $firstOutside < $to,
            'the first instant of the next period must be outside'
        );
    }

    /**
     * The archive window must not shrink when the server's own clock skips
     * midnight.
     *
     * The bounds used to be built with mktime() in the server's zone. When
     * the server's wall clock skips midnight, mktime() normalises 00:00 to
     * 01:00, and the window is an hour short -- so the posts published in
     * that hour fall out of the day's archive.
     */
    public function testTheArchiveWindowSurvivesTheServersOwnMidnightGap()
    {
        $original = date_default_timezone_get();

        try {
            /**
             * Santiago skips 00:00-00:59 on 2021-09-05.
             */
            date_default_timezone_set('America/Santiago');

            $site = 'Asia/Shanghai';

            [$from, $to] = \Widget\Archive::periodWindow(2021, 9, 5, 9, 5, $site);

            /**
             * A post published in the first hour of that day, in the site's
             * time zone.
             */
            $post = \Typecho\Timezone::wallToTimestamp('2021-09-05 00:30:00', $site);

            $this->assertTrue(
                $post >= $from && $post < $to,
                'a post in the first hour of the day must be inside the day'
            );

            $this->assertSame(
                86400,
                $to - $from,
                'the window must be a whole day long'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The window's end must roll over into the next month and the next
     * year, not into a day that does not exist.
     *
     * A 31-day month and December both used to break: the end was built by
     * putting the number in a string, so 31 became the 32nd and December
     * became the 13th month -- a date that does not parse, and the whole
     * archive query then failed.
     */
    public function testTheArchiveWindowRollsOverCorrectly()
    {
        $site = 'Asia/Shanghai';

        /**
         * January: the 31st is the last day.
         */
        [$from, $to] = \Widget\Archive::periodWindow(2021, 1, 1, 1, 31, $site);
        $this->assertSame(
            \Typecho\Timezone::wallToTimestamp('2021-02-01 00:00:00', $site),
            $to,
            'January must end at the first of February'
        );

        /**
         * December: the next period is the next year.
         */
        [$from, $to] = \Widget\Archive::periodWindow(2021, 12, 1, 12, 31, $site);
        $this->assertSame(
            \Typecho\Timezone::wallToTimestamp('2022-01-01 00:00:00', $site),
            $to,
            'December must end at the first of January'
        );

        /**
         * February 2020, a leap year.
         */
        [$from, $to] = \Widget\Archive::periodWindow(2020, 2, 1, 2, 29, $site);
        $this->assertSame(
            \Typecho\Timezone::wallToTimestamp('2020-03-01 00:00:00', $site),
            $to,
            'February 2020 must end at the first of March'
        );
    }

    /**
     * The archive window is half open: the start belongs to this period,
     * the end belongs to the next one.
     *
     * The right end is the *start of the next period*. If the query used
     * '<=', a post published at exactly that instant would show up in two
     * periods at once.
     */
    public function testTheArchiveWindowIsHalfOpen()
    {
        $predicate = \Widget\Archive::periodPredicate();

        $this->assertStringContainsString(
            '>= ?',
            $predicate,
            'the start of the period must be included'
        );

        $this->assertStringContainsString(
            '< ?',
            $predicate,
            'the start of the next period must be excluded'
        );

        $this->assertStringNotContainsString(
            '<= ?',
            $predicate,
            'the start of the next period must not be included'
        );
    }

    /**
     * A date field with a valid shape but an impossible date must not
     * blow up.
     *
     * '2021-13-45 25:00' passes the shape check -- four digits, two
     * digits, a dash, a colon. Then the wall clock cannot be parsed, and
     * the conversion returns null. If that null reaches a function
     * declared to return int, PHP raises a TypeError and the request
     * ends in a 500.
     *
     * The answer is the same as for a malformed shape: keep the original
     * publish time when editing, use the current time when creating.
     */
    public function testAnImpossibleDateDoesNotRaise()
    {
        $original = date_default_timezone_get();

        /**
         * 【站点时区也是进程级的静态量, 要一起恢复】
         *
         * Date::setTimezone() 改的是静态属性, finally 里只把默认时区
         * 恢复了的话, 站点时区就留在 'Asia/Shanghai' 上, 后面那些用例
         * 继承的是一个被改过的站点时区。
         */
        $originalSiteName = \Typecho\Date::$timezoneName;
        $originalSiteOffset = \Typecho\Date::$timezoneOffset;

        try {
            date_default_timezone_set('UTC');

            \Typecho\Date::setTimezone('Asia/Shanghai');

            $instant = strtotime('2021-07-04 12:30:00 UTC');
            $date = new \Typecho\Date($instant);

            /**
             * The same shape, but impossible values.
             *
             * Measured, not guessed: a minute past the hour and a month
             * past the year fail to parse. An hour of 24 and a day that
             * does not exist are carried over by PHP, the same way
             * mktime() carries them over upstream -- a normalisation, not
             * a malformed input.
             */
            foreach ([
                '2021-13-45 25:00',
                '2021-13-01 00:00',
                '2021-01-01 00:61',
                '2021-01-01 25:61',
                '2021-07-15 24:61',
                '2021-99-99 99:99',
                'not a date',
            ] as $wall) {
                $parsed = \Typecho\Timezone::wallToTimestamp($wall, 'Asia/Shanghai');

                $this->assertNull(
                    $parsed,
                    "an impossible wall clock must not parse: $wall"
                );
            }

            /**
             * A day that does not exist is carried over, like mktime()
             * does. Upstream: mktime(0, 0, 0, 2, 30, 2021) = 2021-03-02.
             */
            $carried = \Typecho\Timezone::wallToTimestamp('2021-02-30 00:00', 'Asia/Shanghai');
            $march = \Typecho\Timezone::wallToTimestamp('2021-03-02 00:00', 'Asia/Shanghai');

            $this->assertSame(
                $march,
                $carried,
                'a day that does not exist must be carried over, the way mktime() does'
            );
        } finally {
            date_default_timezone_set($original);
            \Typecho\Date::$timezoneName = $originalSiteName;
            \Typecho\Date::$timezoneOffset = $originalSiteOffset;
        }
    }

    /**
     * "N days ago" counts calendar days, not elapsed 24 hour blocks.
     *
     * The day a daylight saving switch happens is 23 or 25 hours long.
     * When the real elapsed time is a bit under three days and the wall
     * clock is exactly three days, floor(elapsed / 86400) says "2 days
     * ago" while the calendar says three.
     *
     * The same reasoning already decides "is it the same day" and "is it
     * yesterday" -- those use the wall clock, so the day count has to
     * use it too.
     */
    public function testTheDayCountIsACalendarCount()
    {
        $site = 'America/Los_Angeles';

        /**
         * 2021-03-14, 02:00-02:59 does not exist in Los Angeles: that day
         * is 23 hours long.
         *
         * From 2021-03-11 11:00 to 2021-03-14 11:00 the calendar is three
         * days, but only 71 hours really passed.
         *
         * The wall clock runs from 11:00 to 11:00: the clocks moved forward
         * an hour in between, so the second 11:00 is 10:00 in the offset
         * that was in force on the first day.
         */
        $from = \Typecho\Timezone::wallTimestamp(strtotime('2021-03-11 19:00:00 UTC'), $site);
        $now = \Typecho\Timezone::wallTimestamp(strtotime('2021-03-14 18:00:00 UTC'), $site);

        $elapsed = strtotime('2021-03-14 18:00:00 UTC') - strtotime('2021-03-11 19:00:00 UTC');

        $this->assertSame(
            71 * 3600,
            $elapsed,
            'the real elapsed time is 71 hours, not 72'
        );

        $this->assertSame(
            3 * 86400,
            $now - $from,
            'the wall clock is exactly three days'
        );

        $word = \Typecho\I18n::dateWord($from, $now, $elapsed);

        $this->assertSame(
            _n('一天前', '%d天前', 3),
            $word,
            'three site calendar days must be reported even though only 71 hours elapsed'
        );
    }

    /**
     * The site time zone the database holds must be selectable.
     *
     * The old settings form offered 25 whole-hour offsets, but the
     * database can hold any number of seconds -- a non-whole-hour offset
     * like +00:30 survives the migration unchanged, because no region
     * has exactly that standard offset.
     *
     * If the value is not in the list, the browser submits the first
     * entry, and saving the settings changes the site time zone without
     * anyone asking for it.
     */
    public function testTheSiteTimeZoneIsAlwaysSelectable()
    {
        foreach ([
            'a whole hour' => '28800',
            'half an hour' => '1800',
            'three quarters' => '27480',
            'minus ten and a half' => '-37800',
            'a minute' => '60',
        ] as $label => $value) {
            $list = \Widget\Options\General::getTimezoneList($value);

            $this->assertArrayHasKey(
                $value,
                $list,
                "$label ($value) must be in the list, or saving the settings changes it"
            );
        }
    }

    /**
     * An archive period with an impossible month must not raise.
     *
     * The archive route takes the month straight from the request, so
     * /2021/13/ reaches the period window. The text '2021-13-01' is not
     * a date, and parsing it throws -- the request would end in a 500.
     *
     * Upstream normalised it instead: mktime() carried month 13 over to
     * January of the next year and rendered a page. gmmktime() does the
     * same thing in the UTC domain, so the boundary does not depend on
     * the server's time zone.
     */
    public function testAnImpossibleArchivePeriodIsNormalised()
    {
        $window = \Widget\Archive::periodWindow(2021, 13, 1, 13, 31, 'America/Los_Angeles');

        $this->assertCount(2, $window);
        $this->assertIsInt($window[0]);
        $this->assertIsInt($window[1]);
        $this->assertGreaterThan(0, $window[0]);
        $this->assertGreaterThan($window[0], $window[1]);

        /**
         * Month 13 is January of the next year, the way mktime() reads
         * it.
         */
        $expected = \Typecho\Timezone::wallToTimestamp(
            '2022-01-01 00:00:00',
            'America/Los_Angeles'
        );

        $this->assertSame(
            $expected,
            $window[0],
            'month 13 must be carried over to January of the next year'
        );

        /**
         * A day past the end of the month is carried over too.
         */
        $carried = \Widget\Archive::periodWindow(2021, 1, 32, 1, 32, 'America/Los_Angeles');
        $february = \Widget\Archive::periodWindow(2021, 2, 1, 2, 1, 'America/Los_Angeles');

        $this->assertSame(
            $february[0],
            $carried[0],
            'day 32 must be carried over to February 1'
        );
    }

    /**
     * A site local literal must not be read through the server's clock.
     *
     * IXR\Date::getTimestamp() interprets the literal with mktime(),
     * which builds a server-local instant. When the literal falls in the
     * server's own daylight saving gap, mktime() carries it forward and
     * the site receives an hour-late wall clock. All sixty minutes of
     * that hour are wrong.
     */
    public function testASiteLocalLiteralIsNotReadThroughTheServerClock()
    {
        $original = date_default_timezone_get();

        try {
            /**
             * The server skips 02:00-02:59 on this day.
             */
            date_default_timezone_set('Europe/Berlin');

            $site = 'Asia/Shanghai';

            $wrong = 0;

            for ($minute = 0; $minute < 60; $minute++) {
                $literal = sprintf('20210328T02%02d00', $minute);

                $parsed = new \IXR\Date($literal);

                $expected = \Typecho\Timezone::wallLiteralToTimestamp($literal, $site);

                /**
                 * What the old reading produced: the server reads the
                 * literal through its own skipped hour.
                 */
                $throughServer = $parsed->getTimestamp();

                $serverWall = date('Y-m-d H:i:s', $throughServer);

                if ($serverWall !== sprintf('2021-03-28 02:%02d:00', $minute)) {
                    $wrong++;
                }

                $this->assertNotNull(
                    $expected,
                    "the literal must parse: $literal"
                );

                $this->assertSame(
                    gmdate('Y-m-d H:i:s', $expected),
                    gmdate('Y-m-d H:i:s', \Typecho\Timezone::wallToTimestamp(
                        sprintf('2021-03-28 02:%02d:00', $minute),
                        $site
                    )),
                    "the literal must be read as a site wall clock: $literal"
                );
            }

            $this->assertSame(
                60,
                $wrong,
                'the whole gap hour must be read wrong through the server clock'
            );
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * The migrated time zone must survive another iteration.
     *
     * Widget::next() assigns the row from the stack, so a value that is
     * only changed in the row is replaced by whatever is still in the
     * stack. The migration claims the time zone is updated in memory for
     * the rest of the request; that is only true if the stack holds the
     * new value too.
     */
    public function testTheMigratedTimeZoneSurvivesAnotherIteration()
    {
        $options = $this->options('-28800');

        $this->assertSame(
            '-28800',
            (string) $options->timezoneName,
            'the site starts on the old fixed offset'
        );

        $options->setTimezone('America/Los_Angeles');

        $this->assertSame(
            'America/Los_Angeles',
            (string) $options->timezoneName,
            'the time zone is updated in memory'
        );

        /**
         * Walk the widget the way a plugin would. The stack holds one
         * row, so the second call wraps around to the same row.
         */
        $options->next();

        $this->assertSame(
            'America/Los_Angeles',
            (string) $options->timezoneName,
            'the migrated time zone must not revert to the old value'
        );

        $options->next();

        $this->assertSame(
            'America/Los_Angeles',
            (string) $options->timezoneName,
            'the migrated time zone must not revert when the stack wraps'
        );
    }

    /**
     * The rendered result must not depend on the server's own time zone.
     *
     * This is the property, not an example. The same UTC instant, rendered
     * on five servers with five different clocks, has to give the same
     * answer -- otherwise the site shows a different time depending on
     * which machine runs it.
     *
     * Asserting one concrete example would not catch it: on a switch day
     * the wrong answer often differs by an hour, and an assertion on the
     * date alone still passes. Comparing the servers against each other
     * catches every case.
     */
    public function testTheRenderedResultIsTheSameOnEveryServer()
    {
        $original = date_default_timezone_get();

        /**
         * 【站点时区也是进程级的静态, 要一起恢复】
         *
         * Date::setTimezone() 改的是静态属性, 它的 finally 只把
         * date_default_timezone_set() 恢复了 —— 于是站点时区被留在最后
         * 一个迭代的值上 ('Pacific/Auckland'), 后面那些用例继承的就是
         * 一个被改过的站点时区。
         */
        $originalSiteName = \Typecho\Date::$timezoneName;
        $originalSiteOffset = \Typecho\Date::$timezoneOffset;

        $servers = ['UTC', 'Europe/Berlin', 'America/New_York', 'Australia/Sydney', 'Pacific/Apia'];
        $sites = ['UTC', 'America/Los_Angeles', 'Europe/Berlin', 'Asia/Shanghai', 'Pacific/Auckland'];
        $bad = [];

        try {
            foreach ($sites as $site) {
                \Typecho\Date::setTimezone($site);

                /**
                 * A whole year every six hours, plus the exact switch days.
                 */
                $moments = [];
                for ($t = strtotime('2021-01-01 00:00:00 UTC');
                     $t < strtotime('2022-01-01 00:00:00 UTC');
                     $t += 6 * 3600) {
                    $moments[] = $t;
                }
                foreach (['2021-03-14 07:00:00', '2021-03-28 01:00:00',
                          '2021-10-31 01:00:00', '2021-11-07 07:00:00',
                          '2011-12-29 12:00:00'] as $text) {
                    $moments[] = strtotime($text . ' UTC');
                }

                $byServer = [];

                foreach ($servers as $server) {
                    date_default_timezone_set($server);

                    foreach ($moments as $moment) {
                        $date = new \Typecho\Date($moment);

                        $key = gmdate('Y-m-d H:i:s', $moment);
                        $rendered = $date->format('Y-m-d H:i:s')
                            . '|' . $date->year . $date->month . $date->day;

                        if (isset($byServer[$key]) && $byServer[$key] !== $rendered) {
                            $bad[] = sprintf(
                                'site %s, instant %s: server UTC gave "%s", server %s gave "%s"',
                                $site,
                                $key,
                                $byServer[$key],
                                $server,
                                $rendered
                            );
                        } else {
                            $byServer[$key] = $rendered;
                        }
                    }
                }
            }
        } finally {
            date_default_timezone_set($original);
            \Typecho\Date::$timezoneName = $originalSiteName;
            \Typecho\Date::$timezoneOffset = $originalSiteOffset;
        }

        $this->assertSame(
            [],
            $bad,
            "the rendered result must be the same on every server:\n" . implode("\n", array_slice($bad, 0, 10))
        );
    }
    /**
     * A wall clock that the server zone skips is read the way the platform
     * reads it when producing the legacy server-local carrier.
     *
     * When the clocks move forward, an hour of wall clock does not exist
     * in that zone: 02:00 to 02:59 on 2021-03-14 in Los Angeles, 02:00 to
     * 02:59 on 2021-03-28 in Berlin. A wall clock in that gap has no exact
     * instant, and the platform normalises it forward by an hour.
     *
     * This change does not reject such a clock — rejecting it would be a
     * change of behaviour against the platform — so the legacy carrier has
     * to match that normalisation. Real UTC instants and site formatting
     * must remain exact without going through the server-local carrier.
     *
     * The site zone here is Asia/Shanghai, which has no daylight saving:
     * the wall clock exists and is read exactly. The gap belongs to the
     * server zone, and the server side is what the platform normalises.
     */
    public function testAWallClockInTheGapOfTheServerZoneIsReadAsThePlatformReadsIt(): void
    {
        $original = date_default_timezone_get();

        try {
            date_default_timezone_set('Europe/Berlin');

            /**
             * 02:30 on the day the clocks move forward does not exist in
             * Berlin.
             */
            $wall = '2021-03-28 02:30:00';

            $this->assertFalse(
                self::wallClockExists($wall, 'Europe/Berlin'),
                'this wall clock is skipped in Berlin'
            );

            /**
             * The site zone has no daylight saving, so the wall clock
             * exists there and is read exactly.
             */
            $instant = Timezone::wallToTimestamp($wall, 'Asia/Shanghai');

            $this->assertIsInt($instant);

            /**
             * gmdate() gives the UTC wall clock, so the site wall clock is
             * the instant plus the site offset.
             */
            $this->assertSame(
                '2021-03-28 02:30:00',
                gmdate('Y-m-d H:i:s', $instant + 28800),
                'the site zone reads the wall clock as it was typed'
            );

            // 原生服务器日历是兼容载体的 oracle，缺口明确向前到 03:30。
            $nativeServer = new DateTimeImmutable($wall, new DateTimeZone('Europe/Berlin'));
            $this->assertSame('2021-03-28 03:30:00', $nativeServer->format('Y-m-d H:i:s'));

            // 真实输出和发布解析直接使用站点时区，不能继承载体的归一化误差。
            Date::setTimezone('Asia/Shanghai');
            $date = new Date($instant);
            $nativeSite = (new DateTimeImmutable('@' . $instant))
                ->setTimezone(new DateTimeZone('Asia/Shanghai'));
            $this->assertSame($instant, $date->utcTimeStamp);
            $this->assertSame($nativeSite->format('c'), $date->format('c'));
            $this->assertSame($wall, $date->format('Y-m-d H:i:s'));
            $this->assertSame($instant, (int) $date->format('U'));
            $this->assertSame($instant,
                Timezone::wallToTimestamp($date->format('Y-m-d H:i:s'), 'Asia/Shanghai'));

            /**
             * The round trip through the server side does **not** keep it
             * on this day: the server time zone is Berlin and its own
             * clock skips an hour, so the intermediate wall clock the
             * server side works with is one that does not exist, and the
             * platform normalises it forward by an hour.
             *
             * That is the platform's reading, and this change does not
             * replace it. The instant itself is right — the site zone read
             * the wall clock exactly — and it is the detour through the
             * server's own gap that costs the hour.
             */
            // 旧 serverOffset 参数仅为调用兼容保留，计算必须忽略它。
            // 显式覆盖冬夏两个值，避免测试依赖执行当天的季节。
            $roundTrips = [];
            foreach ([3600, 7200] as $serverOffset) {
                $wallEpoch = Timezone::toLocalTimestamp(
                    $instant,
                    'Asia/Shanghai',
                    $serverOffset
                );

                $this->assertSame($nativeServer->getTimestamp(), $wallEpoch,
                    'the legacy carrier must use the native forward gap normalisation');
                $this->assertSame('2021-03-28 03:30:00', date('Y-m-d H:i:s', $wallEpoch));

                $back = Timezone::fromLocalTimestamp(
                    $wallEpoch,
                    'Asia/Shanghai',
                    $serverOffset
                );

                // 载体向前归一化一小时；不能用 abs() 放过错误的向后归一化。
                $this->assertSame(
                    3600,
                    $back - $instant,
                    'the legacy round trip moves forward by exactly the skipped hour'
                );
                $roundTrips[] = [$wallEpoch, $back];
            }
            $this->assertSame($roundTrips[0], $roundTrips[1],
                'the legacy server offset argument must not affect either conversion');
        } finally {
            date_default_timezone_set($original);
        }
    }

    /**
     * 这个墙钟在给定时区里**存在**吗
     *
     * 夏令时向前拨的那一小时里, 有一个区间的墙钟根本不存在。
     *
     * @param string $wall 墙钟时间 (Y-m-d H:i:s)
     * @param string $zone 时区
     * @return boolean
     */
    private static function wallClockExists(string $wall, string $zone): bool
    {
        try {
            $date = new \DateTime($wall, new \DateTimeZone($zone));
        } catch (\Throwable $e) {
            return false;
        }

        return $date->format('Y-m-d H:i:s') === $wall;
    }

    /**
     * 取某一行往后的一小段窗口
     *
     * 【为什么不按大括号切】按大括号切要靠偏移量回到原文里定位, 而
     * 偏移量会因为中间剪掉过注释而对不上 —— 切出来的是错的块。按行
     * 走就没有这个问题。
     *
     * @param string $source 源码
     * @param string $anchor 起始那一行
     * @param int $lines 窗口行数
     * @return array
     */
    private static function windowAfter(string $source, string $anchor, int $lines): array
    {
        $all = explode("\n", $source);
        $at = null;

        foreach ($all as $i => $line) {
            if ($line === $anchor) {
                $at = $i;
                break;
            }
        }

        if ($at === null) {
            return [];
        }

        return array_slice($all, $at, $lines);
    }

    /**
     * 取所有包含某段文本的行
     *
     * @param string $source 源码
     * @param string $marker 要找的文本
     * @return array
     */
    private static function linesContaining(string $source, string $marker): array
    {
        $out = [];

        foreach (explode("\n", $source) as $line) {
            if (strpos($line, $marker) !== false) {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * 取包含某段文本的那一行
     *
     * @param string $source 源码
     * @param string $marker 要找的文本
     * @return string 空字符串表示没找到
     */
    private static function lineAfter(string $source, string $marker): string
    {
        $at = self::findInCode($source, $marker);

        if ($at === false) {
            return '';
        }

        $start = strrpos(substr($source, 0, $at), "\n");
        $start = $start === false ? 0 : $start + 1;
        $end = strpos($source, "\n", $at);

        return $end === false ? substr($source, $start) : substr($source, $start, $end - $start);
    }

    /**
     * 在**代码**里找一段文本, 跳过注释里的那些
     *
     * @param string $source 源码
     * @param string $marker 要找的文本
     * @return int|false 原文里的偏移, 找不到返回 false
     */
    private static function findInCode(string $source, string $marker)
    {
        $offset = 0;
        $inBlock = false;

        foreach (explode("\n", $source) as $line) {
            $trimmed = ltrim($line);

            if ($inBlock) {
                /**
                 * 块注释的结束行。整行都算注释。
                 */
                if (strpos($trimmed, '*/') !== false) {
                    $inBlock = false;
                }

                $offset += strlen($line) + 1;

                continue;
            }

            /**
             * 单行的块注释。
             */
            if (strpos($trimmed, '/*') === 0) {
                if (strpos($trimmed, '*/', 2) === false) {
                    $inBlock = true;
                }

                $offset += strlen($line) + 1;

                continue;
            }

            /**
             * 行注释 (整行都是注释的)。
             */
            if (strpos($trimmed, '//') === 0) {
                $offset += strlen($line) + 1;

                continue;
            }

            /**
             * 行尾注释: 代码后面跟着 // ...
             *
             * 【为什么要单独处理】这一行**有代码**, 只是后面跟着注释。
             * 如果不把它里的注释去掉, 那么写在行尾注释里的标记会被当成
             * 代码里的标记 —— 于是 blockAfter() 会从那条注释后面开始切,
             * 切出来的块是错的。
             *
             * 所以这里把行尾的注释剪掉, 再在这**剩下的**代码里找标记。
             * 剪掉之后这一行如果空了, 就整行跳过。
             */
            $codePart = preg_replace('#//[^\n]*$#', '', $line);
            $codePart = $codePart === null ? $line : $codePart;

            if (trim($codePart) === '') {
                $offset += strlen($line) + 1;

                continue;
            }

            /**
             * 块注释的中间行 (以 * 开头)。
             */
            if (strpos($trimmed, '*') === 0) {
                $offset += strlen($line) + 1;

                continue;
            }

            $at = strpos($codePart, $marker);

            if ($at !== false) {
                return $offset + $at;
            }

            $offset += strlen($line) + 1;
        }

        return false;
    }

    /**
     * 取某个标记之后的那一个 {...} 块
     *
     * 【为什么需要它】带 /s 的惰性正则会跨过整个文件, 所以"这一行里
     * 有没有某个东西"这种断言根本钉不住 —— 文件别处的同名东西就能满足
     * 它。按大括号切块, 断言的范围才是"这一段代码"。
     *
     * @param string $source 源码
     * @param string $marker 标记
     * @return string
     */
    private static function blockAfter(string $source, string $marker): string
    {
        /**
         * 【要找**代码里**的那一处, 不是注释里的】
         *
         * 生产文件里我写的注释会提到方法名和变量名, 所以标记第一次出现
         * 的位置很可能在注释里 —— 那切出来的"块"是错的。
         *
         * 【为什么不能"先剥注释再定位"】剥掉注释之后文本变短了, 剥注释
         * 文本里的偏移和原文的偏移对不上, 拿它回原文切块会切歪。正确
         * 的做法是**在原文里逐行找, 跳过注释行** —— 这样拿到的是原文
         * 里的真实偏移。
         */
        $at = self::findInCode($source, $marker);

        if ($at === false) {
            return '';
        }

        $open = strpos($source, '{', $at);

        if ($open === false) {
            return '';
        }

        $depth = 0;
        $length = strlen($source);

        for ($i = $open; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $open, $i - $open + 1);
                }
            }
        }

        return substr($source, $open);
    }

}


/**
 * An Options widget that takes its row from the test instead of the
 * database. Everything else, including the time zone converters, is the
 * production code.
 */
final class TestOptions extends Options
{
    protected function initComponents(int &$components)
    {
        $components = self::INIT_NONE;
    }

    protected function initParameter(Config $parameter)
    {
        /** no database, the row is pushed by the test */
    }
}

/**
 * Exposes the protected method of the publish path so the test can call
 * the production code instead of reading its source text.
 *
 * The production trait is used as it is, so the test runs the very code
 * the site runs.
 */
final class TestCreatedFromDate extends \Widget\Base
{
    use \Widget\Contents\EditTrait;

    public function setTestOptions(Options $options): void
    {
        /** The production property, not a shadow copy */
        $this->options = $options;
    }

    protected function initComponents(int &$components)
    {
        /** INIT_NONE: the test supplies the options widget itself */
        $components = self::INIT_NONE;
    }

    protected function initParameter(Config $parameter)
    {
    }

    public function callCreatedFromDate(): int
    {
        return $this->getCreatedFromDate();
    }

    /** The trait requires it; the test never renders a form */
    protected function getThemeFieldsHook(): string
    {
        return '';
    }

}
