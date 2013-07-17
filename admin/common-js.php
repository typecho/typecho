<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<script type="text/javascript" src="<?php $options->adminUrl('javascript/mootools.js?v=' . $suffixVersion); ?>"></script> 
<script type="text/javascript" src="<?php $options->adminUrl('javascript/typecho.js?v=' . $suffixVersion); ?>"></script>
<script type="text/javascript">
    (function () {
        window.addEvent('domready', function() {
            var _d = $(document);
            var handle = new Typecho.guid('typecho:guid', {offset: 1, type: 'mouse'});
            
            //增加高亮效果
            (function () {
                var _hlId = '<?php echo $notice->highlight; ?>';
                
                if (_hlId) {
                    var _hl = _d.getElement('#' + _hlId);
                    
                    if (_hl) {
                        _hl.set('tween', {duration: 1500});
            
                        var _bg = _hl.getStyle('background-color');
                        if (!_bg || 'transparent' == _bg) {
                            _bg = '#F7FBE9';
                        }

                        _hl.tween('background-color', '#AACB36', _bg);
                    }
                }
            })();

            //增加淡出效果
            (function () {
                var _msg = _d.getElement('.popup');
            
                if (_msg) {
                    (function () {

                        var _messageEffect = new Fx.Morph(this, {
                            duration: 'short', 
                            transition: Fx.Transitions.Sine.easeOut
                        });

                        _messageEffect.addEvent('complete', function () {
                            this.element.setStyle('display', 'none');
                        });

                        _messageEffect.start({'margin-top': [30, 0], 'height': [21, 0], 'opacity': [1, 0]});

                    }).delay(5000, _msg);
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
