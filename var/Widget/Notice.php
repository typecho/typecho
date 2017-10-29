<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: Widget.php 48 2008-03-16 02:51:40Z magike.net $
 */

/**
 * 提示框组件
 *
 * @package Widget
 */
class Widget_Notice extends Typecho_Widget
{
    /**
     * 提示高亮
     *
     * @access public
     * @var string
     */
    public $highlight;

    /**
     * 高亮相关元素
     *
     * @access public
     * @param string $theId 需要高亮元素的id
     * @return void
     */
    public function highlight($theId)
    {
        $this->highlight = $theId;
        Typecho_Cookie::set('__typecho_notice_highlight', $theId,
        $this->widget('Widget_Options')->time + $this->widget('Widget_Options')->timezone + 86400);
    }

    /**
     * 获取高亮的id
     *
     * @access public
     * @return integer
     */
    public function getHighlightId()
    {
        return preg_match("/[0-9]+/", $this->highlight, $matches) ? $matches[0] : 0;
    }

    /**
     * 设定堆栈每一行的值
     *
     * @param string $value 值对应的键值
     * @param string $type 提示类型
     * @param string $typeFix 兼容老插件
     * @return array
     */
    public function set($value, $type = 'notice', $typeFix = 'notice')
    {
        $notice = is_array($value) ? array_values($value) : array($value);
        if (empty($type) && $typeFix) {
            $type = $typeFix;
        }

        Typecho_Cookie::set('__typecho_notice', Json::encode($notice),
        $this->widget('Widget_Options')->time + $this->widget('Widget_Options')->timezone + 86400);
        Typecho_Cookie::set('__typecho_notice_type', $type,
        $this->widget('Widget_Options')->time + $this->widget('Widget_Options')->timezone + 86400);
    }
}
