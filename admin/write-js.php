<?php Typecho_Plugin::factory('admin/write-js.php')->write(); ?>
<?php Typecho_Widget::widget('Widget_Metas_Tag_Cloud', 'sort=count&desc=1&limit=200')->to($tags); ?>

<script src="<?php $options->adminUrl('js/timepicker.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminUrl('js/tokeninput.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminUrl('js/markdown.js?v=' . $suffixVersion); ?>"></script>
<script>
$(document).ready(function() {
    // 日期时间控件
    $('#date').mask('9999-99-99 99:99').datetimepicker({
        currentText     :   '<?php _e('现在'); ?>',
        prevText        :   '<?php _e('上一月'); ?>',
        nextText        :   '<?php _e('下一月'); ?>',
        monthNames      :   ['<?php _e('一月'); ?>', '<?php _e('二月'); ?>', '<?php _e('三月'); ?>', '<?php _e('四月'); ?>',
            '<?php _e('五月'); ?>', '<?php _e('六月'); ?>', '<?php _e('七月'); ?>', '<?php _e('八月'); ?>',
            '<?php _e('九月'); ?>', '<?php _e('十月'); ?>', '<?php _e('十一月'); ?>', '<?php _e('十二月'); ?>'],
        dayNames        :   ['<?php _e('星期日'); ?>', '<?php _e('星期一'); ?>', '<?php _e('星期二'); ?>',
            '<?php _e('星期三'); ?>', '<?php _e('星期四'); ?>', '<?php _e('星期五'); ?>', '<?php _e('星期六'); ?>'],
        dayNamesShort   :   ['<?php _e('周日'); ?>', '<?php _e('周一'); ?>', '<?php _e('周二'); ?>', '<?php _e('周三'); ?>',
            '<?php _e('周四'); ?>', '<?php _e('周五'); ?>', '<?php _e('周六'); ?>'],
        dayNamesMin     :   ['<?php _e('日'); ?>', '<?php _e('一'); ?>', '<?php _e('二'); ?>', '<?php _e('三'); ?>',
            '<?php _e('四'); ?>', '<?php _e('五'); ?>', '<?php _e('六'); ?>'],
        closeText       :   '<?php _e('完成'); ?>',
        timeOnlyTitle   :   '<?php _e('选择时间'); ?>',
        timeText        :   '<?php _e('时间'); ?>',
        hourText        :   '<?php _e('时'); ?>',
        amNames         :   ['<?php _e('上午'); ?>', 'A'],
        pmNames         :   ['<?php _e('下午'); ?>', 'P'],
        minuteText      :   '<?php _e('分'); ?>',
        secondText      :   '<?php _e('秒'); ?>',

        dateFormat      :   'yy-mm-dd',
        hour            :   (new Date()).getHours(),
        minute          :   (new Date()).getMinutes()
    });

    // tag autocomplete 提示
    var tags = $('#tags'), tagsPre = [];
    
    if (tags.length > 0) {
        var items = tags.val().split(','), result = [];
        for (var i = 0; i < items.length; i ++) {
            var tag = items[i];

            if (!tag) {
                continue;
            }

            tagsPre.push({
                id      :   tag,
                tags    :   tag
            });
        }

        tags.tokenInput(<?php 
        $data = array();
        while ($tags->next()) {
            $data[] = array(
                'id'    =>  $tags->name,
                'tags'  =>  $tags->name
            );
        }
        echo json_encode($data);
        ?>, {
            propertyToSearch:   'tags',
            tokenValue      :   'tags',
            searchDelay     :   0,
            preventDuplicates   :   true,
            animateDropdown :   false,
            hintText        :   '<?php _e('请输入标签名'); ?>',
            noResultsText   :   '此标签不存在, 按回车创建',
            prePopulate     :   tagsPre,

            onResult        :   function (result) {
                return result.slice(0, 5);
            }
        });

        // tag autocomplete 提示宽度设置
        $('#token-input-tags').focus(function() {
            var t = $('.token-input-dropdown'),
                offset = t.outerWidth() - t.width();
            t.width($('.token-input-list').outerWidth() - offset);
        });
    }

    // 缩略名自适应宽度
    var slug = $('#slug'), sw = slug.width();

    if (slug.val().length > 0) {
        slug.css('width', 'auto').attr('size', slug.val().length);
    }
    
    slug.bind('input propertychange', function () {
        var t = $(this), l = t.val().length;

        if (l > 0) {
            t.css('width', 'auto').attr('size', l);
        } else {
            t.css('width', sw).removeAttr('size');
        }
    }).width();

    // 自动保存
<?php if ($options->autoSave): ?>
    var savedData = null, locked = false,
        form = $('form[name=write_post],form[name=write_page]'),
        formAction = form.attr('action'),
        idInput = $('input[name=cid]'),
        autoSave = $('#auto-save-message'),
        autoSaveOnce = !!idInput.val(),
        lastSaveTime = null;

    function autoSaveListener () {
        setInterval(function () {
            var data = form.serialize();
                
            if (savedData != data) {
                savedData = data;
                locked = true;

                autoSave.text('<?php _e('正在保存'); ?>');
                $.post(formAction + '?do=save', data, function (o) {
                    lastSaveTime = o.time;
                    idInput.val(o.cid);
                    autoSave.text('<?php _e('内容已经保存'); ?>' + ' (' + o.time + ')').effect('highlight', 1000);
                    locked = false;
                });
            }
        }, 10000);
    }

    if (autoSaveOnce) {
        savedData = form.serialize();
        autoSaveListener();
    }

    $('#text').bind('input propertychange', function () {
        if (!locked) {
            autoSave.text('<?php _e('内容尚未保存'); ?>' + (lastSaveTime ? ' (<?php _e('上次保存时间'); ?>: ' + lastSaveTime + ')' : ''));
        }

        if (!autoSaveOnce) {
            autoSaveOnce = true;
            autoSaveListener();
        }
    });
<?php endif; ?>

    // 高级选项控制
    $('#advance-panel-btn').click(function() {
        $('#advance-panel').toggle();
        $(this).toggleClass('fold');
        return false;
    });
    
});
</script>
<?php $content = !empty($post) ? $post : $page; if ($options->markdown && (!$content->have() || $content->isMarkdown)): ?>
<script src="<?php $options->adminUrl('js/markdown.js?v=' . $suffixVersion); ?>"></script>
<script>
$(document).ready(function () {
    var toolbar = $('<div class="editor" id="wmd-button-bar" />').insertBefore($('#text').parent())
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

        help: '<?php _e('Markdown语法帮助'); ?>'
    };

    var editor = new Markdown.Editor(converter, '', options);
    editor.run();
});
</script>
<?php endif; ?>

