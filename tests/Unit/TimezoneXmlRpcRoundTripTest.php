<?php

use PHPUnit\Framework\TestCase;

/**
 * 回归测试: XML-RPC 的 dateCreated 往返。
 *
 * 【为什么这条要单独钉住】
 *
 * `wp.getPost` / `wp.getPosts` 返回的 `dateCreated` 是
 * `new Date($post->created + $this->options->timezoneOffsetAt($post->created))`
 * —— 站点墙钟那个瞬间的数值, 交给 `IXR\Date` 序列化成一个字面。
 *
 * `IXR\Date::parseTimestamp()` 用 gmdate() 把数值按 UTC 读, 后缀留空,
 * 所以字面是紧凑格式且**没有时区后缀** (形如 20210704T05:00:00)。
 *
 * 客户端 (WordPress 桌面客户端、Jetpack、各类同步插件) 会把这个字面
 * 原样回传给 `wp.newPost` / `wp.editPost`。写路径调用
 * `Timezone::wallLiteralToTimestamp()`, 它**必须丢掉后缀**:
 *
 * - 认后缀 = 把它当成绝对 UTC 瞬间, 发布时间会平移一个站点偏移;
 * - 拒带后缀的形状 = 返回 null, created 被 unset, 文章**静默变成
 *   "现在"** —— 而客户端收到的是成功响应。
 *
 * 两个都是主发布路径上的严重故障, 而且都是静默的。这个测试把
 * "取回来再提交, 时间必须不变"钉住。
 *
 * 【为什么用真的 IXR\Date 而不是手拼字面量】
 *
 * 手拼字面量绕过了 IXR\Date 的序列化, 于是"getPost 的表达式会不会
 * 产生二次偏移"这件事根本没被验证。这里用真的 IXR\Date, 走完
 * getPost -> 序列化 -> 客户端回传 -> 解析 整个来回。
 */
final class TimezoneXmlRpcRoundTripTest extends TestCase
{
    /**
     * 站点时区取一个带夏令时的, 让偏移随日期变
     */
    private const ZONE = 'America/Los_Angeles';

    /**
     * 取回来再提交, 发布时间必须一模一样
     *
     * @dataProvider provideInstants
     */
    public function testAGetThenEditRoundTripKeepsThePublishTime(int $instant): void
    {
        /**
         * wp.getPost 的表达式, 一字不差。
         */
        $offset = \Typecho\Timezone::offsetAt(self::ZONE, $instant);
        $literal = (new \IXR\Date($instant + $offset))->getIso();

        /**
         * 客户端回传, 写路径解析回来。
         */
        $parsed = \Typecho\Timezone::wallLiteralToTimestamp($literal, self::ZONE);

        $this->assertNotNull(
            $parsed,
            sprintf('the literal %s must parse, otherwise the post silently becomes now', $literal)
        );

        $this->assertSame(
            $instant,
            $parsed,
            sprintf('the round trip through %s must not move the publish time', $literal)
        );
    }

    /**
     * @return array<string, array{int}>
     */
    public static function provideInstants(): array
    {
        return [
            'summer, in daylight saving' => [strtotime('2021-07-04 12:00:00 UTC')],
            'winter, in standard time' => [strtotime('2021-01-04 12:00:00 UTC')],
            'the day the clocks change' => [strtotime('2021-03-14 10:00:00 UTC')],
        ];
    }

    /**
     * getPost 的字面必须真的是"站点墙钟", 而且往返不产生二次偏移
     *
     * 二次偏移的形态是: 字面里的时刻 = created + offset, 再按站点时区
     * 解释一次就成了 created + 2*offset。这里直接把字面里的时刻取出来,
     * 与站点墙钟对一下。
     */
    public function testTheLiteralCarriesTheSiteWallClockAndNoMore(): void
    {
        $instant = strtotime('2021-07-04 12:00:00 UTC');
        $offset = \Typecho\Timezone::offsetAt(self::ZONE, $instant);

        $literal = (new \IXR\Date($instant + $offset))->getIso();

        /**
         * 字面里的墙钟 = created + offset, 这正是"站点墙钟"该有的样子。
         */
        $this->assertSame(
            gmdate('Y-m-d H:i:s', $instant + $offset),
            gmdate('Y-m-d H:i:s', strtotime($literal . ' UTC')),
            'the literal must carry the site wall clock'
        );

        /**
         * 而"站点墙钟"再按站点时区解释一次, 必须回到 created ——
         * 否则就是二次偏移。
         */
        $this->assertSame(
            $instant,
            \Typecho\Timezone::wallLiteralToTimestamp($literal, self::ZONE),
            'reading the site wall clock in the site zone must give the instant back'
        );
    }

    /**
     * 后缀被丢掉, 而不是被当成绝对瞬间
     *
     * 客户端可能把字面按它自己的时区序列化 (WordPress 的
     * `date_created_gmt` 就是 `new Date($post->created)`, 后缀是 UTC)。
     * 写路径必须把字面一律当作**站点墙钟**来读。
     */
    public function testTheSuffixIsDroppedRatherThanHonoured(): void
    {
        $instant = strtotime('2021-07-04 12:00:00 UTC');
        $offset = \Typecho\Timezone::offsetAt(self::ZONE, $instant);
        $wall = gmdate('Y-m-d H:i:s', $instant + $offset);

        $withZ = \Typecho\Timezone::wallLiteralToTimestamp($wall . 'Z', self::ZONE);
        $withOffset = \Typecho\Timezone::wallLiteralToTimestamp($wall . '+05:00', self::ZONE);
        $without = \Typecho\Timezone::wallLiteralToTimestamp($wall, self::ZONE);

        $this->assertNotNull($withZ);
        $this->assertNotNull($withOffset);
        $this->assertNotNull($without);

        $this->assertSame(
            $without,
            $withZ,
            'a Z suffix must not change the result'
        );

        $this->assertSame(
            $without,
            $withOffset,
            'a numeric suffix must not change the result'
        );
    }

    /**
     * 紧凑格式 (IXR\Date::getIso() 的真实格式) 必须能解析
     *
     * 客户端回传的就是这个形状, 不是带分隔符的那个。而从时间戳构造的
     * IXR\Date 留下的是**空后缀**, 所以字面里没有 +00:00。
     */
    public function testTheCompactLiteralShapeIsAccepted(): void
    {
        $instant = strtotime('2021-07-04 12:00:00 UTC');
        $offset = \Typecho\Timezone::offsetAt(self::ZONE, $instant);
        $literal = (new \IXR\Date($instant + $offset))->getIso();

        $this->assertMatchesRegularExpression(
            '/^\d{8}T\d{2}:\d{2}:\d{2}$/',
            $literal,
            'the literal must have the shape IXR\\Date::getIso() produces for a timestamp'
        );

        $this->assertSame(
            $instant,
            \Typecho\Timezone::wallLiteralToTimestamp($literal, self::ZONE),
            'the compact shape must round trip'
        );
    }

    /**
     * 站点时区是个**数字**旧偏移时, 往返也一样成立
     */
    public function testTheRoundTripAlsoHoldsForALegacyOffsetSite(): void
    {
        $instant = strtotime('2021-07-04 12:00:00 UTC');
        $legacy = '28800';

        $literal = (new \IXR\Date($instant + 28800))->getIso();

        $this->assertSame(
            $instant,
            \Typecho\Timezone::wallLiteralToTimestamp($literal, $legacy),
            'a legacy offset site must round trip too'
        );
    }
}
