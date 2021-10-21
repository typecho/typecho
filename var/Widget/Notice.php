<?php

namespace Widget;

use Typecho\Cookie;
use Typecho\Widget;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 提示框组件
 *
 * @package Widget
 */
class Notice extends Widget
{
    /**
     * 提示高亮
     *
     * @var string
     */
    public $highlight;

    /**
     * 高亮相关元素
     *
     * @param string $theId 需要高亮元素的id
     */
    public function highlight(string $theId)
    {
        $this->highlight = $theId;
        Cookie::set(
            '__typecho_notice_highlight',
            $theId
        );
    }

    /**
     * 获取高亮的id
     *
     * @return integer
     */
    public function getHighlightId(): int
    {
        return preg_match("/[0-9]+/", $this->highlight, $matches) ? $matches[0] : 0;
    }

    /**
     * 设定堆栈每一行的值
     *
     * @param string|array $value 值对应的键值
     * @param string|null $type 提示类型
     * @param string $typeFix 兼容老插件
     */
    public function set($value, ?string $type = 'notice', string $typeFix = 'notice')
    {
        $notice = is_array($value) ? array_values($value) : [$value];
        if (empty($type) && $typeFix) {
            $type = $typeFix;
        }

        Cookie::set(
            '__typecho_notice',
            json_encode($notice)
        );
        Cookie::set(
            '__typecho_notice_type',
            $type
        );
    }
}
