<?php

namespace Widget\Contents\Page;

use Typecho\Db;
use Widget\Base\Contents;
use Widget\Base\TreeTrait;
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
    use TreeTrait;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Db\Exception
     */
    public function execute()
    {
        $this->initPage();

        $parentId = $this->request->filter('int')->get('parent', 0);
        $this->stack = $this->getRows($this->getChildIds($parentId));
    }

    /**
     * @return array
     */
    protected function initTreeRows(): array
    {
        $select = $this->select(
            'table.contents.cid',
            'table.contents.title',
            'table.contents.slug',
            'table.contents.created',
            'table.contents.authorId',
            'table.contents.modified',
            'table.contents.type',
            'table.contents.status',
            'table.contents.commentsNum',
            'table.contents.order',
            'table.contents.parent',
            'table.contents.template',
            'table.contents.password',
        )->where('table.contents.type = ? OR table.contents.type = ?', 'page', 'page_draft');

        return $this->db->fetchAll($select);
    }
}
