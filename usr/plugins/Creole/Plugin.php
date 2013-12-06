<?php
/**
 * 使用 <a href="http://www.wikicreole.org/" target="_blank">Creole 语法</a>发布文章。改进版本支持中文（utf-8 编码）、并除去不必要的标签。
 * 
 * @package Creole 解析器（改进版）
 * @author 明城<i.feelinglucky@gmail.com>
 * @version 0.2
 * @link http://www.gracecode.com/
 */

require_once 'Creole/Creole_Wiki.php';

class Creole_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('Creole_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('Creole_Plugin', 'parse');
    }
 
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {
    
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
 
 
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function parse($text, $widget, $lastResult) {
        $text = empty($lastResult) ? $text : $lastResult;
        $creole_parse = new Creole_Wiki;
        return $creole_parse->transform(trim($text));
    }
}
