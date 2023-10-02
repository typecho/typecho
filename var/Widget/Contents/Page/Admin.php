<?php

namespace Widget\Contents\Page;

use Typecho\Db;
use Widget\Base\Contents;
use Widget\Contents\AdminTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 独立页面管理列表组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Contents
{
    use AdminTrait;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Db\Exception
     */
    public function execute()
    {
        /** 过滤状态 */
        $select = $this->select()->where(
            'table.contents.type = ? OR table.contents.type = ?',
            'page',
            'page_draft'
        );

        $this->searchQuery($select);

        /** 提交查询 */
        $select->order('table.contents.order');

        $this->db->fetchAll($select, [$this, 'push']);
    }
}
