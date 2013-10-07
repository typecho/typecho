<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<script type="text/javascript" src="<?php $options->adminUrl('javascript/jquery.js?v=' . $suffixVersion); ?>"></script> 
<script type="text/javascript" src="<?php $options->adminUrl('javascript/jquery-ui.js?v=' . $suffixVersion); ?>"></script> 
<script type="text/javascript" src="<?php $options->adminUrl('javascript/typecho.js?v=' . $suffixVersion); ?>"></script>
<script type="text/javascript">
    (function () {
        $(document).ready(function() {
            var _d = $(document);
            
            //增加高亮效果
            <?php if ($notice->highlight): ?>                
            $('#<?php echo $notice->highlight; ?>').effect('highlight', '#AACB36', 1000);
            <?php endif; ?>

            //增加淡出效果
            (function () {
                var p = $('.popup');

                if (p.length > 0) {
                    if (p.hasClass('notice')) {
                        p.effect('bounce');
                    } else if (p.hasClass('error')) {
                        p.effect('shake');
                    } else {
                        p.slideDown();
                    }
                    
                    p.delay(5000).fadeOut();
                }
            })();
            
            //增加滚动效果,滚动到上面的一条error
            (function () {
                var _firstError = _d.getElement('.typecho-option .error');
    
                if (_firstError) {
                    var _errorFx = new Fx.Scroll(window).toElement(_firstError.getParent('.typecho-option'));
                }
            })();

            //禁用重复提交
            (function () {
                _d.getElements('input[type=submit]').removeProperty('disabled');
                _d.getElements('button[type=submit]').removeProperty('disabled');
    
                var _disable = function (e) {
                    e.stopPropagation();
                    
                    this.setProperty('disabled', true);
                    this.getParent('form').submit();
                    
                    return false;
                };

                _d.getElements('input[type=submit]').addEvent('click', _disable);
                _d.getElements('button[type=submit]').addEvent('click', _disable);
            })();

            //打开链接
            (function () {
                
                _d.getElements('a').each(function (item) {
                    var _href = item.href;
                    
                    if (_href && 0 != _href.indexOf('#')) {
                        //确认框
                        item.addEvent('click', function (event) {
                            var _lang = this.get('lang');
                            var _c = _lang ? confirm(_lang) : true;
                
                            if (!_c) {
                                event.stop();
                            }
                        });
        
                        /** 如果匹配则继续 */
                        if (/^<?php echo preg_quote($options->adminUrl, '/'); ?>.*$/.exec(_href) 
                            || /^<?php echo substr(preg_quote(Typecho_Common::url('s', $options->index), '/'), 0, -1); ?>action\/[_a-zA-Z0-9\/]+.*$/.exec(_href)) {
                            return;
                        }
            
                        item.set('target', '_blank');
                    }
                });
            })();
            
            Typecho.Table.init('.typecho-list-table');
            Typecho.Table.init('.typecho-list-notable');
        });
    })();
</script>
