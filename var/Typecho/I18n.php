<?php

namespace Typecho;

use Typecho\I18n\GetTextMulti;

/**
 * 国际化字符翻译
 *
 * @package I18n
 */
class I18n
{
    /**
     * 是否已经载入的标志位
     *
     * @access private
     * @var GetTextMulti|null
     */
    private static ?GetTextMulti $loaded = null;

    /**
     * 语言文件
     *
     * @access private
     * @var string|null
     */
    private static ?string $lang = null;

    /**
     * 翻译文字
     *
     * @access public
     *
     * @param string $string 待翻译的文字
     *
     * @return string
     */
    public static function translate(string $string): string
    {
        self::init();
        return self::$loaded ? self::$loaded->translate($string) : $string;
    }

    /**
     * 初始化语言文件
     *
     * @access private
     */
    private static function init()
    {
        /** GetText支持 */
        if (!isset(self::$loaded) && self::$lang && file_exists(self::$lang)) {
            self::$loaded = new GetTextMulti(self::$lang);
        }
    }

    /**
     * 针对复数形式的翻译函数
     *
     * @param string $single 单数形式的翻译
     * @param string $plural 复数形式的翻译
     * @param integer $number 数字
     * @return string
     */
    public static function ngettext(string $single, string $plural, int $number): string
    {
        self::init();
        return self::$loaded ? self::$loaded->ngettext($single, $plural, $number) : ($number > 1 ? $plural : $single);
    }

    /**
     * 两参数调用沿用服务器日历；传 elapsed 时两个日期值为站点墙钟。
     * 真实秒差用于小时、分钟与过去/未来判断，日历差用于“昨天”“N 天前”。
     *
     * @param int $from 起始日期值
     * @param int $now 参考日期值
     * @param int|null $elapsed 两个真实 UTC 瞬间的秒差
     */
    public static function dateWord(int $from, int $now, ?int $elapsed = null): string
    {
        $format = $elapsed === null ? 'date' : 'gmdate';
        $wallBetween = $now - $from;
        $between = $elapsed ?? $wallBetween;

        /** 如果是一天 */
        if ($between >= 0 && $wallBetween < 86400 && $format('d', $from) == $format('d', $now)) {
            /** 如果是一小时 */
            if ($between < 3600) {
                /** 如果是一分钟 */
                if ($between < 60) {
                    if (0 == $between) {
                        return _t('刚刚');
                    } else {
                        return str_replace('%d', $between, _n('一秒前', '%d秒前', $between));
                    }
                }

                $min = floor($between / 60);
                return str_replace('%d', $min, _n('一分钟前', '%d分钟前', $min));
            }

            $hour = floor($between / 3600);
            return str_replace('%d', $hour, _n('一小时前', '%d小时前', $hour));
        }

        /** 如果是昨天 */
        if (
            $between > 0
            && $wallBetween > 0
            && $wallBetween < 172800
            && ($elapsed !== null
                ? gmdate('Y-m-d', $from + 86400) === gmdate('Y-m-d', $now)
                : ($format('z', $from) + 1 == $format('z', $now)
                    || $format('z', $from) + 1 == $format('L') + 365 + $format('z', $now)))
        ) {    // 跨年的情况
            return _t('昨天 %s', $format('H:i', $from));
        }

        /**
         * 如果是一个星期
         *
         * 【为什么这里用墙钟差而不是 $between】"N 天前"是**日历**概念,
         * 数的是跨了几个日历年边界, 不是过了多少个 24 小时。
         *
         * 夏令时切换日有 23 或 25 小时。真实经过 2.96 天而墙钟上是 3 天
         * 整 (春季跳过一小时) 时, floor(elapsed / 86400) 会报 2 天, 而
         * 日历上明明是 3 天前。反过来秋季重复的那一小时 (钟往回拨), 真实
         * 过了 2 天零 1 小时, 而墙钟上只走了 2 天整, 会报 1 天。
         *
         * 上面"同一天"/"昨天"已经用墙钟判断了, 这里保持一致。
         */
        if ($between > 0 && $wallBetween > 0 && $wallBetween < 604800) {
            $day = (int) floor($wallBetween / 86400);
            return str_replace('%d', $day, _n('一天前', '%d天前', $day));
        }

        /** 如果是 */
        if ($format('Y', $from) == $format('Y', $now)) {
            return $format(_t('n月j日'), $from);
        }

        return $format(_t('Y年m月d日'), $from);
    }

    /**
     * 增加语言项
     *
     * @access public
     *
     * @param string $lang 语言名称
     *
     * @return void
     */
    public static function addLang(string $lang)
    {
        self::$loaded->addFile($lang);
    }

    /**
     * 获取语言项
     *
     * @access public
     * @return string
     */
    public static function getLang(): ?string
    {
        return self::$lang;
    }

    /**
     * 设置语言项
     *
     * @access public
     *
     * @param string $lang 配置信息
     *
     * @return void
     */
    public static function setLang(string $lang)
    {
        self::$lang = $lang;
    }
}
