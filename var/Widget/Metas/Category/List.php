<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 分类输出
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 分类输出组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Metas_Category_List extends Widget_Abstract_Metas
{
    /**
     * 多级分类回调函数
     * 
     * @var boolean
     * @access private
     */
    private $_customTreeViewCategoriesCallback = false;

    /**
     * 树状分类结构 
     * 
     * @var array
     * @access private
     */
    private $_treeViewCategories = array();

    /**
     * _categoryOptions
     * 
     * @var mixed
     * @access private
     */
    private $_categoryOptions = NULL;

    /**
     * 顶层分类
     * 
     * @var array
     * @access private
     */
    private $_top = array();

    /**
     * 所有分类哈希表 
     * 
     * @var array
     * @access private
     */
    private $_map = array();

    /**
     * 顺序流
     * 
     * @var array
     * @access private
     */
    private $_orders = array();

    /**
     * 所有子节点列表 
     * 
     * @var array
     * @access private
     */
    private $_children = array();

    /**
     * 所有父节点列表 
     * 
     * @var array
     * @access private
     */
    private $_parents = array();

    /**
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->parameter->setDefault('ignore=0&current=');
        
        /** 初始化回调函数 */
        if (function_exists('treeViewCategories')) {
            $this->_customTreeViewCategoriesCallback = true;
        }

        $select = $this->select()->where('type = ?', 'category');
        if ($this->parameter->ignore) {
            $select->where('mid <> ?', $this->parameter->ignore);
        }

        $categories = $this->db->fetchAll($select->order('table.metas.order', Typecho_Db::SORT_ASC));
        foreach ($categories as $category) {
            $category['levels'] = 0;
            $this->_map[$category['mid']] = $category;
        }

        // 读取数据
        foreach ($this->_map as $mid => $category) {
            $parent = $category['parent'];

            if (0 != $parent && isset($this->_map[$parent])) {
                $this->_treeViewCategories[$parent][] = $mid;
            } else {
                $this->_top[] = $mid;
            }
        }
        
        // 预处理深度
        $this->levelWalkCallback($this->_top);
        $this->_map = array_map(array($this, 'filter'), $this->_map);
    }

    /**
     * 列出分类回调
     * 
     * @access private
     */
    private function treeViewCategoriesCallback()
    {
        $categoryOptions = $this->_categoryOptions;
        if ($this->_customTreeViewCategoriesCallback) {
            return treeViewCategories($this, $categoryOptions);
        }

        $classes = array();

        if ($categoryOptions->itemClass) {
            $classes[] = $categoryOptions->itemClass;
        }

        $classes[] = 'category-level-' . $this->levels;

        echo '<' . $categoryOptions->itemTag . ' class="'
            . implode(' ', $classes);
        
        if ($this->levels > 0) {
            echo ' category-child';
            $this->levelsAlt(' category-level-odd', ' category-level-even');
        } else {
            echo ' category-parent';
        }

        if ($this->mid == $this->parameter->current) {
            echo ' category-active';
        } else if (isset($this->_children[$this->mid]) && in_array($this->parameter->current, $this->_children[$this->mid])) {
            echo ' category-parent-active';
        }

        echo '"><a href="' . $this->permalink . '">' . $this->name . '</a>';

        if ($categoryOptions->showCount) {
            printf($categoryOptions->countTemplate, intval($this->count));
        }

        if ($categoryOptions->showFeed) {
            printf($categoryOptions->feedTemplate, $this->feedUrl);
        }

        if ($this->children) {
            $this->treeViewCategories();
        }

        echo '</li>';
    }

    /**
     * 预处理分类迭代
     * 
     * @param array $categories
     * @param array $parents
     * @access private
     */
    private function levelWalkCallback(array $categories, $parents = array())
    {
        foreach ($parents as $parent) {
            if (!isset($this->_children[$parent])) {
                $this->_children[$parent] = array();
            }

            $this->_children[$parent] = array_merge($this->_children[$parent], $categories);
        }
        
        foreach ($categories as $mid) {
            $this->_orders[] = $mid;
            $parent = $this->_map[$mid]['parent'];

            if (0 != $parent && isset($this->_map[$parent])) {
                $levels = $this->_map[$parent]['levels'] + 1;
                $this->_map[$mid]['levels'] = $levels;
            }

            $this->_parents[$mid] = $parents;

            if (!empty($this->_treeViewCategories[$mid])) {
                $new = $parents;
                $new[] = $mid;
                $this->levelWalkCallback($this->_treeViewCategories[$mid], $new);
            }
        }
    }

    /**
     * 子评论
     *
     * @access protected
     * @return array
     */
    protected function ___children()
    {
        return isset($this->_treeViewCategories[$this->mid]) ?
            $this->getCategories($this->_treeViewCategories[$this->mid]) : array();
    }

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $this->stack = $this->getCategories($this->_orders);
    }

    /**
     * treeViewCategories  
     * 
     * @access public
     * @return void
     */
    public function treeViewCategories()
    {
        $children = $this->children;
        if ($children) {
            //缓存变量便于还原
            $tmp = $this->row;
            $this->sequence ++;

            //在子评论之前输出
            echo '<' . $this->_categoryOptions->wrapTag . (empty($this->_categoryOptions->wrapClass)
                ? '' : ' class="' . $this->_categoryOptions->wrapClass . '"') . '>';

            foreach ($children as $child) {
                $this->row = $child;
                $this->treeViewCategoriesCallback();
                $this->row = $tmp;
            }

            //在子评论之后输出
            echo '</' . $this->_categoryOptions->wrapTag . '>';

            $this->sequence --;
        }
    }

    /**
     * treeViewCategories  
     *
     * @param $categoryOptions 输出选项
     * @access public
     * @return void
     */
    public function listCategories($categoryOptions = NULL)
    {
        //初始化一些变量
        $this->_categoryOptions = Typecho_Config::factory($categoryOptions);
        $this->_categoryOptions->setDefault(array(
            'wrapTag'           =>  'ul',
            'wrapClass'         =>  '',
            'itemTag'           =>  'li',
            'itemClass'         =>  '',
            'showCount'         =>  false,
            'showFeed'          =>  false,
            'countTemplate'     =>  '(%d)',
            'feedTemplate'      =>  '<a href="%s">RSS</a>'
        ));

        // 插件插件接口
        $this->pluginHandle()->trigger($plugged)->listCategories($this->_categoryOptions, $this);

        if (!$plugged) {
            $this->stack = $this->getCategories($this->_top);

            if ($this->have()) { 
                echo '<' . $this->_categoryOptions->wrapTag . (empty($this->_categoryOptions->wrapClass)
                    ? '' : ' class="' . $this->_categoryOptions->wrapClass . '"') . '>';
                while ($this->next()) {
                    $this->treeViewCategoriesCallback();
                }
                echo '</' . $this->_categoryOptions->wrapTag . '>';
            }

            $this->stack = $this->_map;
        }
    }

    /**
     * 根据深度余数输出
     *
     * @access public
     * @return void
     */
    public function levelsAlt()
    {
        $args = func_get_args();
        $num = func_num_args();
        $split = $this->levels % $num;
        echo $args[(0 == $split ? $num : $split) -1];
    }

    /**
     * 将每行的值压入堆栈
     *
     * @access public
     * @param array $value 每行的值
     * @return array
     */
    public function filter(array $value)
    {
        $value['directory'] = $this->getAllParents($value['mid']);
        $value['directory'][] = $value['slug'];

        $tmpCategoryTree = $value['directory'];
        $value['directory'] = implode('/', array_map('urlencode', $value['directory']));

        $value = parent::filter($value);
        $value['directory'] = $tmpCategoryTree;

        return $value;
    }

    /**
     * 获取某个分类下的所有子节点
     * 
     * @param mixed $mid 
     * @access public
     * @return array
     */
    public function getAllChildren($mid)
    {
        return isset($this->_children[$mid]) ? $this->_children[$mid] : array();
    }

    /**
     * 获取某个分类所有父级节点
     * 
     * @param mixed $mid 
     * @access public
     * @return array
     */
    public function getAllParents($mid)
    {
        $parents = array();
        
        if (isset($this->_parents[$mid])) {
            foreach ($this->_parents[$mid] as $parent) {
                $parents[] = $this->_map[$parent]['slug'];
            }
        }

        return $parents;
    }

    /**
     * 获取单个分类
     * 
     * @param integer $mid 
     * @access public
     * @return mixed
     */
    public function getCategory($mid)
    {
        return isset($this->_map[$mid]) ? $this->_map[$mid] : NULL;
    }

    /**
     * 获取多个分类 
     * 
     * @param mixed $mids 
     * @access public
     * @return array
     */
    public function getCategories($mids)
    {
        $result = array();

        if (!empty($mids)) {
            foreach ($mids as $mid) {
                $result[] = $this->_map[$mid];
            }
        }

        return $result;
    }
}

