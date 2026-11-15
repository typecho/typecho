<?php

namespace Widget\Contents\Post;

use Typecho\Cookie;
use Typecho\Db;
use Typecho\Db\Exception as DbException;
use Typecho\Widget\Exception;
use Widget\Base\Contents;
use Widget\Contents\AdminTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 文章管理列表组件
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
     * 获取菜单标题
     *
     * @return string
     * @throws Exception|DbException
     */
    public function getMenuTitle(): string
    {
        if ($this->request->is('uid')) {
            return _t('%s的文章', $this->db->fetchObject($this->db->select('screenName')->from('table.users')
                ->where('uid = ?', $this->request->filter('int')->get('uid')))->screenName);
        }

        throw new Exception(_t('用户不存在'), 404);
    }

    /**
     * 执行函数
     *
     * @throws DbException
     */
    public function execute()
    {
        $this->initPage();

        /** 构建基础查询 */
        $select = $this->select();

        /** 如果具有编辑以上权限,可以查看所有文章,反之只能查看自己的文章 */
        if (!$this->user->pass('editor', true)) {
            $select->where('table.contents.authorId = ?', $this->user->uid);
        } else {
            if ($this->request->is('__typecho_all_posts=on')) {
                Cookie::set('__typecho_all_posts', 'on');
            } else {
                if ($this->request->is('__typecho_all_posts=off')) {
                    Cookie::set('__typecho_all_posts', 'off');
                }

                if ('on' != Cookie::get('__typecho_all_posts')) {
                    $select->where(
                        'table.contents.authorId = ?',
                        $this->request->filter('int')->get('uid', $this->user->uid)
                    );
                }
            }
        }

        /** 按状态查询 */
        if ($this->request->is('status=draft')) {
            $select->where('table.contents.type = ?', 'post_draft');
        } elseif ($this->request->is('status=waiting')) {
            $select->where(
                '(table.contents.type = ? OR table.contents.type = ?) AND table.contents.status = ?',
                'post',
                'post_draft',
                'waiting'
            );
        } else {
            $select->where(
                'table.contents.type = ? OR table.contents.type = ?',
                'post',
                'post_draft'
            );
        }

        /** 过滤分类 */
        if (null != ($category = $this->request->get('category'))) {
            $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->where('table.relationships.mid = ?', $category);
        }

        $this->searchQuery($select);
        $this->countTotal($select);

        /** 提交查询 */
        $select->order('table.contents.cid', Db::SORT_DESC)
            ->page($this->currentPage, $this->parameter->pageSize);

        $result = $this->db->fetchAll($select);

        // Early return if no posts found to avoid unnecessary queries
        if (empty($result)) {
            return;
        }
        /* 所有的内容id */
        $cid = array_column($result, 'cid');

        /* 批量获取修订版 */
        $select = $this->select('parent', 'modified')
            ->where('table.contents.parent in ?', $cid)
            ->where('table.contents.type = ?', 'revision');
        $revisions = $this->db->fetchAll($select);
        $revisions = array_column($revisions, 'modified', 'parent');

        /* 批量获取分类 */
        $select = $this->select('table.relationships.cid,table.metas.*')
            ->from('table.relationships')
            ->join('table.metas', 'table.relationships.mid = table.metas.mid')
            ->where('table.metas.type = ?', 'category')
            ->where('table.relationships.cid IN ?', $cid);
        $categories = $this->db->fetchAll($select);
        $contentCategories = [];
        foreach ($categories as $item) {
            $contentCategories[$item['cid']][] = $item;
        }

        /* 补全分类、修订版 */
        foreach ($result as &$item) {
            $item['#categories'] = $contentCategories[$item['cid']] ?? [];
            if (isset($revisions[$item['cid']])) {
                $item['#revision'] = ['cid' => $item['cid'], 'modified' => $revisions[$item['cid']]];
            } else {
                $item['#revision'] = null;
            }
        }
        unset($item);

        array_map([$this, 'push'], $result);
    }
}
