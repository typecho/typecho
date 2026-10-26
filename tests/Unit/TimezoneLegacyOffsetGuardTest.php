<?php

use PHPUnit\Framework\TestCase;

/**
 * 回归测试: 旧固定偏移的**读路径**也必须查范围。
 *
 * 【为什么写这个】
 *
 * 写路径 (设置页 / wp.setOptions) 查范围只挡住**新的**污染。数据库里
 * 可能已经躺着一行越界的偏移 —— 修复前的 XML-RPC 就能写进去。读路径
 * 如果无条件 (int) 强转, 这行数据会被整站消费:
 *
 * - Timezone::offsetAt() 直接返回 999999999 (偏移 11574 天);
 * - Date::siteZone() 拿它拼出 '+277777:46' 交给 DateTimeZone, 构造
 *   函数抛 Exception, 而这里没有 try/catch —— 异常一路冒到
 *   Date::format(), 于是每一篇文章/评论/归档的渲染都 500。修复前这个
 *   值最多让日期算错, 不会让整个站点挂掉;
 * - 两个迁移的"成功判据" ($migrated === (string) (int) $timezone) 对
 *   越界值恒为真, 于是中毒的行永远不会被写库修正。
 *
 * 所以读和写用同一个判断, 并且要能真的把中毒的行修掉。
 */
final class TimezoneLegacyOffsetGuardTest extends TestCase
{
    /**
     * 越界的旧偏移在所有读路径上都按"解释不了"处理
     */
    public function testAnOutOfRangeLegacyOffsetIsRejectedOnEveryReadPath(): void
    {
        $absurd = '999999999';
        $instant = strtotime('2021-07-04 12:00:00 UTC');

        /**
         * 偏移本身: 越界的值回退到 0 (不调整)。
         */
        $this->assertSame(
            0,
            \Typecho\Timezone::offsetAt($absurd, $instant),
            'an out-of-range legacy offset must not be used as an offset'
        );

        /**
         * 墙钟: 越界的值按"没有偏移"处理, 而不是加 11574 天。
         */
        $this->assertSame(
            $instant,
            \Typecho\Timezone::wallTimestamp($instant, $absurd),
            'an out-of-range legacy offset must not shift the wall clock'
        );

        /**
         * 墙钟文本解析: 越界的值交回 null, 让调用方按自己的策略降级。
         *
         * 【为什么不在这里降级】null 在这个方法里表示"这个墙钟时间在
         * 给定时区下不成立", 而各调用方的降级方式不一样: 发布路径要
         * **保持原有的发布时间** (形状不合法就不动它), 归档窗口要按
         * "不调整"取边界。同一个 null, 不同的调用方按自己的策略处理,
         * 比在这里统一按 UTC 读更准确。
         */
        $this->assertNull(
            \Typecho\Timezone::wallToTimestamp('2021-07-04 12:00:00', $absurd),
            'an out-of-range legacy offset must be reported as unusable'
        );

        /**
         * 形状真的不合法时, 同样交回 null。
         */
        $this->assertNull(
            \Typecho\Timezone::wallToTimestamp('not a wall clock', 'UTC'),
            'an unparseable wall clock must still be reported as unparseable'
        );

        /**
         * 合法范围的两端仍然可用。
         */
        $this->assertSame(
            50400,
            \Typecho\Timezone::offsetAt('50400', $instant),
            'UTC+14 must still be honoured'
        );
        $this->assertSame(
            -50400,
            \Typecho\Timezone::offsetAt('-50400', $instant),
            'UTC-14 must still be honoured'
        );
        $this->assertSame(
            19800,
            \Typecho\Timezone::offsetAt('19800', $instant),
            'a non-whole-hour offset such as +05:30 must still be honoured'
        );
    }

    /**
     * Date::siteZone() 不能因为越界的旧偏移抛异常
     *
     * 这是最严重的一条: 未捕获的 Exception 冒到 format(), 每一个渲染
     * 页面都 500 —— 比修复前"日期算错"严重得多。
     */
    public function testTheSiteZoneDoesNotThrowForAnOutOfRangeLegacyOffset(): void
    {
        $original = \Typecho\Date::$timezoneOffset;
        $originalName = \Typecho\Date::$timezoneName;

        try {
            /**
             * 模拟数据库里躺着一行越界的偏移。
             *
             * 直接写这个**公开静态**量, 而不是走 setTimezoneOffset() ——
             * 后者会把越界的值归零, 于是走不到 siteZone() 的那条分支,
             * 这个测试就变成永远通过的空测试。
             */
            \Typecho\Date::$timezoneName = '';
            \Typecho\Date::$timezoneOffset = 999999999;

            $date = new \Typecho\Date(strtotime('2021-07-04 12:00:00 UTC'));

            /**
             * 不抛异常就是通过 —— 但还要确认结果是有意义的 (不是 1970)。
             */
            $formatted = $date->format('Y-m-d H:i');
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/',
                $formatted,
                'the render must still produce a real date'
            );
            $this->assertStringStartsWith(
                '2021-',
                $formatted,
                'the render must not fall back to 1970'
            );
        } finally {
            \Typecho\Date::$timezoneOffset = $original;
            \Typecho\Date::$timezoneName = $originalName;
        }
    }

    /**
     * 迁移必须能修掉已经中毒的行
     *
     * migrateOffset() 之前对越界值返回原值, 于是迁移的"成功判据"
     * ($migrated === (string) (int) $timezone) 恒为真 —— 永远认为
     * "已经迁移过", 中毒的行永久留在数据库里。
     */
    public function testTheMigrationCanRepairAPoisonedRow(): void
    {
        $migrated = \Typecho\Timezone::migrateOffset(999999999);

        $this->assertNotSame(
            '999999999',
            $migrated,
            'the migration must not keep the poisoned value'
        );
        $this->assertTrue(
            \Typecho\Timezone::isName($migrated),
            'the migration must produce a usable time zone name, got: ' . $migrated
        );

        /**
         * 这条判据就是两个迁移点的"已经迁移过"判断。
         */
        $this->assertNotSame(
            (string) (int) '999999999',
            $migrated,
            'the poisoned row must not look like an already-migrated one'
        );
    }

    /**
     * Date::word() 也要用同一个守卫
     *
     * word() 自己带了一份 isValidLegacyOffset() 的拷贝。构造函数和
     * siteZone() 都查了, 而这一处没查的话, 同一个坏值会让相对时间
     * 文案 ("3 小时前") 算错, 而渲染的日期是对的 —— 两处自相矛盾。
     *
     * 【为什么用"3 小时前"这个瞬间】相对时间文案的形式取决于间隔
     * 多长: 间隔在同一天内才说"几小时前", 跨天才说日期。拿一个 2021
     * 年的瞬间来测, 两种 $now 都会渲染成日期形式, 断言就区分不出任何
     * 东西 —— 一个恒真的测试比没有测试更糟。
     *
     * 所以这里用"3 小时前", 这时 $now 的偏移直接决定输出是"3小时前"
     * 还是一个荒谬的日期。
     */
    public function testTheRelativeTimeWordingAlsoRefusesAPoisonedOffset(): void
    {
        $originalOffset = \Typecho\Date::$timezoneOffset;
        $originalName = \Typecho\Date::$timezoneName;

        try {
            \Typecho\Date::$timezoneName = '';
            \Typecho\Date::$timezoneOffset = 999999999;

            /**
             * 【为什么不能断言"小时前"】
             *
             * I18n::dateWord() 的"小时"分支要求两个瞬间是**同一天**
             * (它比较的是日历日), 跨天就走日期形式。而 word() 里的"现在"
             * 是真实的当前时刻, 测试没法注入 —— 所以凌晨一点跑这个测试,
             * "三小时前"落在昨天, 断言会在每天的这个时段里莫名其妙地失败。
             *
             * 【所以改成比较两次的结果】
             *
             * 两次用的是同一个被测瞬间, 唯一区别是站点偏移有没有被污染。
             * 正常偏移给出的是"真实的相对时间", 被污染的偏移会把"现在"
             * 平移 11574 天, 结果必然不同。所以"两者相等"就是"偏移没有
             * 影响相对时间"这个性质本身, 而且不依赖测试跑在一天里的
             * 哪个时刻。
             */
            $instant = time() - 3 * 3600;

            $date = new \Typecho\Date($instant);
            $word = $date->word();

            $this->assertIsString($word);
            $this->assertNotSame('', $word);

            /**
             * 【基线必须是"守卫之后等效的那个值", 不是环境里的原值】
             *
             * 被污染的 999999999 过不了范围校验, word() 把它当成"不调整",
             * 也就是偏移 0。所以基线也要是 0 —— 这样两次调用用的是**同一个
             * 墙钟**, 差别只剩"有没有被污染"。
             *
             * 拿环境里的原值当基线是不对的: 原值非 0 时, 两次调用算的是
             * 不同的墙钟, 相对时间的差异就分不清是污染造成的还是墙钟本身
             * 不同造成的。
             */
            \Typecho\Date::$timezoneName = '';
            \Typecho\Date::$timezoneOffset = 0;

            $unpoisoned = (new \Typecho\Date($instant))->word();

            $this->assertSame(
                $unpoisoned,
                $word,
                'a poisoned site offset must not move the relative time'
            );
        } finally {
            \Typecho\Date::$timezoneOffset = $originalOffset;
            \Typecho\Date::$timezoneName = $originalName;
        }
    }

    /** 秒级旧偏移必须保留；RFC分钟区标识无法表达时改用真实UTC序列化。 */
    public function testASecondPrecisionLegacyOffsetIsPreservedConsistently(): void
    {
        $originalOffset = \Typecho\Date::$timezoneOffset;
        $originalName = \Typecho\Date::$timezoneName;
        try {
            foreach ([12345, -12345, 45, -45] as $offset) {
                \Typecho\Date::$timezoneName = '';
                \Typecho\Date::$timezoneOffset = $offset;
                $instant = strtotime('2021-07-04 12:00:00 UTC');
                $date = new \Typecho\Date($instant);
                $this->assertSame($offset, \Typecho\Timezone::normalizeLegacyOffset($offset));
                $this->assertSame($offset, \Typecho\Timezone::offsetAt((string) $offset, $instant));
                $this->assertSame($instant + $offset, \Typecho\Timezone::wallTimestamp($instant, (string) $offset));
                $wall = gmdate('Y-m-d H:i:s', $instant + $offset);
                $this->assertSame($wall, $date->format('Y-m-d H:i:s'));
                $this->assertSame($instant, \Typecho\Timezone::wallToTimestamp($wall, (string) $offset));
                $this->assertSame((string) $instant, $date->format('U'));
                $this->assertSame((string) $offset, $date->format('Z'));
                foreach (['c', 'r', 'Y-m-d H:i:s P'] as $format) {
                    $this->assertSame(gmdate($format, $instant), $date->format($format));
                }
            }
        } finally {
            \Typecho\Date::$timezoneOffset = $originalOffset;
            \Typecho\Date::$timezoneName = $originalName;
        }
    }

    /**
     * 归档窗口不能因为毒化的时区行而抛出未捕获的异常
     *
     * Archive::periodWindow() 把墙钟文本交给 Timezone::wallToTimestamp()
     * 换算, 后者对不可用的时区降级成"不调整"。如果它交回 null, 归档
     * 的调用方会把它当成"日期不合法"抛出去 —— 整个归档页 500。
     *
     * 而同一个坏值在 Date::siteZone()、Timezone::offsetAt() 上都是
     * 降级而不是抛异常。所以这里钉住: 归档窗口也要降级。
     */
    public function testTheArchiveWindowSurvivesAPoisonedTimezone(): void
    {
        [$from, $to] = \Widget\Archive::periodWindow(
            2021,
            7,
            1,
            7,
            31,
            '999999999'
        );

        $this->assertSame(
            strtotime('2021-07-01 00:00:00 UTC'),
            $from,
            'the window must start at the wall clock read with no adjustment'
        );

        $this->assertSame(
            strtotime('2021-08-01 00:00:00 UTC'),
            $to,
            'the window must end at the start of the next period'
        );
    }

    /**
     * Date::setTimezone() 不能把公开的偏移清成 0
     *
     * Date::$timezoneOffset 是**公开静态**属性, 语义上表示"站点当前的
     * 期望偏移"。清成 0 的话, 任何本 PR 之外读它的模块/插件在所有已
     * 迁移的站点上都会拿到 0 —— 也就是把站点当成 UTC, 整整偏一个时区。
     *
     * 所以它必须保存站点此刻的真实偏移。
     */
    public function testSetTimezoneKeepsTheSiteOffsetInThePublicProperty(): void
    {
        $originalOffset = \Typecho\Date::$timezoneOffset;
        $originalName = \Typecho\Date::$timezoneName;

        try {
            \Typecho\Date::setTimezone('America/Los_Angeles');

            $this->assertNotSame(
                0,
                \Typecho\Date::$timezoneOffset,
                'the public offset must not be cleared to 0 on a migrated site'
            );

            $this->assertSame(
                \Typecho\Timezone::offsetAt('America/Los_Angeles', time()),
                \Typecho\Date::$timezoneOffset,
                'the public offset must be the site offset at the current instant'
            );
        } finally {
            \Typecho\Date::$timezoneOffset = $originalOffset;
            \Typecho\Date::$timezoneName = $originalName;
        }
    }

    /**
     * 合法但未映射的旧偏移仍然保留原值
     *
     * 12345 秒这样的偏移找不到精确等价的地区时区, 保留原值是**有意的**
     * —— 截断成整点会让站点时间整个偏移。这条不能被上面的越界处理
     * 误伤。
     *
     * (+05:30 的 19800 和 +05:45 的 20700 反而都有精确等价的地区时区,
     * 所以它们会被正常迁移, 不属于这一类。)
     */
    public function testAValidButUnmappedLegacyOffsetKeepsItsValue(): void
    {
        $migrated = \Typecho\Timezone::migrateOffset(12345);

        $this->assertSame(
            '12345',
            $migrated,
            'a valid unmapped offset must keep its value so the site keeps its time'
        );
    }
}
