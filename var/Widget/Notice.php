<?php
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
     * 提示类型
     *
     * @access public
     * @var string
     */
    public $noticeType = 'notice';

    /**
     * 提示高亮
     *
     * @access public
     * @var string
     */
    public $highlight;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        if (NULL !== Typecho_Cookie::get('__typecho_notice')) {
            $this->noticeType = Typecho_Cookie::get('__typecho_notice_type');
            $this->push(Typecho_Cookie::get('__typecho_notice'));
            Typecho_Cookie::delete('__typecho_notice', $this->widget('Widget_Options')->siteUrl);
            Typecho_Cookie::delete('__typecho_notice_type', $this->widget('Widget_Options')->siteUrl);
        }

        if (NULL !== Typecho_Cookie::get('__typecho_notice_highlight')) {
            $this->highlight = Typecho_Cookie::get('__typecho_notice_highlight');
            Typecho_Cookie::delete('__typecho_notice_highlight', $this->widget('Widget_Options')->siteUrl);
        }
    }

    /**
     * 输出提示类型
     *
     * @access public
     * @return void
     */
    public function noticeType()
    {
        echo $this->noticeType;
    }

    /**
     * 列表显示所有提示内容
     *
     * @access public
     * @param string $tag 列表html标签
     * @return void
     */
    public function lists($tag = 'li')
    {
        foreach ($this->row as $row) {
            echo "<$tag>" . $row . "</$tag>";
        }
    }

    /**
     * 显示相应提示字段
     *
     * @access public
     * @param string $name 字段名称
     * @param string $format 字段格式
     * @return void
     */
    public function display($name, $format = '%s')
    {
        echo empty($this->row[$name]) ? NULL :
        ((false === strpos($format, '%s')) ? $format : sprintf($format, $this->row[$name]));
    }

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
        $this->widget('Widget_Options')->gmtTime + $this->widget('Widget_Options')->timezone + 86400,
        $this->widget('Widget_Options')->siteUrl);
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
     * @param string $name 值对应的键值
     * @param mixed $name 相应的值
     * @param string $type 提示类型
     * @return array
     */
    public function set($name, $value = NULL, $type = 'notice')
    {
        $notice = array();

        if (is_array($name)) {
            foreach ($name as $key => $row) {
                $notice[$key] = $row;
            }
        } else {
            if (empty($value)) {
                $notice[] = $name;
            } else {
                $notice[$name] = $value;
            }
        }

        $this->noticeType = $type;
        $this->push($notice);

        Typecho_Cookie::set('__typecho_notice', $notice,
        $this->widget('Widget_Options')->gmtTime + $this->widget('Widget_Options')->timezone + 86400,
        $this->widget('Widget_Options')->siteUrl);
        Typecho_Cookie::set('__typecho_notice_type', $type,
        $this->widget('Widget_Options')->gmtTime + $this->widget('Widget_Options')->timezone + 86400,
        $this->widget('Widget_Options')->siteUrl);
    }
}
