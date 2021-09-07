<?php

namespace Widget\Metas\Tag;

use Typecho\Db;
use Typecho\Widget\Exception;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 标签云组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Admin extends Cloud
{
    /**
     * 入口函数
     *
     * @throws Db\Exception
     */
    public function execute()
    {
        $select = $this->select()->where('type = ?', 'tag')->order('mid', Db::SORT_DESC);
        $this->db->fetchAll($select, [$this, 'push']);
    }

    /**
     * 获取菜单标题
     *
     * @return string|null
     * @throws Exception|Db\Exception
     */
    public function getMenuTitle(): ?string
    {
        if (isset($this->request->mid)) {
            $tag = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'tag', $this->request->mid));

            if (!empty($tag)) {
                return _t('编辑标签 %s', $tag['name']);
            }
        } else {
            return null;
        }

        throw new Exception(_t('标签不存在'), 404);
    }
}
