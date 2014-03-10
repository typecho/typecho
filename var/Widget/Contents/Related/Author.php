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
 * 相关内容组件(根据作者关联)
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Contents_Related_Author extends Widget_Abstract_Contents
{
    /**
     * 执行函数,初始化数据
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $this->parameter->setDefault('limit=5');

        if ($this->parameter->author) {
            $this->db->fetchAll($this->select()
            ->where('table.contents.authorId = ?', $this->parameter->author)
            ->where('table.contents.cid <> ?', $this->parameter->cid)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.password IS NULL')
            ->where('table.contents.created < ?', $this->options->gmtTime)
            ->where('table.contents.type = ?', $this->parameter->type)
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->limit($this->parameter->limit), array($this, 'push'));
        }
    }
}
