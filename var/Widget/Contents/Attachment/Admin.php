<?php

namespace Widget\Contents\Attachment;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Exception;
use Widget\Base\Contents;
use Widget\Contents\AdminTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 文件管理列表组件
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
     * @return void
     * @throws Exception|\Typecho\Widget\Exception
     */
    public function execute()
    {
        $this->initPage();

        /** 构建基础查询 */
        $select = $this->select()->where('table.contents.type = ?', 'attachment');

        /** 如果具有编辑以上权限,可以查看所有文件,反之只能查看自己的文件 */
        if (!$this->user->pass('editor', true)) {
            $select->where('table.contents.authorId = ?', $this->user->uid);
        }

        /** 过滤标题 */
        $this->searchQuery($select);
        $this->countTotal($select);

        /** 提交查询 */
        $select->order('table.contents.created', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);

        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * 所属文章
     *
     * @return Config
     * @throws Exception
     */
    protected function ___parentPost(): Config
    {
        return new Config($this->db->fetchRow(
            $this->select()->where('table.contents.cid = ?', $this->parent)->limit(1)
        ));
    }
}
