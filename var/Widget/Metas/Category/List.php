<?php
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
     * _singleCategoryOptions  
     * 
     * @var mixed
     * @access private
     */
    private $_singleCategoryOptions = NULL;

    /**
     * 原始的stack备份 
     * 
     * @var array
     * @access private
     */
    private $_stack = array();

    /**
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     * @return void
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->parameter->setDefault('ignore=0&levelWalk=0');
        
        /** 初始化回调函数 */
        if (function_exists('treeViewCategories')) {
            $this->_customTreeViewCategoriesCallback = true;
        }
    }

    /**
     * 列出分类回调
     * 
     * @param mixed $singleCategoryOptions 
     * @access private
     * @return void
     */
    private function treeViewCategoriesCallback()
    {
        $levels = $this->levels;
        if (0 != $this->parent && isset($this->_stack[$this->parent]) && !$this->parameter->levelWalk) {
            $levels = $this->_stack[$this->parent]['levels'] + 1;
            $this->levels = $levels;
            $this->_stack[$this->mid]['levels'] = $levels;
        }

        $singleCategoryOptions = $this->_singleCategoryOptions;
        if ($this->_customTreeViewCategoriesCallback) {
            return treeViewCategories($this, $singleCategoryOptions);
        }

        $classes = array();

        if ($singleCategoryOptions->itemClass) {
            $classes[] = $singleCommentOptions->itemClass;
        }

        $classes[] = 'category-level-' . $this->levels;

        echo '<' . $singleCategoryOptions->itemTag . ' class="'
            . implode(' ', $classes);
        
        if ($this->levels > 0) {
            echo ' category-child';
            $this->levelsAlt(' category-level-odd', ' category-level-even');
        } else {
            echo ' category-parent';
        }

        echo '"><a href="' . $this->permalink . '">' . $this->name . '</a>';

        if ($singleCategoryOptions->showCount) {
            printf($singleCategoryOptions->countTemplate, intval($this->count));
        }

        if ($singleCategoryOptions->showFeed) {
            printf($singleCategoryOptions->feedTemplate, $this->feedUrl);
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
     * @access private
     * @return void
     */
    private function levelWalkCallback(array $categories)
    {
        foreach ($categories as $mid => $category) {
            $parent = $category['parent'];

            if (0 != $parent && isset($this->stack[$parent])) {
                $levels = $this->stack[$parent]['levels'] + 1;
                $this->stack[$mid]['levels'] = $levels;
                $this->_treeViewCategories[$parent][$mid]['levels'] = $levels;

                if (isset($this->_stack[$mid])) {
                    $this->_stack[$mid]['levels'] = $levels;
                }
            }

            if (!empty($this->_treeViewCategories[$mid])) {
                $this->levelWalkCallback($this->_treeViewCategories[$mid]);
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
        return !empty($this->_treeViewCategories[$this->mid]) 
            ? $this->_treeViewCategories[$this->mid] : array();
    }

    /**
     * 基于level的名称
     * 
     * @access protected
     * @return string
     */
    protected function ___nameByLevel()
    {
        return str_repeat(' ', $this->levels) . $this->name;
    }

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $select = $this->select()->where('type = ?', 'category');
        if ($this->parameter->ignore) {
            $select->where('mid <> ?', $this->parameter->ignore);
        }

        $this->db->fetchAll($select->order('table.metas.order', Typecho_Db::SORT_ASC),
            array($this, 'push'));

        // 读取数据
        foreach ($this->stack as $mid => $category) {
            $parent = $category['parent'];

            if (0 != $parent && isset($this->stack[$parent])) {
                $this->_treeViewCategories[$parent][$mid] = $category;
            } else {
                $this->_stack[$mid] = $category;
            }
        }

        // 预处理深度
        if ($this->parameter->levelWalk) {
            $this->levelWalkCallback($this->_stack);
        }
        
        reset($this->stack);
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
            echo '<' . $this->_singleCategoryOptions->wrapTag . (empty($this->_singleCategoryOptions->wrapClass) 
                ? '' : ' class="' . $this->_singleCategoryOptions->wrapClass . '"') . '>';

            foreach ($children as $child) {
                $this->row = $child;
                $this->treeViewCategoriesCallback();
                $this->row = $tmp;
            }

            //在子评论之后输出
            echo '</' . $this->_singleCategoryOptions->wrapTag . '>';

            $this->sequence --;
        }
    }

    /**
     * treeViewCategories  
     * 
     * @access public
     * @return void
     */
    public function listCategories($singleCategoryOptions = NULL)
    {
        //初始化一些变量
        $this->_singleCategoryOptions = Typecho_Config::factory($singleCategoryOptions);
        $this->_singleCategoryOptions->setDefault(array(
            'wrapTag'           =>  'ul',
            'wrapClass'         =>  '',
            'itemTag'           =>  'li',
            'itemClass'         =>  '',
            'showCount'         =>  false,
            'showFeed'          =>  false,
            'countTemplate'     =>  '(%d)',
            'feedTemplate'      =>  '<a href="%s">RSS</a>'
        ));

        // 存储原始数据方便树状访问
        $outputCategories = $this->_stack;
        $this->_stack = $this->stack;
        $this->stack = $outputCategories;

        // 插件插件接口
        $this->pluginHandle()->trigger($plugged)->listCategories($this->_singleCategoryOptions, $this);

        if (!$plugged) {
            if ($this->have()) { 
                echo '<' . $this->_singleCategoryOptions->wrapTag . (empty($this->_singleCategoryOptions->wrapClass) 
                    ? '' : ' class="' . $this->_singleCategoryOptions->wrapClass . '"') . '>';
                while ($this->next()) {
                    $this->treeViewCategoriesCallback();
                }
                echo '</' . $this->_singleCategoryOptions->wrapTag . '>';
            }
        }
    }

    /**
     * 根据深度余数输出
     *
     * @access public
     * @param string $param 需要输出的值
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
    public function push(array $value)
    {
        $value = $this->filter($value);
        
        /** 计算深度 */
        $value['levels'] = 0;

        /** 重载push函数,使用coid作为数组键值,便于索引 */
        $this->stack[$value['mid']] = $value;
        $this->length ++;
        $this->row = $value;
        
        return $value;
    }
}

