<?php
/**
 * Set of plugins for HTML and CSS hi-speed coding
 * 
 * @package Zen Coding
 * @author qining
 * @version 1.0.0
 * @link http://code.google.com/p/zen-coding/
 */
class ZenCoding_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('ZenCoding_Plugin', 'writeBottom');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('ZenCoding_Plugin', 'writeBottom');
        Typecho_Plugin::factory('admin/theme-editor.php')->bottom = array('ZenCoding_Plugin', 'themeBottom');
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
    {}
    
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
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function themeBottom($files)
    {
        $options = Helper::options();
        $js = Typecho_Common::url('ZenCoding/zen_textarea.js', $options->pluginUrl);
        echo "<script type=\"text/javascript\" src=\"{$js}\"></script>
<script type=\"text/javascript\">
    $(document).getElement('#content').addClass('zc-use_tab-true zc-syntax-xsl zc-profile-xml');
    zen_textarea.setup({pretty_break: true});
</script>";
    }
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function writeBottom($post)
    {
        $options = Helper::options();
        $js = Typecho_Common::url('ZenCoding/zen_textarea.js', $options->pluginUrl);
        echo "<script type=\"text/javascript\" src=\"{$js}\"></script>
<script type=\"text/javascript\">
    $(document).getElement('#text').addClass('zc-use_tab-true zc-syntax-xsl zc-profile-xml');
    zen_textarea.setup({pretty_break: true});
</script>";
    }
}
