<?php

namespace Typecho;

/**
 * 日期处理
 *
 * @author qining
 * @category typecho
 * @package Date
 */
class Date
{
    /**
     * 期望时区偏移
     *
     * @access public
     * @var integer
     */
    public static $timezoneOffset = 0;

    /**
     * 服务器时区偏移
     *
     * @access public
     * @var integer
     */
    public static $serverTimezoneOffset = 0;

    /**
     * 当前的服务器时间戳
     *
     * @access public
     * @var integer
     */
    public static $serverTimeStamp;

    /**
     * 可以被直接转换的时间戳
     *
     * @access public
     * @var integer
     */
    public $timeStamp = 0;

    /**
     * @var string
     */
    public $year;

    /**
     * @var string
     */
    public $month;

    /**
     * @var string
     */
    public $day;

    /**
     * 初始化参数
     *
     * @param integer|null $time 时间戳
     */
    public function __construct(?int $time = null)
    {
        $this->timeStamp = (null === $time ? self::time() : $time)
            + (self::$timezoneOffset - self::$serverTimezoneOffset);

        $this->year = date('Y', $this->timeStamp);
        $this->month = date('m', $this->timeStamp);
        $this->day = date('d', $this->timeStamp);
    }

    /**
     * 设置当前期望的时区偏移
     *
     * @param integer $offset
     */
    public static function setTimezoneOffset(int $offset)
    {
        self::$timezoneOffset = $offset;
        self::$serverTimezoneOffset = idate('Z');
    }

    /**
     * 获取格式化时间
     *
     * @param string $format 时间格式
     * @return string
     */
    public function format(string $format): string
    {
        return date($format, $this->timeStamp);
    }

    /**
     * 获取国际化偏移时间
     *
     * @return string
     */
    public function word(): string
    {
        return I18n::dateWord($this->timeStamp, self::time() + (self::$timezoneOffset - self::$serverTimezoneOffset));
    }

    /**
     * 获取GMT时间
     *
     * @deprecated
     * @return int
     */
    public static function gmtTime(): int
    {
        return self::time();
    }

    /**
     * 获取服务器时间
     *
     * @return int
     */
    public static function time(): int
    {
        return self::$serverTimeStamp ?: (self::$serverTimeStamp = time());
    }
}
