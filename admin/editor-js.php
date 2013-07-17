<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<script type="text/javascript">
    var textEditor = new Typecho.textarea('#text', {
        autoSaveTime: 30,
        resizeAble: true,
        autoSave: <?php echo ($options->autoSave ? 'true' : 'false'); ?>,
        autoSaveMessageElement: 'auto-save-message',
        autoSaveLeaveMessage: '<?php _e('您的内容尚未保存, 是否离开此页面?'); ?>',
        resizeUrl: '<?php $options->index('/action/ajax'); ?>'
    });

    /** 这两个函数在插件中必须实现 */
    var insertImageToEditor = function (title, url, link, cid) {
        textEditor.setContent('<a href="' + link + '" title="' + title + '"><img src="' + url + '" alt="' + title + '" /></a>', '');
    };
    
    var insertLinkToEditor = function (title, url, link, cid) {
        textEditor.setContent('<a href="' + url + '" title="' + title + '">' + title + '</a>', '');
    };
</script>
