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
     * @var GetTextMulti
     */
    private static $loaded;

    /**
     * 语言文件
     *
     * @access private
     * @var string
     */
    private static $lang = null;

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
     * 词义化时间
     *
     * @access public
     *
     * @param int $from 起始时间
     * @param int $now 终止时间
     *
     * @return string
     */
    public static function dateWord(int $from, int $now): string
    {
        $between = $now - $from;

        /** 如果是一天 */
        if ($between >= 0 && $between < 86400 && date('d', $from) == date('d', $now)) {
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
            && $between < 172800
            && (date('z', $from) + 1 == date('z', $now)                             // 在同一年的情况
                || date('z', $from) + 1 == date('L') + 365 + date('z', $now))
        ) {    // 跨年的情况
            return _t('昨天 %s', date('H:i', $from));
        }

        /** 如果是一个星期 */
        if ($between > 0 && $between < 604800) {
            $day = floor($between / 86400);
            return str_replace('%d', $day, _n('一天前', '%d天前', $day));
        }

        /** 如果是 */
        if (date('Y', $from) == date('Y', $now)) {
            return date(_t('n月j日'), $from);
        }

        return date(_t('Y年m月d日'), $from);
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
