<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 经典分页样式
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Typecho_Widget_Helper_PageNavigator_Classic extends Typecho_Widget_Helper_PageNavigator
{
    /**
     * 输出经典样式的分页
     *
     * @access public
     * @param string $prevWord 上一页文字
     * @param string $nextWord 下一页文字
     * @return void
     */
    public function render($prevWord = 'PREV', $nextWord = 'NEXT')
    {
        $this->prev($prevWord);
        $this->next($nextWord);
    }

    /**
     * 输出上一页
     *
     * @access public
     * @param string $prevWord 上一页文字
     * @return void
     */
    public function prev($prevWord = 'PREV')
    {
        //输出上一页
        if ($this->_total > 0 && $this->_currentPage > 1) {
            echo '<a class="prev" href="' . str_replace($this->_pageHolder, $this->_currentPage - 1, $this->_pageTemplate) . $this->_anchor . '">'
            . $prevWord . '</a>';
        }
    }

    /**
     * 输出下一页
     *
     * @access public
     * @param string $prevWord 下一页文字
     * @return void
     */
    public function next($nextWord = 'NEXT')
    {
        //输出下一页
        if ($this->_total > 0 && $this->_currentPage < $this->_totalPage) {
            echo '<a class="next" title="" href="' . str_replace($this->_pageHolder, $this->_currentPage + 1, $this->_pageTemplate) . $this->_anchor . '">'
            . $nextWord . '</a>';
        }
    }
}
