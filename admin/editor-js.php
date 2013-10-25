<?php $content = !empty($post) ? $post : $page; if ($options->markdown && (!$content->have() || $content->isMarkdown)): ?>
<script src="<?php $options->adminUrl('js/markdown.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminUrl('js/diff.js?v=' . $suffixVersion); ?>"></script>
<script>
$(document).ready(function () {
    var textarea = $('#text'),
        toolbar = $('<div class="editor" id="wmd-button-bar" />').insertBefore(textarea.parent())
        preview = $('<div id="wmd-preview" />').insertAfter('.submit');

    var converter = new Showdown.converter(), options = {};

    options.strings = {
        bold: '<?php _e('加粗'); ?> <strong> Ctrl+B',
        boldexample: '<?php _e('加粗文字'); ?>',
            
        italic: '<?php _e('斜体'); ?> <em> Ctrl+I',
        italicexample: '<?php _e('斜体文字'); ?>',

        link: '<?php _e('链接'); ?> <a> Ctrl+L',
        linkdescription: '<?php _e('请输入链接描述'); ?>',

        quote:  '<?php _e('引用'); ?> <blockquote> Ctrl+Q',
        quoteexample: '<?php _e('引用文字'); ?>',

        code: '<?php _e('代码'); ?> <pre><code> Ctrl+K',
        codeexample: '<?php _e('请输入代码'); ?>',

        image: '<?php _e('图片'); ?> <img> Ctrl+G',
        imagedescription: '<?php _e('请输入图片描述'); ?>',

        olist: '<?php _e('数字列表'); ?> <ol> Ctrl+O',
        ulist: '<?php _e('普通列表'); ?> <ul> Ctrl+U',
        litem: '<?php _e('列表项目'); ?>',

        heading: '<?php _e('标题'); ?> <h1>/<h2> Ctrl+H',
        headingexample: '<?php _e('标题文字'); ?>',

        hr: '<?php _e('分割线'); ?> <hr> Ctrl+R',

        undo: '<?php _e('撤销'); ?> - Ctrl+Z',
        redo: '<?php _e('重做'); ?> - Ctrl+Y',
        redomac: '<?php _e('重做'); ?> - Ctrl+Shift+Z',

        fullscreen: '<?php _e('全屏'); ?> - Ctrl+M',
        exitFullscreen: '<?php _e('退出全屏'); ?> - Ctrl+M',

        imagedialog: '<p><b><?php _e('插入图片'); ?></b></p><p><?php _e('请在下方的输入框内输入要插入的远程图片地址'); ?></p><p><?php _e('您也可以使用编辑器下方的文件上传功能插入本地图片'); ?></p>',
        linkdialog: '<p><b><?php _e('插入链接'); ?></b></p><p><?php _e('请在下方的输入框内输入要插入的链接地址'); ?></p>',

        ok: '<?php _e('确定'); ?>',
        cancel: '<?php _e('取消'); ?>',

        help: '<?php _e('Markdown语法帮助'); ?>'
    };

    var editor = new Markdown.Editor(converter, '', options),
        diffMatch = new diff_match_patch(), last = '', preview = $('#wmd-preview'),
        boundary = '@boundary' + Math.ceil(Math.random() * 1000000) + '@';

    // 自动跟随
    converter.preConversion = function (text) {
        var diffs = diffMatch.diff_main(last, text);
        last = text;


        if (diffs.length > 0) {
            text = '';

            for (var i = 0; i < diffs.length; i ++) {
                var diff = diffs[i];

                if (diff[0] >= 0) {
                    text += diff[1];
                }

                if (diff[0] != 0) {
                    text += (diff[1].substring(-1).match(/\w\u3300-\u33ff\u3400-\u4d8f\u4e00-\u9fff/i) ? ' ' : '') + boundary;
                }
            }
        }

        return text;
    }

    converter.postConversion = function (html) {
        html = html.replace(boundary, '<span class="diff" />');
        return html.replace(new RegExp(boundary, 'g'), '');
    }

    editor.hooks.chain('onPreviewRefresh', function () {
        var diff = $('.diff', preview);

        if (diff.length > 0) {
            var p = diff.position();

            if (p.top < 0 || p.top > preview.height()) {
                preview.scrollTo(diff, {
                    offset  :   - 50
                });
            }
        }
    });

    var input = $('#text'), th = textarea.height();

    editor.hooks.chain('enterFullScreen', function () {
        th = textarea.height();
        $(document.body).addClass('fullscreen');
        $('#wmd-fullscreen-button span').css('background-position', '-240px -20px');
        textarea.height(window.screen.height - 38);
    });

    editor.hooks.chain('exitFullScreen', function () {
        $(document.body).removeClass('fullscreen');
        textarea.height(th);
    });

    editor.run();

    var imageButton = $('#wmd-image-button'),
        linkButton = $('#wmd-link-button');

    Typecho.insertFileToEditor = function (file, url, isImage) {
        var button = isImage ? imageButton : linkButton;

        options.strings[isImage ? 'imagename' : 'linkname'] = file;
        button.trigger('click');

        var checkDialog = setInterval(function () {
            if ($('.wmd-prompt-dialog').length > 0) {
                $('.wmd-prompt-dialog input').val(url).select();
                clearInterval(checkDialog);
                checkDialog = null;
            }
        }, 10);
    };
});
</script>
<?php endif; ?>

