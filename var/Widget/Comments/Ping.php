<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 回响归档
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 回响归档组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Comments_Ping extends Widget_Abstract_Comments
{
    /**
     * _customSinglePingCallback 
     * 
     * @var boolean
     * @access private
     */
    private $_customSinglePingCallback = false;
    
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
        $this->parameter->setDefault('parentId=0');
        
        /** 初始化回调函数 */
        if (function_exists('singlePing')) {
            $this->_customSinglePingCallback = true;
        }
    }

    /**
     * 重载内容获取
     *
     * @access protected
     * @return void
     */
    protected function ___parentContent()
    {
        return $this->parameter->parentContent;
    }
    
    /**
     * 回响回调函数
     * 
     * @access private
     * @param string $singlePingOptions 单个回响自定义选项
     * @return void
     */
    private function singlePingCallback($singlePingOptions)
    {
        if ($this->_customSinglePingCallback) {
            return singlePing($this, $singlePingOptions);
        }

?>
<li id="<?php $this->theId(); ?>" class="ping-body">
    <div class="ping-title">
        <cite class="fn"><?php
        $singlePingOptions->beforeTitle();
        $this->author(true);
        $singlePingOptions->afterTitle();
        ?></cite>
    </div>
    <div class="ping-meta">
        <a href="<?php $this->permalink(); ?>"><?php $singlePingOptions->beforeDate();
        $this->date($singlePingOptions->dateFormat);
        $singlePingOptions->afterDate(); ?></a>
    </div>
    <?php $this->content(); ?>
</li>
<?php
    }
    
    /**
     * 输出文章回响数
     *
     * @access public
     * @param string $string 评论数格式化数据
     * @return void
     */
    public function num()
    {
        $args = func_get_args();
        if (!$args) {
            $args[] = '%d';
        }

        echo sprintf(isset($args[$this->length]) ? $args[$this->length] : array_pop($this->length), $this->length);
    }

    /**
     * execute  
     * 
     * @access public
     * @return void
     */
    public function execute()
    {
        if (!$this->parameter->parentId) {
            return;
        }
        
        $select = $this->select()->where('table.comments.status = ?', 'approved')
        ->where('table.comments.cid = ?', $this->parameter->parentId)
        ->where('table.comments.type <> ?', 'comment')
        ->order('table.comments.coid', 'ASC');

        $this->db->fetchAll($select, array($this, 'push'));
    }
    
    /**
     * 列出回响
     * 
     * @access private
     * @param mixed $singlePingOptions 单个回响自定义选项
     * @return void
     */
    public function listPings($singlePingOptions = NULL)
    {
        if ($this->have()) {
            //初始化一些变量
            $parsedSinglePingOptions = Typecho_Config::factory($singlePingOptions);
            $parsedSinglePingOptions->setDefault(array(
                'before'        =>  '<ol class="ping-list">',
                'after'         =>  '</ol>',
                'beforeTitle'   =>  '',
                'afterTitle'    =>  '',
                'beforeDate'    =>  '',
                'afterDate'     =>  '',
                'dateFormat'    =>  $this->options->commentDateFormat
            ));
        
            echo $parsedSinglePingOptions->before;
            
            while ($this->next()) {
                $this->singlePingCallback($parsedSinglePingOptions);
            }
            
            echo $parsedSinglePingOptions->after;
        }
    }
}
