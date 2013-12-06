<?php
/**
 * 直接在在文章中插入[embed_snipt:{code_id}]（{code_id}为snipt上面的id）就可引用http://snipt.org/上分享的代码
 * 
 * @package ShareCode
 * @author blankyao
 * @version 1.0.0
 * @link http://www.blankyao.cn
 */

class ShareCode_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        /** 前端输出处理接口 */
        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('ShareCode_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('ShareCode_Plugin', 'parse');
    }
    
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
     * 解析内容
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function parse($value, $lastResult)
    {
        $value = empty($lastResult) ? $value : $lastResult;
        $regex = '/\[embed_snipt:(.*?)]/i';
        preg_match_all( $regex, $value, $matches);
        
        $count = count($matches[0]);
        for($i = 0;$i < $count;$i++) {
            $url = $matches[1][$i];
            $url = '<script type="text/javascript" src="http://embed.snipt.org/'. $url .'"></script>';
            
            $value = str_replace($matches[0][$i], $url, $value);
        }
        
        return $value;
    }
}