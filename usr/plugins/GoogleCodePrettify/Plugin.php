<?php
/**
 * Google高亮代码
 * 
 * @package Google Code Prettify
 * @author qining
 * @version 1.0.0
 * @dependence 9.9.2-*
 * @link http://typecho.org
 */
class GoogleCodePrettify_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('GoogleCodePrettify_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('GoogleCodePrettify_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('GoogleCodePrettify_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Archive')->header = array('GoogleCodePrettify_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('GoogleCodePrettify_Plugin', 'footer');
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
     * 输出头部css
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function header() {
        $cssUrl = Helper::options()->pluginUrl . '/GoogleCodePrettify/src/prettify.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
    }
    
    /**
     * 输出尾部js
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function footer() {
        $jsUrl = Helper::options()->pluginUrl . '/GoogleCodePrettify/src/prettify.js';
        echo '<script type="text/javascript" src="'. $jsUrl .'"></script>';
        echo '<script type="text/javascript">window.onload = function () {
            prettyPrint();
        }</script>';
    }
    
    /**
     * 解析
     * 
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallback($matches)
    {
        $language = trim($matches[2]);
        
        $map = array(
            'js'                =>  'javascript',
            'as'                =>  'actionscript',
            'as3'               =>  'actionscript3'
        );
        
        if (!empty($language) && isset($map[$language])) {
            $language = $map[$language];
        }
        
        $source = '<div class="prettyprint-box"><table class="prettyprint-table"><tr>';
        $numberItem = '<td width="2%" class="number"><table>';
        $sourceItem = '<td width="98%" class="code"><pre' . (empty($language) ? '' : ' id="' . $language . '"') . ' class="prettyprint"><table>';
        
        $sourceList = explode("\n", trim($matches[3]));
        foreach ($sourceList as $key => $sourceLine) {
            $numberItem .= '<tr><td>' . ($key + 1) . '</td></tr>';
            $sourceItem .= '<tr><td class="source">' . htmlspecialchars($sourceLine) . '</td></tr>';
        }
        
        $numberItem .= '</table></td>';
        $sourceItem .= '</table></pre></td>';
        
        return $source . $numberItem . $sourceItem . '</tr></table></div>';
    }
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;
        
        if ($widget instanceof Widget_Archive || $widget instanceof Widget_Abstract_Comments) {
            return preg_replace_callback("/<(code|pre)(\s*[^>]*)>(.*?)<\/\\1>/is", array('GoogleCodePrettify_Plugin', 'parseCallback'), $text);
        } else {
            return $text;
        }
    }
}
