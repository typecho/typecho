<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 标签云
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 标签云组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Metas_Tag_Cloud extends Widget_Abstract_Metas
{
    /**
     * 入口函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $this->parameter->setDefault(['sort' => 'count', 'ignoreZeroCount' => false, 'desc' => true, 'limit' => 0]);
        $select = $this->select()->where('type = ?', 'tag')->order($this->parameter->sort,
            $this->parameter->desc ? Typecho_Db::SORT_DESC : Typecho_Db::SORT_ASC);

        /** 忽略零数量 */
        if ($this->parameter->ignoreZeroCount) {
            $select->where('count > 0');
        }

        /** 总数限制 */
        if ($this->parameter->limit) {
            $select->limit($this->parameter->limit);
        }

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * 按分割数输出字符串
     *
     * @param ...$args 需要输出的值
     */
    public function split(...$args)
    {
        array_unshift($args, $this->count);
        echo call_user_func_array(['Typecho_Common', 'splitByCount'], $args);
    }
}
