<?php

namespace Widget\Contents\Page;

use Typecho\Common;
use Typecho\Db;
use Typecho\Widget\Exception;
use Widget\Base\Contents;
use Widget\Base\TreeTrait;
use Widget\Contents\AdminTrait;
use Widget\Contents\From;

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
     * @var int 父级页面
     */
    private int $parentId = 0;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Db\Exception
     */
    public function execute()
    {
        $this->parameter->setDefault('ignoreRequest=0');

        if ($this->parameter->ignoreRequest) {
            $this->pushAll($this->getRows($this->orders, $this->parameter->ignore));
        } elseif ($this->request->is('keywords')) {
            $select = $this->select('table.contents.cid')
                ->where('table.contents.type = ? OR table.contents.type = ?', 'page', 'page_draft');
            $this->searchQuery($select);

            $ids = array_column($this->db->fetchAll($select), 'cid');
            $this->pushAll($this->getRows($ids));
        } else {
            $this->parentId = $this->request->filter('int')->get('parent', 0);
            $this->pushAll($this->getRows($this->getChildIds($this->parentId)));
        }
    }

    /**
     * 向上的返回链接
     *
     * @throws Db\Exception
     */
    public function backLink()
    {
        if ($this->parentId) {
            $page = $this->getRow($this->parentId);

            if (!empty($page)) {
                $parent = $this->getRow($page['parent']);

                if ($parent) {
                    echo '<a href="'
                        . Common::url('manage-pages.php?parent=' . $parent['mid'], $this->options->adminUrl)
                        . '">';
                } else {
                    echo '<a href="' . Common::url('manage-pages.php', $this->options->adminUrl) . '">';
                }

                echo '&laquo; ';
                _e('返回父级页面');
                echo '</a>';
            }
        }
    }

    /**
     * 获取菜单标题
     *
     * @return string|null
     * @throws Db\Exception|Exception
     */
    public function getMenuTitle(): ?string
    {
        if ($this->parentId) {
            $page = $this->getRow($this->parentId);

            if (!empty($page)) {
                return _t('管理 %s 的子页面', $page['title']);
            }
        } else {
            return null;
        }

        throw new Exception(_t('页面不存在'), 404);
    }

    /**
     * 获取菜单标题
     *
     * @return string
     */
    public function getAddLink(): string
    {
        return 'write-page.php' . ($this->parentId ? '?parent=' . $this->parentId : '');
    }

    /**
     * @return array
     * @throws Db\Exception
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
