<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php Typecho_Plugin::factory('admin/write-js.php')->write(); ?>
<?php Typecho_Widget::widget('Widget_Metas_Tag_Cloud', 'sort=count&desc=1&limit=200')->to($tags); ?>

<script src="<?php $options->adminStaticUrl('js', 'timepicker.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'tokeninput.js?v=' . $suffixVersion); ?>"></script>
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

    // 聚焦
    $('#title').select();

    // text 自动拉伸
    Typecho.editorResize('text', '<?php $security->index('/action/ajax?do=editorResize'); ?>');

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
        echo Json::encode($data);
        ?>, {
            propertyToSearch:   'tags',
            tokenValue      :   'tags',
            searchDelay     :   0,
            preventDuplicates   :   true,
            animateDropdown :   false,
            hintText        :   '<?php _e('请输入标签名'); ?>',
            noResultsText   :   '<?php _e('此标签不存在, 按回车创建'); ?>',
            prePopulate     :   tagsPre,

            onResult        :   function (result, query) {
                if (!query) {
                    return result;
                }

                if (!result) {
                    result = [];
                }

                if (!result[0] || result[0]['id'] != query) {
                    result.unshift({
                        id      :   query,
                        tags    :   query
                    });
                }

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
    var slug = $('#slug');

    if (slug.length > 0) {
        var wrap = $('<div />').css({
            'position'  :   'relative',
            'display'   :   'inline-block'
        }),
        justifySlug = $('<pre />').css({
            'display'   :   'block',
            'visibility':   'hidden',
            'height'    :   slug.height(),
            'padding'   :   '0 2px',
            'margin'    :   0
        }).insertAfter(slug.wrap(wrap).css({
            'left'      :   0,
            'top'       :   0,
            'minWidth'  :   '5px',
            'position'  :   'absolute',
            'width'     :   '100%'
        })), originalWidth = slug.width();

        function justifySlugWidth() {
            var val = slug.val();
            justifySlug.text(val.length > 0 ? val : '     ');
        }

        slug.bind('input propertychange', justifySlugWidth);
        justifySlugWidth();
    }

    // 原始的插入图片和文件
    Typecho.insertFileToEditor = function (file, url, isImage) {
        var textarea = $('#text'), sel = textarea.getSelection(),
            html = isImage ? '<img src="' + url + '" alt="' + file + '" />'
                : '<a href="' + url + '">' + file + '</a>',
            offset = (sel ? sel.start : 0) + html.length;

        textarea.replaceSelection(html);
        textarea.setSelection(offset, offset);
    };

    var submitted = false, form = $('form[name=write_post],form[name=write_page]').submit(function () {
        submitted = true;
    }), savedData = null;

    // 自动保存
<?php if ($options->autoSave): ?>
    var locked = false,
        formAction = form.attr('action'),
        idInput = $('input[name=cid]'),
        cid = idInput.val(),
        autoSave = $('<span id="auto-save-message" class="left"></span>').prependTo('.submit'),
        autoSaveOnce = !!cid,
        lastSaveTime = null;

    function autoSaveListener () {
        setInterval(function () {
            idInput.val(cid);
            var data = form.serialize();
                
            if (savedData != data && !locked) {
                locked = true;

                autoSave.text('<?php _e('正在保存'); ?>');
                $.post(formAction, data + '&do=save', function (o) {
                    savedData = data;
                    lastSaveTime = o.time;
                    cid = o.cid;
                    autoSave.text('<?php _e('已保存'); ?>' + ' (' + o.time + ')').effect('highlight', 1000);
                    locked = false;
                }, 'json');
            }
        }, 10000);
    }

    if (autoSaveOnce) {
        savedData = form.serialize();
        autoSaveListener();
    }

    $('#text').bind('input propertychange', function () {
        if (!locked) {
            autoSave.text('<?php _e('尚未保存'); ?>' + (lastSaveTime ? ' (<?php _e('上次保存时间'); ?>: ' + lastSaveTime + ')' : ''));
        }

        if (!autoSaveOnce) {
            autoSaveOnce = true;
            autoSaveListener();
        }
    });
<?php endif; ?>

    // 自动检测离开页
    var lastData = form.serialize();

    $(window).bind('beforeunload', function () {
        if (!!savedData) {
            lastData = savedData;
        }

        if (form.serialize() != lastData && !submitted) {
            return '<?php _e('内容已经改变尚未保存, 您确认要离开此页面吗?'); ?>';
        }
    });

    // 控制选项和附件的切换
    var fileUploadInit = false;
    $('#edit-secondary .typecho-option-tabs li').click(function() {
        $('#edit-secondary .typecho-option-tabs li').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').addClass('hidden');
        
        var selected_tab = $(this).find('a').attr('href'),
            selected_el = $(selected_tab).removeClass('hidden');

        if (!fileUploadInit) {
            selected_el.trigger('init');
            fileUploadInit = true;
        }

        return false;
    });

    // 高级选项控制
    $('#advance-panel-btn').click(function() {
        $('#advance-panel').toggle();
        return false;
    });

    // 自动隐藏密码框
    $('#visibility').change(function () {
        var val = $(this).val(), password = $('#post-password');
        console.log(val);

        if ('password' == val) {
            password.removeClass('hidden');
        } else {
            password.addClass('hidden');
        }
    });
    
    // 草稿删除确认
    $('.edit-draft-notice a').click(function () {
        if (confirm('<?php _e('您确认要删除这份草稿吗?'); ?>')) {
            window.location.href = $(this).attr('href');
        }

        return false;
    });
});
</script>

