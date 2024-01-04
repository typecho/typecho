<?php

namespace Widget\Metas\Category;

use Typecho\Common;
use Typecho\Db;
use Typecho\Widget\Exception;
use Widget\Base\Metas;
use Widget\Base\TreeTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Category Admin
 */
class Admin extends Metas
{
    use InitTreeRowsTrait;
    use TreeTrait;

    /**
     * @var int Parent category
     */
    private int $parentId = 0;

    /**
     * 执行函数
     */
    public function execute()
    {
        $this->parentId = $this->request->filter('int')->get('parent', 0);
        $this->pushAll($this->getRows($this->getChildIds($this->parentId)));
    }

    /**
     * 向上的返回链接
     *
     * @throws Db\Exception
     */
    public function backLink()
    {
        if ($this->parentId) {
            $category = $this->getRow($this->parentId);

            if (!empty($category)) {
                $parent = $this->getRow($category['parent']);

                if ($parent) {
                    echo '<a href="'
                        . Common::url('manage-categories.php?parent=' . $parent['mid'], $this->options->adminUrl)
                        . '">';
                } else {
                    echo '<a href="' . Common::url('manage-categories.php', $this->options->adminUrl) . '">';
                }

                echo '&laquo; ';
                _e('返回父级分类');
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
            $category = $this->getRow($this->parentId);

            if (!empty($category)) {
                return _t('管理 %s 的子分类', $category['name']);
            }
        } else {
            return null;
        }

        throw new Exception(_t('分类不存在'), 404);
    }

    /**
     * 获取菜单标题
     *
     * @return string
     */
    public function getAddLink(): string
    {
        return 'category.php' . ($this->parentId ? '?parent=' . $this->parentId : '');
    }
}
