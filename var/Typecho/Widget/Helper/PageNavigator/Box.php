<?php

namespace Typecho\Widget\Helper\PageNavigator;

use Typecho\Widget\Helper\PageNavigator;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 盒状分页样式
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Box extends PageNavigator
{
    /**
     * 输出盒装样式分页栏
     *
     * @access public
     * @param string $prevWord 上一页文字
     * @param string $nextWord 下一页文字
     * @param int $splitPage 分割范围
     * @param string $splitWord 分割字符
     * @param array $template
     * @return void
     */
    public function render(
        string $prevWord = 'PREV',
        string $nextWord = 'NEXT',
        int $splitPage = 3,
        string $splitWord = '...',
        array $template = []
    ) {
        if ($this->total < 1) {
            return;
        }

        $default = [
            'itemTag' => 'li',
            'textTag' => 'span',
            'currentClass' => 'current',
            'prevClass' => 'prev',
            'nextClass' => 'next'
        ];

        $template = array_merge($default, $template);
        extract($template);

        // 定义item
        $itemBegin = empty($itemTag) ? '' : ('<' . $itemTag . '>');
        $itemCurrentBegin = empty($itemTag) ? '' : ('<' . $itemTag
            . (empty($currentClass) ? '' : ' class="' . $currentClass . '"') . '>');
        $itemPrevBegin = empty($itemTag) ? '' : ('<' . $itemTag
            . (empty($prevClass) ? '' : ' class="' . $prevClass . '"') . '>');
        $itemNextBegin = empty($itemTag) ? '' : ('<' . $itemTag
            . (empty($nextClass) ? '' : ' class="' . $nextClass . '"') . '>');
        $itemEnd = empty($itemTag) ? '' : ('</' . $itemTag . '>');
        $textBegin = empty($textTag) ? '' : ('<' . $textTag . '>');
        $textEnd = empty($textTag) ? '' : ('</' . $textTag . '>');
        $linkBegin = '<a href="%s">';
        $linkCurrentBegin = empty($itemTag) ? ('<a href="%s"'
            . (empty($currentClass) ? '' : ' class="' . $currentClass . '"') . '>')
            : $linkBegin;
        $linkPrevBegin = empty($itemTag) ? ('<a href="%s"'
            . (empty($prevClass) ? '' : ' class="' . $prevClass . '"') . '>')
            : $linkBegin;
        $linkNextBegin = empty($itemTag) ? ('<a href="%s"'
            . (empty($nextClass) ? '' : ' class="' . $nextClass . '"') . '>')
            : $linkBegin;
        $linkEnd = '</a>';

        $from = max(1, $this->currentPage - $splitPage);
        $to = min($this->totalPage, $this->currentPage + $splitPage);

        //输出上一页
        if ($this->currentPage > 1) {
            echo $itemPrevBegin . sprintf(
                $linkPrevBegin,
                str_replace($this->pageHolder, $this->currentPage - 1, $this->pageTemplate) . $this->anchor
            )
                . $prevWord . $linkEnd . $itemEnd;
        }

        //输出第一页
        if ($from > 1) {
            echo $itemBegin
                . sprintf($linkBegin, str_replace($this->pageHolder, 1, $this->pageTemplate) . $this->anchor)
                . '1' . $linkEnd . $itemEnd;

            if ($from > 2) {
                //输出省略号
                echo $itemBegin . $textBegin . $splitWord . $textEnd . $itemEnd;
            }
        }

        //输出中间页
        for ($i = $from; $i <= $to; $i++) {
            $current = ($i == $this->currentPage);

            echo ($current ? $itemCurrentBegin : $itemBegin) . sprintf(
                ($current ? $linkCurrentBegin : $linkBegin),
                str_replace($this->pageHolder, $i, $this->pageTemplate) . $this->anchor
            )
                . $i . $linkEnd . $itemEnd;
        }

        //输出最后页
        if ($to < $this->totalPage) {
            if ($to < $this->totalPage - 1) {
                echo $itemBegin . $textBegin . $splitWord . $textEnd . $itemEnd;
            }

            echo $itemBegin
                . sprintf(
                    $linkBegin,
                    str_replace($this->pageHolder, $this->totalPage, $this->pageTemplate) . $this->anchor
                )
                . $this->totalPage . $linkEnd . $itemEnd;
        }

        //输出下一页
        if ($this->currentPage < $this->totalPage) {
            echo $itemNextBegin . sprintf(
                $linkNextBegin,
                str_replace($this->pageHolder, $this->currentPage + 1, $this->pageTemplate) . $this->anchor
            )
                . $nextWord . $linkEnd . $itemEnd;
        }
    }
}
