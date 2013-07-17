<?php
/**
 * google code svn 同步文章
 * 
 * @package Google Code SVN Transmit
 * @author qining
 * @version 1.0.0
 * @dependence 10.6.24-*
 * @link http://typecho.org
 */
class GoogleCodeSVN_Plugin implements Typecho_Plugin_Interface
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
        if (false == Typecho_Http_Client::get()) {
            throw new Typecho_Plugin_Exception(_t('对不起, 您的主机不支持 php-curl 扩展而且没有打开 allow_url_fopen 功能, 无法正常使用此功能'));
        }
    
        Helper::addAction('googlecode-svn', 'GoogleCodeSVN_Action');
        return _t('请在插件设置里设置 Google Code 的SVN参数') . $error;
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeAction('googlecode-svn');
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
        $secretKey = new Typecho_Widget_Helper_Form_Element_Text('secretKey', NULL, NULL,
        _t('安全密钥'), _t('请在你的Google Code项目的管理员面板的source区的最下方找到此项'));
        $form->addInput($secretKey->addRule('required', _t('必须填写安全密钥')));
        
        $basePath = new Typecho_Widget_Helper_Form_Element_Text('basePath', NULL, '/trunk',
        _t('SVN目录'), _t('填写需要监控的SVN目录'));
        $form->addInput($basePath->addRule('required', _t('必须填写数据库用户名')));
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}
