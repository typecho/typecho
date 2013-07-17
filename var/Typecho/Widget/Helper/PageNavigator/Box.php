<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/** Typecho_Widget_Helper_PageNavigator */
require_once 'Typecho/Widget/Helper/PageNavigator.php';

/**
 * 盒状分页样式
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Widget_Helper_PageNavigator_Box extends Typecho_Widget_Helper_PageNavigator
{
    /**
     * 输出盒装样式分页栏
     *
     * @access public
     * @param string $prevWord 上一页文字
     * @param string $nextWord 下一页文字
     * @param int $splitPage 分割范围
     * @param string $splitWord 分割字符
     * @return void
     */
    public function render($prevWord = 'PREV', $nextWord = 'NEXT', $splitPage = 3, $splitWord = '...')
    {
        if ($this->_total < 1) {
            return;
        }

        $from = max(1, $this->_currentPage - $splitPage);
        $to = min($this->_totalPage, $this->_currentPage + $splitPage);

        //输出上一页
        if ($this->_currentPage > 1) {
            echo '<li><a class="prev" href="' . str_replace($this->_pageHolder, $this->_currentPage - 1, $this->_pageTemplate) . $this->_anchor . '">'
            . $prevWord . '</a></li>';
        }

        //输出第一页
        if ($from > 1) {
            echo '<li><a href="' . str_replace($this->_pageHolder, 1, $this->_pageTemplate) . $this->_anchor . '">1</a></li>';

            if ($from > 2) {
                //输出省略号
                echo '<li>' . $splitWord . '</li>';
            }
        }

        //输出中间页
        for ($i = $from; $i <= $to; $i ++) {
                echo '<li' . ($i != $this->_currentPage ? '' : ' class="current"') . '><a href="' .
                str_replace($this->_pageHolder, $i, $this->_pageTemplate) . $this->_anchor . '">'
                . $i . '</a></li>';
        }

        //输出最后页
        if ($to < $this->_totalPage) {
            if ($to < $this->_totalPage - 1) {
                echo '<li>' . $splitWord . '</li>';
            }

            echo '<li><a href="' . str_replace($this->_pageHolder, $this->_totalPage, $this->_pageTemplate) . $this->_anchor . '">'
            . $this->_totalPage . '</a></li>';
        }

        //输出下一页
        if ($this->_currentPage < $this->_totalPage) {
            echo '<li><a class="next" href="' . str_replace($this->_pageHolder, $this->_currentPage + 1, $this->_pageTemplate)
            . $this->_anchor . '">' . $nextWord . '</a></li>';
        }
    }
}
