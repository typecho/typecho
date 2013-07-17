<?php
/**
 * 将页面转化为友情链接列表的插件
 * 
 * @package Page To Links
 * @author qining
 * @version 1.0.0
 * @link http://typecho.org
 */
class PageToLinks implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){}
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 解析并输出
     * 
     * @access public
     * @param string $slug 页面标题
     * @param string $tag 标题的html tag
     * @param string $listTag 列表的html tag
     * @return void
     */
    public static function output($slug = 'links', $tag = 'h2', $listTag = 'ul')
    {
        /** 获取数据库支持 */
        $db = Typecho_Db::get();
        
        /** 获取文本 */
        $contents = $db->fetchObject($db->select('text')->from('table.contents')
        ->where('slug = ?', $slug)->limit(1));
        
        if (!$contents) {
            return;
        }
        
        $text = $contents->text;
        $cats = preg_split("/<\/(ol|ul)>/i", $text);
        
        foreach ($cats as $cat) {
            $item = trim($cat);
            
            if ($item) {
                $matches = array_map('trim', preg_split("/<(ol|ul)[^>]*>/i", $item));
                if (2 == count($matches)) {
                    list ($title, $list) = $matches;
                    echo "<$tag>" . strip_tags($title) . "</$tag>";
                    echo "<$listTag>" . $list . "</$listTag>";
                }
            }
        }
    }
}
