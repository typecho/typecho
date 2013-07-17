<?php
/**
 * 可以被Widget_Do调用的接口
 *
 * @package Widget
 * @version $id$
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @author qining <magike.net@gmail.com>
 * @license GNU General Public License 2.0
 */
interface Widget_Interface_Do
{
    /**
     * 接口需要实现的入口函数
     *
     * @access public
     * @return void
     */
    public function action();
}
