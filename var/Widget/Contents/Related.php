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
class Widget_Contents_Related extends Widget_Abstract_Contents
{
    /**
     * 获取查询对象
     *
     * @access public
     * @return Typecho_Db_Query
     */
    public function select()
    {
        return $this->db->select('DISTINCT table.contents.cid', 'table.contents.title', 'table.contents.slug', 'table.contents.created', 'table.contents.authorId',
        'table.contents.modified', 'table.contents.type', 'table.contents.status', 'table.contents.text', 'table.contents.commentsNum', 'table.contents.order',
        'table.contents.template', 'table.contents.password', 'table.contents.allowComment', 'table.contents.allowPing', 'table.contents.allowFeed')
        ->from('table.contents');
    }

    /**
     * 执行函数,初始化数据
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $this->parameter->setDefault('limit=5');

        if ($this->parameter->tags) {
            $tagsGroup = implode(',', Typecho_Common::arrayFlatten($this->parameter->tags, 'mid'));
            $this->db->fetchAll($this->select()
            ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid IN (' . $tagsGroup . ')')
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
