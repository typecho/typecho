<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 异常处理组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_ExceptionHandle extends Widget_Archive
{
    /**
     * 重载构造函数
     *
     * @access public
     * @param Exception $excepiton 抛出的异常
     * @return void
     */
    public function __construct()
    {
        $this->widget('Widget_Archive@404', 'type=404')->render();
        exit;
    }
}
