<?php

namespace Widget\Contents\Attachment;

use Typecho\Db;
use Widget\Base\Contents;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 文章相关文件组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Related extends Contents
{
    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Db\Exception
     */
    public function execute()
    {
        $this->parameter->setDefault('parentId=0&limit=0');

        //如果没有cid值
        if (!$this->parameter->parentId) {
            return;
        }

        /** 构建基础查询 */
        $select = $this->select()->where('table.contents.type = ?', 'attachment');

        //order字段在文件里代表所属文章
        $select->where('table.contents.parent = ?', $this->parameter->parentId);

        /** 提交查询 */
        $select->order('table.contents.created', Db::SORT_ASC);

        if ($this->parameter->limit > 0) {
            $select->limit($this->parameter->limit);
        }

        if ($this->parameter->offset > 0) {
            $select->offset($this->parameter->offset);
        }

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
