<?php Typecho_Plugin::factory('admin/write-js.php')->trigger($plugged)->editorPreview(); ?>
<?php if (!$plugged): ?>
<script type="text/javascript">
    var editorPreview = function () {
        var textarea = $('text'), old = '', preview = $('typecho-preview-box');

        var t = setInterval(function () {
            var current = textarea.get('value');
            
            if (old != current) {
                preview.set('html', Typecho.preview.autop(current));
                old = current;
            }
        }, 100);
    }
</script>
<?php endif; ?>


<script src="<?php $options->adminUrl('javascript/timepicker.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminUrl('javascript/tokeninput.js?v=' . $suffixVersion); ?>"></script>
<script>
$(document).ready(function() {
    // 日期时间控件
    $('#date').datetimepicker({
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
        closeText       :   '<?php _e('关闭'); ?>',
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
    $('#tags').tokenInput('http://shell.loopj.com/tokeninput/tvshows.php');
    // tag autocomplete 提示宽度设置
    $('#token-input-tags').focus(function() {
        $('.token-input-dropdown').width($('.token-input-list').css('width'));
    });

    // 高级选项控制
    $('#advance-panel-btn').click(function() {
        $('#advance-panel').toggle();
        return false;
    });
    
});
</script>

<script type="text/javascript">
    (function () {
        window.addEvent('domready', function() {
        
            $(document).getElements('.typecho-date').each(function (item) {
                item.setProperty('name', '_' + item.getProperty('name'));
                item.addEvent('change', function () {

                    $(document).getElements('.typecho-date').each(function (_item) {
                        var name = _item.name;

                        if (0 == name.indexOf('_')) {
                            _item.setProperty('name', name.slice(1));
                        }
                    });
                    
                });
                
                item.removeProperty('disabled');
            });
        
            /** 绑定按钮 */
            $(document).getElement('span.advance').addEvent('click', function () {
                Typecho.toggle('#advance-panel', this,
                '<?php _e('收起高级选项'); ?>', '<?php _e('展开高级选项'); ?>');
            });
            
            $(document).getElement('span.attach').addEvent('click', function () {
                Typecho.toggle('#upload-panel', this,
                '<?php _e('收起附件'); ?>', '<?php _e('展开附件'); ?>');
            });
            
            $('btn-save').removeProperty('disabled');
            $('btn-submit').removeProperty('disabled');
            
            $('btn-save').addEvent('click', function (e) {
                this.getParent('span').addClass('loading');
                this.setProperty('disabled', true);
                $(document).getElement('input[name=do]').set('value', 'save');
                $(document).getElement('.typecho-post-area form').submit();
            });
            
            $('btn-submit').addEvent('click', function (e) {
                this.getParent('span').addClass('loading');
                this.setProperty('disabled', true);
                $(document).getElement('input[name=do]').set('value', 'publish');
                $(document).getElement('.typecho-post-area form').submit();
            });

            if ('undefined' != typeof(editorPreview)) {
                //$(document).getElement('.typecho-preview-label').setStyle('display', 'block');

                function togglePreview(el) {
                    if (el.getProperty('checked')) {
                        $('typecho-preview-box').setStyle('display', 'block');
                    } else {
                        $('typecho-preview-box').setStyle('display', 'none');
                    }

                    return el;
                }

                togglePreview($('btn-preview')).addEvent('click', function (e) {
                    togglePreview(this);
                });

                editorPreview();
            }
        });
    })();
</script>
