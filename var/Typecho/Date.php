<?php

/**
 * 日期处理
 *
 * @author qining
 * @category typecho
 * @package Date
 */
class Typecho_Date
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
     * 当前的GMT时间戳
     *
     * @access public
     * @var integer
     */
    public static $gmtTimeStamp;

    /**
     * 可以被直接转换的时间戳
     *
     * @access public
     * @var integer
     */
    public $timeStamp = 0;

    /**
     * 初始化参数
     *
     * @access public
     * @param integer $gmtTime GMT时间戳
     * @return void
     */
    public function __construct($gmtTime)
    {
        $this->timeStamp = $gmtTime + (self::$timezoneOffset - self::$serverTimezoneOffset);
    }

    /**
     * 设置当前期望的时区偏移
     *
     * @access public
     * @param integer $offset
     * @return void
     */
    public static function setTimezoneOffset($offset)
    {
        self::$timezoneOffset = $offset;
        self::$serverTimezoneOffset = idate('Z');
    }

    /**
     * 获取格式化时间
     *
     * @access public
     * @param string $format 时间格式
     * @return string
     */
    public function format($format)
    {
        return date($format, $this->timeStamp);
    }

    /**
     * 获取国际化偏移时间
     *
     * @access public
     * @return string
     */
    public function word()
    {
        return Typecho_I18n::dateWord($this->timeStamp, self::gmtTime() + (self::$timezoneOffset - self::$serverTimezoneOffset));
    }

    /**
     * 获取单项数据
     *
     * @access public
     * @param string $name 名称
     * @return integer
     */
    public function __get($name)
    {
        switch ($name) {
            case 'year':
                return date('Y', $this->timeStamp);
            case 'month':
                return date('m', $this->timeStamp);
            case 'day':
                return date('d', $this->timeStamp);
            default:
                return;
        }
    }

    /**
     * 获取GMT时间
     *
     * @access public
     * @return integer
     */
    public static function gmtTime()
    {
        return self::$gmtTimeStamp ? self::$gmtTimeStamp : (self::$gmtTimeStamp = @gmmktime());
    }
}
