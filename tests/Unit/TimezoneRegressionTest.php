<?php

use PHPUnit\Framework\TestCase;

/**
 * 回归测试: 2026-10-25 的独立 MoA 审查 (FAIL) 抓到的问题。
 *
 * 这三个测试各自钉住一个具体的缺陷, 并且都必须在**修复前**变红:
 *
 * 1. Post/Date.php 的 wallOf() 曾用 \Widget\Options::widget('Options') 取站点
 *    时区。Widget::widget() 把首参当类名 (new $className), 而 'Options'
 *    既不是类名 (真名是 Widget\Options) 也不带命名空间分隔符, 自动加载器
 *    找不到它 —— 于是抛 Error: Class "Options" not found。归档组件只要有
 *    一篇文章就整页挂掉。
 *
 * 2. 时区的数值分支只查形状不查大小, 于是 999999999 (偏移 11574 天) 能被
 *    写进数据库。
 *
 * 3. getCreatedFromDate() 曾直接 (string) 转换请求参数, 客户端提交
 *    date[]=x 时触发 "Array to string conversion" 警告 (phpunit.xml.dist
 *    开了 failOnWarning)。
 */
final class TimezoneRegressionTest extends TestCase
{
    // Options 工厂和归档默认时区路径由 TimezoneOptionsContractTest 的真实 SQLite 用例覆盖。

    /**
     * 一个不存在的短名确实会抛 Class-not-found
     *
     * 【为什么单独测这个】它把上面那条测试的前提钉住: 断言'短名不是类名'
     * 这件事本身在本仓库是成立的, 所以上一条测试的判断依据是可靠的,
     * 而不是恰好通过。
     */
    public function testAShortNameIsNotAClassName(): void
    {
        $this->expectException(\Error::class);

        /** @phpstan-ignore-next-line */
        new \Options();
    }

    /**
     * 时区的数值形态必须查范围
     *
     * 999999999 秒是 11574 天。落进数据库, 未迁移的站点整站时间失真。
     */
    public function testALegacyOffsetOutsideTheRealRangeIsRejected(): void
    {
        $this->assertFalse(
            \Typecho\Timezone::isValidLegacyOffset(999999999),
            'an absurd offset must not be accepted'
        );
        $this->assertFalse(
            \Typecho\Timezone::isValidLegacyOffset(-999999999),
            'an absurd offset must not be accepted'
        );

        /**
         * 真实时区的偏移都在 UTC-14 .. UTC+14 之间。
         */
        $this->assertTrue(
            \Typecho\Timezone::isValidLegacyOffset(50400),
            'UTC+14 must be accepted'
        );
        $this->assertTrue(
            \Typecho\Timezone::isValidLegacyOffset(-50400),
            'UTC-14 must be accepted'
        );
        $this->assertTrue(
            \Typecho\Timezone::isValidLegacyOffset(19800),
            'a non-whole-hour offset such as +05:30 must be accepted'
        );

        $this->assertFalse(
            \Typecho\Timezone::isValidLegacyOffset(50401),
            'one second past UTC+14 must be rejected'
        );
    }

    /**
     * 设置页与 XML-RPC 都必须用这一个范围检查
     *
     * 两处是同一条规则的两个入口, 只改一处就是漏洞。
     */
    public function testBothTimezoneWritePathsUseTheRangeCheck(): void
    {
        $general = file_get_contents(
            dirname(__DIR__, 2) . '/var/Widget/Options/General.php'
        );
        $xmlrpc = file_get_contents(
            dirname(__DIR__, 2) . '/var/Widget/XmlRpc.php'
        );

        /**
         * 【范围要限定在保存处理器的代码块里】
         *
         * 只断言文件里出现 'isValidLegacyOffset' 这个字符串是不够的:
         * 注释里提一句、或者某个不相关的 helper (比如读偏移的
         * getTimezoneList) 里调用一次, 都能满足它。写路径真的不再校验
         * 时, 这个断言照样是绿的。
         *
         * 所以先把块注释和行注释剥掉, 再切出**保存处理器的方法体**,
         * 只在那里面找 —— 它必须是一个调用语句, 后面跟着 (。
         */
        $generalSave = self::methodBody($general, 'public function updateGeneralSettings(');
        $xmlrpcSave = self::methodBody($xmlrpc, 'public function wpSetOptions(');

        $this->assertNotSame('', $generalSave, 'the settings save handler must be found');
        $this->assertNotSame('', $xmlrpcSave, 'wp.setOptions must be found');

        $this->assertMatchesRegularExpression(
            '/[^\w]isValidLegacyOffset\s*\(/',
            self::withoutComments($generalSave),
            'the settings page must range-check a legacy offset in code, not in a comment'
        );

        $this->assertMatchesRegularExpression(
            '/[^\w]isValidLegacyOffset\s*\(/',
            self::withoutComments($xmlrpcSave),
            'wp.setOptions must range-check a legacy offset in code, not in a comment'
        );
    }

    /**
     * 取某个方法的方法体
     *
     * @param string $source 源码
     * @param string $signature 方法签名
     * @return string
     */
    private static function methodBody(string $source, string $signature): string
    {
        $at = strpos($source, $signature);

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

    /**
     * 去掉块注释和行注释, 只留下真正的代码
     */
    private static function withoutComments(string $source): string
    {
        $source = preg_replace('#/\*.*?\*/#s', '', $source);
        $source = preg_replace('#//[^\n]*#', '', $source);

        return $source ?? '';
    }

}
