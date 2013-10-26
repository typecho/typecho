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
        fullscreenUnsupport: '<?php _e('此浏览器不支持全屏操作'); ?>',

        imagedialog: '<p><b><?php _e('插入图片'); ?></b></p><p><?php _e('请在下方的输入框内输入要插入的远程图片地址'); ?></p><p><?php _e('您也可以使用编辑器下方的文件上传功能插入本地图片'); ?></p>',
        linkdialog: '<p><b><?php _e('插入链接'); ?></b></p><p><?php _e('请在下方的输入框内输入要插入的链接地址'); ?></p>',

        ok: '<?php _e('确定'); ?>',
        cancel: '<?php _e('取消'); ?>',

        help: '<?php _e('Markdown语法帮助'); ?>'
    };

    var editor = new Markdown.Editor(converter, '', options),
        diffMatch = new diff_match_patch(), last = '', preview = $('#wmd-preview'),
        mark = '@mark' + Math.ceil(Math.random() * 100000000) + '@',
        span = '<span class="diff" />';

    // 自动跟随
    converter.postConversion = function (html) {
        var diffs = diffMatch.diff_main(last, html);
        last = html;

        if (diffs.length > 0) {
            var stack = [], markStr = mark;
            
            for (var i = 0; i < diffs.length; i ++) {
                var diff = diffs[i], op = diff[0], str = diff[1]
                    sp = str.lastIndexOf('<'), ep = str.lastIndexOf('>');

                if (op != 0) {
                    if (sp >=0 && sp > ep) {
                        if (op > 0) {
                            stack.push(str.substring(0, sp) + markStr + str.substring(sp));
                        } else {
                            var lastStr = stack[stack.length - 1], lastSp = lastStr.lastIndexOf('<');
                            stack[stack.length - 1] = lastStr.substring(0, lastSp) + markStr + lastStr.substring(lastSp);
                        }
                    } else {
                        if (op > 0) {
                            stack.push(str + markStr);
                        } else {
                            stack.push(markStr);
                        }
                    }
                    
                    markStr = '';
                } else {
                    stack.push(str);
                }
            }

            html = stack.join('');

            if (!markStr) {
                var pos = html.indexOf(mark), prev = html.substring(0, pos),
                    next = html.substr(pos + mark.length),
                    sp = prev.lastIndexOf('<'), ep = prev.lastIndexOf('>');

                if (sp >= 0 && sp > ep) {
                    html = prev.substring(0, sp) + span + prev.substring(sp) + next;
                } else {
                    html = prev + span + next;
                }
            }
        }

        return html;
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
        textarea.css('height', window.screen.height - 46);
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

