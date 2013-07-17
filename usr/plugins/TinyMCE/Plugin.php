<?php
/**
 * 集成tinyMCE编辑器
 * 
 * @package tinyMCE Editor 
 * @author qining
 * @version 1.0.1
 * @dependence 9.9.2-*
 * @link http://typecho.org
 */
class TinyMCE_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('TinyMCE_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('TinyMCE_Plugin', 'render');
        
        //去除段落
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('TinyMCE_Plugin', 'filter');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('TinyMCE_Plugin', 'filter');
        
        Helper::addPanel(0, 'TinyMCE/tiny_mce/langs.php','', '', 'contributor');
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
        Helper::removePanel(0, 'TinyMCE/tiny_mce/langs.php');
    }
    
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
     * 去除段落
     * 
     * @access public
     * @param array $post 数据结构体
     * @return array
     */
    public static function filter($post)
    {
        $post['text'] = Typecho_Common::removeParagraph($post['text']);
        return $post;
    }
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render($post)
    {
        $options = Helper::options();
        $js = Typecho_Common::url('TinyMCE/tiny_mce/tiny_mce.js', $options->pluginUrl);
        $langs = Typecho_Common::url('extending.php?panel=TinyMCE/tiny_mce/langs.php', $options->adminUrl);
        echo "<script type=\"text/javascript\" src=\"{$js}\"></script>
<script type=\"text/javascript\" src=\"{$langs}\"></script>
<script type=\"text/javascript\">
    var insertImageToEditor = function (title, url, link) {
        tinyMCE.activeEditor.execCommand('mceInsertContent', false,
        '<a href=\"' + link + '\" title=\"' + title + '\"><img src=\"' + url + '\" alt=\"' + title + '\" /></a>');
        new Fx.Scroll(window).toElement($(document).getElement('.mceEditor'));
    };
    
    var insertLinkToEditor = function (title, url, link) {
        tinyMCE.activeEditor.execCommand('mceInsertContent', false, '<a href=\"' + url + '\" title=\"' + title + '\">' + title + '</a>');
        new Fx.Scroll(window).toElement($(document).getElement('.mceEditor'));
    };

    //自动保存
    var autoSave;
    
    tinyMCE.init({
    // General options
    mode : 'exact',
    elements : 'text',
    theme : 'advanced',
    skin : 'typecho',
    plugins : 'safari,morebreak,inlinepopups,media,coder',
    extended_valid_elements : 'code[*],pre[*],script[*],iframe[*]',
    
    init_instance_callback : function(ed) {
        
        ed.setContent(\"" . str_replace(array("\n", "\r"), array("\\n", ""), addslashes($post->content)) . "\");
        "
        . ($options->autoSave ? 
        "autoSave = new Typecho.autoSave($('text').getParent('form').getProperty('action'), {
            time: 60,
            getContentHandle: tinyMCE.activeEditor.getContent.bind(ed),
            messageElement: 'auto-save-message',
            leaveMessage: '" . _t('您的内容尚未保存, 是否离开此页面?') . "',
            form: $('text').getParent('form')
        });" : "") .
        
        "
    },
    
    onchange_callback: function (inst) {
        if ('undefined' != typeof(autoSave)) {
            autoSave.onContentChange();
        }
    },
    
    save_callback: function (element_id, html, body) {
        if ('undefined' != typeof(autoSave)) {
            autoSave.saveRev = autoSave.rev;
        }
        
        return html;
    },
    
    // Theme options
    theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,blockquote,|,link,unlink,image,media,|,forecolor,backcolor,|,morebreak,code',
    theme_advanced_buttons2 : '',
    theme_advanced_buttons3 : '',
    theme_advanced_toolbar_location : 'top',
    theme_advanced_toolbar_align : 'left',
    convert_urls : false,
    language : 'typecho'
});
</script>";
    }
}
