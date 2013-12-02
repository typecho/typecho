<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $content = !empty($post) ? $post : $page; if ($options->markdown && (!$content->have() || $content->isMarkdown)): ?>
<script src="<?php $options->adminUrl('js/pagedown.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminUrl('js/pagedown-extra.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminUrl('js/diff.js?v=' . $suffixVersion); ?>"></script>
<script>
$(document).ready(function () {
    var textarea = $('#text'),
        toolbar = $('<div class="editor" id="wmd-button-bar" />').insertBefore(textarea.parent())
        preview = $('<div id="wmd-preview" class="wmd-hidetab" />').insertAfter('.editor');

    var options = {};

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
        more: '<?php _e('摘要分割线'); ?> <!--more--> Ctrl+M',

        undo: '<?php _e('撤销'); ?> - Ctrl+Z',
        redo: '<?php _e('重做'); ?> - Ctrl+Y',
        redomac: '<?php _e('重做'); ?> - Ctrl+Shift+Z',

        fullscreen: '<?php _e('全屏'); ?> - Ctrl+J',
        exitFullscreen: '<?php _e('退出全屏'); ?> - Ctrl+E',
        fullscreenUnsupport: '<?php _e('此浏览器不支持全屏操作'); ?>',

        imagedialog: '<p><b><?php _e('插入图片'); ?></b></p><p><?php _e('请在下方的输入框内输入要插入的远程图片地址'); ?></p><p><?php _e('您也可以使用编辑器下方的文件上传功能插入本地图片'); ?></p>',
        linkdialog: '<p><b><?php _e('插入链接'); ?></b></p><p><?php _e('请在下方的输入框内输入要插入的链接地址'); ?></p>',

        ok: '<?php _e('确定'); ?>',
        cancel: '<?php _e('取消'); ?>',

        help: '<?php _e('Markdown语法帮助'); ?>'
    };

    var converter = new Markdown.Converter(),
        editor = new Markdown.Editor(converter, '', options),
        diffMatch = new diff_match_patch(), last = '', preview = $('#wmd-preview'),
        mark = '@mark' + Math.ceil(Math.random() * 100000000) + '@',
        span = '<span class="diff" />';
    
    // 设置markdown
    Markdown.Extra.init(converter, {
        extensions  :   'all'
    });

    // 自动跟随
    converter.hooks.chain('postConversion', function (html) {
        // clear special html tags
        html = html.replace(/<\/?(\!doctype|html|head|body|link|title|input|select|button|textarea|style|noscript)[^>]*>/ig, function (all) {
            return all.replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/'/g, '&#039;')
                .replace(/"/g, '&quot;');
        });

        // clear hard breaks
        html = html.replace(/\s*((?:<br>\n)+)\s*(<\/?(?:p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|form|fieldset|iframe|hr|legend|article|section|nav|aside|hgroup|header|footer|figcaption|li|dd|dt)[^\w])/gm, '$2');

        if (html.indexOf('<!--more-->') > 0) {
            var parts = html.split(/\s*<\!\-\-more\-\->\s*/),
                summary = parts.shift(),
                details = parts.join('');

            html = '<div class="summary">' + summary + '</div>'
                + '<div class="details">' + details + '</div>';
        }


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
    });

    editor.hooks.chain('onPreviewRefresh', function () {
        var diff = $('.diff', preview), scrolled = false;

        $('img', preview).load(function () {
            if (scrolled) {
                preview.scrollTo(diff, {
                    offset  :   - 50
                });
            }
        });

        if (diff.length > 0) {
            var p = diff.position(), lh = diff.parent().css('line-height');
            lh = !!lh ? parseInt(lh) : 0;

            if (p.top < 0 || p.top > preview.height() - lh) {
                preview.scrollTo(diff, {
                    offset  :   - 50
                });
                scrolled = true;
            }
        }
    });

    var input = $('#text'), th = textarea.height(), ph = preview.height();

    editor.hooks.chain('enterFakeFullScreen', function () {
        th = textarea.height();
        ph = preview.height();
        $(document.body).addClass('fullscreen');
        var h = $(window).height() - toolbar.outerHeight();
        
        textarea.css('height', h);
        preview.css('height', h);
    });

    editor.hooks.chain('enterFullScreen', function () {
        $(document.body).addClass('fullscreen');
        
        var h = window.screen.height - toolbar.outerHeight();
        textarea.css('height', h);
        preview.css('height', h);
    });

    editor.hooks.chain('exitFullScreen', function () {
        $(document.body).removeClass('fullscreen');
        textarea.height(th);
        preview.height(ph);
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


    // 编辑预览切换
    var edittab = $('.editor').prepend('<div class="wmd-edittab"><a href="#wmd-editarea" class="active">撰写</a><a href="#wmd-preview">预览</a></div>'),
        editarea = $(textarea.parent()).attr("id", "wmd-editarea");

    $(".wmd-edittab a").click(function() {
        $(".wmd-edittab a").removeClass('active');
        $(this).addClass("active");
        $("#wmd-editarea, #wmd-preview").addClass("wmd-hidetab");
        
        var selected_tab = $(this).attr("href"),
            selected_el = $(selected_tab).removeClass("wmd-hidetab");

        // 预览时隐藏编辑器按钮
        if (selected_tab == "#wmd-preview") {
            $("#wmd-button-row").addClass("wmd-visualhide");
        } else {
            $("#wmd-button-row").removeClass("wmd-visualhide");
        }

        // 预览和编辑窗口高度一致
        $("#wmd-preview").outerHeight($("#wmd-editarea").innerHeight());

        return false;
    });
});
</script>
<?php endif; ?>

