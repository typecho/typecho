<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 相关内容
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 相关内容组件(根据标签关联)
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Users_Author extends Widget_Abstract_Users
{
    /**
     * 执行函数,初始化数据
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        if ($this->parameter->uid) {
            $this->db->fetchRow($this->select()
            ->where('uid = ?', $this->parameter->uid), array($this, 'push'));
        }
    }
}
