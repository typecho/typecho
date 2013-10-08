(function ($) {
    // 下拉菜单插件
    $.fn.dropdownMenu = function (options) {
        this.each(function () {
            var menu = this, s = $.extend({
                menuEl      :   null,
                btnEl       :   null
            }, options);

            $(s.btnEl, menu).click(function () {
                var t = $(this);

                t.toggleClass('active');
                $(s.menuEl, menu).toggle();
                return false;
            });
        });
    };

    // 表格选择插件
    $.fn.tableSelectable = function (options) {
        var table = this, s = $.extend({
            checkEl     :   null,
            rowEl       :   null,
            selectAllEl :   null,
            actionEl    :   null
        }, options);

        function clickRow (t) {
            var t = $(t), check = $(s.checkEl, t), checked = check.prop('checked');
            check.prop('checked', !checked);
            
            if (checked) {
                t.removeClass('checked');
            } else {
                t.addClass('checked');
            }
        }

        $(s.rowEl, this).each(function () {
            $(s.checkEl, this).click(function (e) {
                clickRow($(this).parents(s.rowEl));
            });

            $('input[type=text],input[type=password],textarea,input[type=submit],input[type=button],a,button').click(function (e) {
                e.stopPropagation();
            });
        }).click(function () {
            clickRow(this);
        });

        $(s.selectAllEl).click(function () {
            var t = $(this), checked = t.prop('checked');
            
            if (checked) {
                $(s.rowEl, table).each(function () {
                    $(s.checkEl, this).prop('checked', true);
                }).addClass('checked');
            } else {
                $(s.rowEl, table).each(function () {
                    $(s.checkEl, this).prop('checked', false);
                }).removeClass('checked');
            }
        });

        $(s.actionEl).click(function () {
            var t = $(this), lang = t.attr('lang');

            if (!lang || confirm(lang)) {
                table.parents('form').attr('action', t.attr('href')).submit();
            }

            return false;
        });
    };
})($);


/** 初始化全局对象 */
var Typecho = {};

Typecho.Table = {
    
    table: null,        //当前表格
    
    draggable: false,    //是否可拖拽
    
    draggedEl: null,    //当前拖拽的元素
    
    draggedFired: false,    //是否触发

    init: function (match) {
        /** 初始化表格风格 */
        $(document).getElements(match).each(function (item) {
            Typecho.Table.table = item;
            Typecho.Table.draggable = item.hasClass('draggable');
            Typecho.Table.bindButtons();
            Typecho.Table.reset();
        });
    },
    
    reset: function () {
        var _el = Typecho.Table.table;
        Typecho.Table.draggedEl = null;
        
        if ('undefined' == typeof(_el._childTag)) {
            switch (_el.get('tag')) {
                case 'ul':
                    _el._childTag = 'li';
                    break;
                case 'table':
                    _el._childTag = 'tr';
                    break;
                default:
                    break;
            }
            
            var _cb = _el.getElements(_el._childTag + ' input[type=checkbox]').each(function (item) {
                item._parent = item.getParent(Typecho.Table.table._childTag);
               
                /** 监听click事件 */
                item.addEvent('click', Typecho.Table.checkBoxClick);
            });
        }
    
        /** 如果有even */
        var _hasEven = _el.getElements(_el._childTag + '.even').length > 0;
        
        _el.getElements(_el._childTag).filter(function (item, index) {
            /** 把th干掉 */
            return 'tr' != item.get('tag') || 0 == item.getChildren('th').length;
        }).each(function (item, index) {
            if (_hasEven) {
                /** 处理已经选择的选项 */
                if (index % 2) {
                    item.removeClass('even');
                } else {
                    item.addClass('even');
                }
                
                if (item.hasClass('checked') || item.hasClass('checked-even')) {
                    item.removeClass(index % 2 ? 'checked-even' : 'checked')
                    .addClass(index % 2 ? 'checked' : 'checked-even');
                }
            }
            
            Typecho.Table.bindEvents(item);
        });
    },
    
    checkBoxClick: function (event) {
        var _el = $(this);
        if (_el.getProperty('checked')) {
            _el.setProperty('checked', false);
            _el._parent.removeClass(_el._parent.hasClass('even') ? 'checked-even' : 'checked');
            Typecho.Table.unchecked(this, _el._parent);
        } else {
            _el.setProperty('checked', true);
            _el._parent.addClass(_el._parent.hasClass('even') ? 'checked-even' : 'checked');
            Typecho.Table.checked(this, _el._parent);
        }
    },
    
    itemMouseOver: function (event) {
        if(!Typecho.Table.draggedEl || Typecho.Table.draggedEl == this) {
            $(this).addClass('hover');
            
            //fix ie
            if (Browser.Engine.trident) {
                $(this).getElements('.hidden-by-mouse').setStyle('display', 'inline');
            }
        }
    },
    
    itemMouseLeave: function (event) {
        if(!Typecho.Table.draggedEl || Typecho.Table.draggedEl == this) {
            $(this).removeClass('hover');
            
            //fix ie
            if (Browser.Engine.trident) {
                $(this).getElements('.hidden-by-mouse').setStyle('display', 'none');
            }
        }
    },
    
    itemClick: function (event) {
        /** 触发多选框点击事件 */
        if ('undefined' != typeof(event)) {
            var _el = $(this).getElement('input[type=checkbox]'), _t = $(event.target);
            
            if (_el && ('a' != _t.get('tag')
            && ('input' != _t.get('tag') || ('text' != _t.get('type') && 'button' != _t.get('type') && 'submit' != _t.get('type')))
            && 'textarea' != _t.get('tag')
            && 'label' != _t.get('tag')
            && 'img'   != _t.get('tag')
            && 'button' != _t.get('tag'))) {
                _el.fireEvent('click');
            }
        }
    },
    
    itemMouseDown: function (event) {
        if (!Typecho.Table.draggedEl) {
            Typecho.Table.draggedEl = this;
            Typecho.Table.draggedFired = false;
            return false;
        }
    },
    
    itemMouseMove: function (event) {
        if (Typecho.Table.draggedEl) {
        
            if (!Typecho.Table.draggedFired) {
                Typecho.Table.dragStart(this);
                $(this).setStyle('cursor', 'move');
                Typecho.Table.draggedFired = true;
            }
            
            if (Typecho.Table.draggedEl != this) {
                /** 从下面进来的 */
                if ($(this).getCoordinates(Typecho.Table.draggedEl).top < 0) {
                    $(this).inject(Typecho.Table.draggedEl, 'after');
                } else {
                    $(this).inject(Typecho.Table.draggedEl, 'before');
                }
                
                if ($(this).hasClass('even')) {
                    if (!$(Typecho.Table.draggedEl).hasClass('even')) {
                        $(this).removeClass('even');
                        $(Typecho.Table.draggedEl).addClass('even');
                    }
                    
                    if ($(this).hasClass('checked-even') && 
                    !$(Typecho.Table.draggedEl).hasClass('checked-even')) {
                        $(this).removeClass('checked-even');
                        $(Typecho.Table.draggedEl).addClass('checked-even');
                    }
                } else {
                    if ($(Typecho.Table.draggedEl).hasClass('even')) {
                        $(this).addClass('even');
                        $(Typecho.Table.draggedEl).removeClass('even');
                    }
                    
                    if ($(this).hasClass('checked') && 
                    $(Typecho.Table.draggedEl).hasClass('checked')) {
                        $(this).removeClass('checked');
                        $(Typecho.Table.draggedEl).addClass('checked');
                    }
                }
                
                return false;
            }
        }
    },
    
    itemMouseUp: function (event) {
        if (Typecho.Table.draggedEl) {
            var _inputs = Typecho.Table.table.getElements(Typecho.Table.table._childTag + ' input[type=checkbox]');
            var result = '';
            
            for (var i = 0; i< _inputs.length; i ++) {
                if (result.length > 0) result += '&';
                result += _inputs[i].name + '=' + _inputs[i].value;
            }
            
            if (Typecho.Table.draggedFired) {    
                $(this).fireEvent('click');
                $(this).setStyle('cursor', '');
                Typecho.Table.dragStop(this, result);
                Typecho.Table.draggedFired = false;
                Typecho.Table.reset();
            }
            
            Typecho.Table.draggedEl = null;
            return false;
        }
    },
    
    checked:   function (input, item) {return false;},
    
    unchecked: function (input, item) {return false;},
    
    dragStart: function (item) {return false;},
    
    dragStop: function (item, result) {return false;},
    
    bindButtons: function () {
        /** 全选按钮 */
        $(document).getElements('.typecho-table-select-all')
        .addEvent('click', function () {
            Typecho.Table.table.getElements(Typecho.Table.table._childTag + ' input[type=checkbox]')
            .each(function (item) {
                if (!item.getProperty('checked')) {
                    item.fireEvent('click');
                }
            });
        });
        
        /** 不选按钮 */
        $(document).getElements('.typecho-table-select-none')
        .addEvent('click', function () {
            Typecho.Table.table.getElements(Typecho.Table.table._childTag + ' input[type=checkbox]')
            .each(function (item) {
                if (item.getProperty('checked')) {
                    item.fireEvent('click');
                }
            });
        });
        
        /** 提交按钮 */
        $(document).getElements('.typecho-table-select-submit')
        .addEvent('click', function () {
            var _lang = this.get('lang');
            var _c = _lang ? confirm(_lang) : true;
            
            if (_c) {
                var _f = Typecho.Table.table.getParent('form');
                _f.getElement('input[name=do]').set('value', $(this).getProperty('rel'));
                _f.submit();
            }
        });
    },
    
    bindEvents: function (item) {
        item.removeEvents();

        item.addEvents({
            'mouseover': Typecho.Table.itemMouseOver,
            'mouseleave': Typecho.Table.itemMouseLeave,
            'click': Typecho.Table.itemClick
        });

        if (Typecho.Table.draggable && 
        Typecho.Table.table.getElements(Typecho.Table.table._childTag + ' input[type=checkbox]').length > 0) {
            item.addEvents({
                'mousedown': Typecho.Table.itemMouseDown,
                'mousemove': Typecho.Table.itemMouseMove,
                'mouseup': Typecho.Table.itemMouseUp
            });
        }
    }
};

Typecho.toggleEl = null;
Typecho.toggleBtn = null;
Typecho.toggleHideWord = null;
Typecho.toggleOpened = false;

Typecho.toggle = function (sel, btn, showWord, hideWord) {
    var el = $(document).getElement(sel);
    
    if (null != Typecho.toggleBtn && btn != Typecho.toggleBtn) {
        $(Typecho.toggleBtn).set('html', Typecho.toggleHideWord);
        Typecho.toggleEl.setStyle('display', 'none');
        Typecho.toggleEl.fireEvent('tabHide');
        $(Typecho.toggleBtn).toggleClass('close');
    }
    
    $(btn).toggleClass('close');
    if ('none' == el.getStyle('display')) {
        $(btn).set('html', showWord);
        el.setStyle('display', 'block');
        el.fireEvent('tabShow');
        Typecho.toggleOpened = true;
    } else {
        $(btn).set('html', hideWord);
        el.setStyle('display', 'none');
        el.fireEvent('tabHide');
        Typecho.toggleOpened = false;
    }
    
    Typecho.toggleEl = el;
    Typecho.toggleBtn = btn;
    Typecho.toggleHideWord = hideWord;
};

/** 自动保存组件 */
/*
Typecho.autoSave = new Class({

    //继承自Options
    Implements: [Options],
    
    //内部选项
    options: {
        time: 10,   //间隔
        getContentHandle: null, //获取内容函数
        messageElement: null,
        leaveMessage: 'leave?',
        form: null
    },

    initialize: function (url, options) {
        this.setOptions(options);
        this.duration = 0;
        this.start = false;
        this.url = url;
        this.rev = 0;
        this.saveRev = 0;
        
        window.onbeforeunload = this.leaveListener.bind(this);
        $(this.options.form).getElements('.submit button').addEvent('mousedown', (function () {
            this.saveRev = this.rev;
        }).bind(this));
        
        //时间间隔计数器
        (function () {
            if (this.start) {
                this.duration ++;
            }
            
            if (this.duration > this.options.time) {
                this.start = false;
                this.onContentChange();
            }
        }).periodical(1000, this);
    },
    
    //离开页面监听器
    leaveListener: function () {
        if (this.saveRev != this.rev) {
            return this.options.leaveMessage;
        }
    },
    
    //内容改变监听器
    onContentChange: function () {
        this.start = true;
        this.rev ++;
        
        if (this.duration > this.options.time) {
        
            var o = {text: this.options.getContentHandle()};
            this.start = false;
            this.duration = 0;
            this.saveText = o.text;
            this.saveRev = this.rev;
            $(this.options.form).getElement('input[name=do]').set('value', 'save');
        
            new Request.JSON({
                url: this.url,
                
                onSuccess: (function (responseJSON) {
                    if (responseJSON.success) {
                        $(this.options.form).getElement('input[name=cid]').set('value', responseJSON.cid);
                    }
                    
                    if (null != this.options.messageElement) {
                        $(this.options.messageElement).set('html', responseJSON.message);
                        $(this.options.messageElement).highlight('#ff0000');
                    }
                    
                }).bind(this)
            }).send($(this.options.form).toQueryString() + '&' + Hash.toQueryString(o));
        }
    }
});
*/

/** 文本编辑器插入文字 */
/*
Typecho.textarea = new Class({

    //继承自Options
    Implements: [Options],

    //内部选项
    options: {
        resizeAble: false,  //能否调整大小
        resizeClass: 'size-btn',    //调整大小的class名
        resizeUrl: '',  //调整大小后的请求地址
        autoSave: false,
        autoSaveMessageElement: null,
        autoSaveLeaveMessage: 'leave?',
        autoSaveTime: 60,
        minSize: 30
    },

    initialize: function (el, options) {
        this.textarea = $(document).getElement(el);
        this.range = null;
        this.setOptions(options);
        
        if (this.options.autoSave) {
            this.autoSave = new Typecho.autoSave(this.textarea.getParent('form').getProperty('action'), {
                time: this.options.autoSaveTime,
                getContentHandle: this.getContent.bind(this),
                messageElement: this.options.autoSaveMessageElement,
                leaveMessage: this.options.autoSaveLeaveMessage,
                form: this.textarea.getParent('form')
            });
        }
        
        var recordRangeCallback = this.recordRange.bind(this);
        
        this.textarea.addEvents({
            mouseup: recordRangeCallback,
            keyup: (function () {
                recordRangeCallback();
                if (this.options.autoSave) {
                    this.autoSave.onContentChange();
                }
            }).bind(this)
        });

        if (this.options.resizeAble) {
            this.makeResizeAble();
        }
    },
    
    //记录当前位置
    recordRange: function () {
        this.range = this.textarea.getSelectedRange();
    },
    
    //设置当前编辑域为可调整大小
    makeResizeAble: function () {
        this.resizeOffset = this.textarea.getStyle('height') ? 
        this.textarea.getSize().y - parseInt(this.textarea.getStyle('height')) : 0;
        this.resizeMouseY = 0;
        this.lastMouseY = 0;
        
        //是否在调整区域按下鼠标
        this.isResizePressed = false;
        
        //创建调整区
        var cross = new Element('span', {
            
            'class': this.options.resizeClass,
            
            'events': {
                mousedown: this.resizeMouseDown.bind(this)
            }
        }).inject(this.textarea, 'after');
        
        //截获事件
        $(document).addEvents({
            mouseup: this.resizeMouseUp.bind(this),
            mousemove: this.resizeMouseMove.bind(this)
        });
        
        //监听事件
        this.resizeListener.periodical(10, this);
    },
    
    //监听调整区
    resizeListener: function () {
        if (this.isResizePressed) {
            var resize = (0 == this.lastMouseY) ? 0 : this.resizeMouseY - this.lastMouseY;
            this.lastMouseY = this.resizeMouseY;
            
            var finalY = this.textarea.getSize().y - this.resizeOffset + resize;
            
            if (finalY > this.options.minSize) {
                this.textarea.setStyle('height', finalY);
            }
        }
    },
    
    //按下调整区
    resizeMouseDown: function (e) {
        this.isResizePressed = true;
        e.stop();
    },
    
    //松开调整区
    resizeMouseUp: function (e) {
        if (this.isResizePressed) {
            this.isResizePressed = false;
            
            var size = this.textarea.getSize().y - this.resizeOffset;
            
            //发送ajax请求
            new Request({
                'method': 'post',
                'url': this.options.resizeUrl
            }).send('size=' + size + '&do=editorResize');
            
            this.resizeMouseY = 0;
            this.lastMouseY = 0;
        }
    },
    
    //移动调整区
    resizeMouseMove: function (e) {
        if (this.isResizePressed) {
            this.resizeMouseY = e.page.y;
        }
    },
    
    //获取内容
    getContent: function () {
        return this.textarea.get('value');
    },
    
    //设置当前选定的内容
    setContent: function (before, after) {
        var range = (null == this.range) ? this.textarea.getSelectedRange() : this.range,
        text = this.textarea.get('value'),
        selectedText = text.substr(range.start, range.end - range.start),
        scrollTop = this.textarea.scrollTop;
        
        //alert(textarea.selectionStart);
        
        this.textarea.set('value', text.substr(0, range.start) + before + selectedText
        + after + text.substr(range.end));
        
        (function () {
            this.textarea.scrollTop = scrollTop;
        }).bind(this).delay(0);

        this.textarea.focus();
        this.textarea.selectRange(range.start, range.end + before.length + after.length);
    }
});
*/

/** 自动完成 */
Typecho.autoComplete = function (match, token) {
    var _sp = ',', _index, _cur = -1, _hoverList = false, _remember = 0,
    _el = $(document).getElement(match).setProperty('autocomplete', 'off');
    
    //创建搜索索引
    var _build = function () {
        var _len = 0, _val = _el.get('value');
        _index = [];
        
        if (_val.length > 0) {
            _val.split(_sp).each(function (item, index) {
                var _final = _len + item.length,
                _l = 0, _r = 0;
                
                item = item.replace(/(\s*)(.*)(\s*)/, function (v, a, b, c) {
                    _l = a.length;
                    _r = c.length;
                    return b;
                });
            
                _index[index] = {
                    txt: item,
                    start: index*1 + _len,
                    end: index*1 + _final,
                    offsetStart: index*1 + _len + _l,
                    offsetEnd: index*1 + _final - _r
                };
                
                _len = _final;
            });
        }
    };
    
    //获取当前keyword
    var _keyword = function (s, pos) {
        return pos ? pos.txt.substr(0, s - pos.offsetStart) : '';
    };
    
    //搜索token
    var _match = function (keyword) {
        var matchCase = keyword.length > 0 ? token.filter(function (item) {
            return 0 == item.indexOf(keyword);
        }) : [];
        
        var matchOther = keyword.length > 0 ? token.filter(function (item) {
            return (0 == item.toLowerCase().indexOf(keyword.toLowerCase()) && !matchCase.contains(item));
        }) : []; 
        
        return matchCase.extend(matchOther);
    };
    
    //选择特定元素
    var _select = function (s, pos) {
        _el.selectRange(pos.offsetStart > s ? pos.offsetStart : s, pos.offsetEnd);
    };
    
    //定位
    var _location = function (s) {
        for (var i in _index) {
            if (s >= _index[i].start && s <= _index[i].end) {
                return _index[i];
            }
        }
        
        return false;
    };
    
    //替换
    var _replace = function (w, s, e) {
        var _val = _el.get('value');
        return _el.set('value', _val.substr(0, s) + w + _val.substr(e));
    };
    
    //显示
    var _show = function (key, list) {
        _cur = -1;
        _hoverList = false;
    
        var _ul = new Element('ul', {
            'class': 'autocompleter-choices',
            'styles': {
                'width': _el.getSize().x - 2,
                'left': _el.getPosition().x,
                'top': _el.getPosition().y + _el.getSize().y
            }
        });
        
        list.each(function (item, index) {
        
            _ul.grab(new Element('li', {
                'rel': index,
                'html': '<span class="autocompleter-queried">' + item.substr(0, key.length)
                    + '</span>' + item.substr(key.length),
                'events': {
                    
                    'mouseover': function () {
                        _hoverList = true;
                        this.addClass('autocompleter-hover');
                    },
                    
                    'mouseleave': function () {
                        _hoverList = false;
                        this.removeClass('autocompleter-hover');
                    },
                    
                    'click': function () {
                        var _i = parseInt(this.get('rel'));
                        var _start = _remember > 0 ? _remember : _el.getSelectedRange().start,
                        _pos = _location(_start);

                        _replace(list[_i], _pos.offsetStart, _pos.offsetEnd);
                        _build();
                        
                        _pos = _location(_start);
                        _el.selectRange(_pos.offsetEnd, _pos.offsetEnd);
                        _hide();
                    }
                }
            }));
        });
        
       $(document).getElement('body').grab(_ul);
    };
    
    var _hide = function () {
        var _e = $(document).getElement('.autocompleter-choices');
        
        if (_e) {
            _e.destroy();
            _hoverList = false;
        }
    };
    
    _build();
    
    var _k, _l;
    
    //绑定事件
    _el.addEvents({
        
        'mouseup': function (e) {
            var _start = _el.getSelectedRange().start,
            _pos = _location(_start);
            _hide();
            _select(_start, _pos);
            this.fireEvent('keyup', e);
            
            _remember = _el.getSelectedRange().end;
            
            e.stop();
            return false;
        },
        
        'blur': function () {            
            if (!_hoverList) {
                _hide();
            }
        },
        
        'keydown': function (e) {
            _build();
            var _start = _el.getSelectedRange().start,
            _pos = _location(_start);
            
            _remember = _el.getSelectedRange().end;
            
            switch (e.key) {
                case 'up':
                
                    if (_l.length > 0 && _cur >= 0) {
                        if (_cur < _l.length) {
                            $(document).getElement('.autocompleter-choices li[rel=' + _cur + ']').removeClass('autocompleter-selected');
                        }

                        if (_cur > 0) {
                            _cur --;
                        } else {
                            _cur = _l.length - 1;
                        }
                        
                        $(document).getElement('.autocompleter-choices li[rel=' + _cur + ']').addClass('autocompleter-selected');
                        _replace(_l[_cur], _pos.offsetStart, _pos.offsetEnd);
                        _build();

                        _pos = _location(_start);
                        _select(_start, _pos);
                    }
                    
                    e.stop();
                    return false;
                
                case 'down':

                    if (_l.length > 0 && _cur < _l.length) {
                        if (_cur >= 0) {
                            $(document).getElement('.autocompleter-choices li[rel=' + _cur + ']').removeClass('autocompleter-selected');
                        }
                    
                        if (_cur < _l.length - 1) {
                            _cur ++;
                        } else {
                            _cur = 0;
                        }
                        
                        $(document).getElement('.autocompleter-choices li[rel=' + _cur + ']').addClass('autocompleter-selected');
                        _replace(_l[_cur], _pos.offsetStart, _pos.offsetEnd);
                        _build();

                        _pos = _location(_start);
                        _select(_start, _pos);
                    }
                    
                    e.stop();
                    return false;
                    
                case 'enter':
                    _hide();
                    _el.selectRange(_pos.offsetEnd, _pos.offsetEnd);
                    e.stop();
                    return false;
                    
                default:
                    break;
            }
        },
        
        'keyup': function (e) {
        
            _build();
            var _start = _el.getSelectedRange().start,
            _pos = _location(_start);
            
            _remember = _el.getSelectedRange().end;
        
            switch (e.key) {
                    
                case 'left':
                case 'right':
                case 'backspace':
                case 'delete':
                case 'esc':
                    
                    _hide();
                    e.key = 'a';
                    this.fireEvent('keyup', e, 1000);
                    break;
                    
                case 'enter':
                    return false;
                    
                case 'up':
                case 'down':
                    return false;
                
                case 'space':
                default:
                    _hide();
                    _k = _keyword(_start, _pos);
                    _l = _match(_k);
                        
                    if (_l.length > 0) {
                        
                        /*
                        if (0 == _l[0].indexOf(_k) && 'undefined' == typeof(e.shoot)) {
                            //_replace(_l[0], _pos.offsetStart, _pos.offsetEnd);
                            _build();
                            _pos = _location(_start);
                        }
                        */
                        
                        _select(_start, _pos);
                        _show(_k, _l);
                    }
                    
                    break;
            }
        }
        
    });
};

Typecho.preview = {
    
    block: 'p|pre|div|blockquote|form|ul|ol|dd|table|h1|h2|h3|h4|h5|h6',

    uniqueId: 0,

    blockKeys: [],

    blockValues: [],

    values: [],

    boundary: '',

    keys: [],

    pos: 0,

    prefix: 'http://segmentfault.com/img/',

    makeUniqueId: function () {
        var id = (this.uniqueId ++) + '';

        for (var i = 0; i < 6 - id.length; i ++) {
            id = '0' + id;
        }

        return ':' + id;
    },

    cutByBlock: function (text) {
        var space = "( |　)";
        return text.replace(new RegExp(space + '*\n' + space + '*', 'ig'), '\n')
        .replace(/\n{2,}/g, '</p><p>')
        .replace(/\n/g, '<br />')
        .replace(/(<p>)?\s*<p:([0-9]{4})\/>\s*(<\/p>)?/ig, '<p:$2/>')
        .replace(new RegExp('<p>' + space + '*</p>', 'ig'), '');
    },

    trim: function (str, charlist) {

        var whitespace, l = 0,
            i = 0;
        str += '';
 
        if (!charlist) {
            // default list
            whitespace = " \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000";
        } else {
            // preg_quote custom list
            charlist += '';
            whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
        }
 
        l = str.length;
        for (i = 0; i < l; i++) {
            if (whitespace.indexOf(str.charAt(i)) === -1) {
                str = str.substring(i);
                break;
            }
        }
 
        l = str.length;
        for (i = l - 1; i >= 0; i--) {
            if (whitespace.indexOf(str.charAt(i)) === -1) {
                str = str.substring(0, i + 1);
                break;
            }
        }
 
        return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
    },

    ltrim: function (str, charlist) {
        charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
        var re = new RegExp('^[' + charlist + ']+', 'g');
        return (str + '').replace(re, '');
    },    

    arrayReverse: function (array, preserve_keys) {
        var arr_len = array.length,
            newkey = 0,
            tmp_arr = {},
            key = '';
        preserve_keys = !! preserve_keys;
 
        for (key in array) {
            newkey = arr_len - key - 1;
            tmp_arr[preserve_keys ? key : newkey] = array[key];
        }
 
        return tmp_arr;
    },    

    pregReplaceCallback: function (reg, callback, subject, limit){
	    limit = !limit?-1:limit;

	    var rs = null,
		    res = [],
		    x = 0,
		    ret = subject;

	    if (limit === -1) {
		    var tmp = [];

		    do{
			    tmp = reg.exec(subject);
			    if(tmp !== null){
				    res.push(tmp);
			    }
		    } while (tmp !== null);
	    } else {
		    res.push(reg.exec(subject));
	    }

	    for (x = res.length-1; x > -1; x--) {//explore match
		    ret = ret.replace(res[x][0],callback(res[x]));
	    }

	    return ret;
    },

    pregMatchAll: function (pattern, text) {
        var result, list = [];
        while (null != (result = pattern.exec(text))) {
            list.push(result);
        }

        return list;
    },

    strrpos: function (haystack, needle, offset) {
        var i = -1;
        if (offset) {
            i = offset > 0 ? (haystack + '').slice(offset) : (haystack + '').slice(0, offset);
            i = i.lastIndexOf(needle); // strrpos' offset indicates starting point of range till end,
            
            if (i !== -1) {
                i += offset > 0 ? offset : 0;
            }
        } else {
            i = (haystack + '').lastIndexOf(needle);
        }

        return i >= 0 ? i : false;
    },

    substrReplace: function (str, replace, start, length) {
        if (start < 0) { // start position in str
            start = start + str.length;
        }
        length = length !== undefined ? length : str.length;
        if (length < 0) {
            length = length + str.length - start;
        }
        return str.slice(0, start) + replace.substr(0, length) + replace.slice(length) + str.slice(start + length);
    },

    htmlspecialchars: function (string, quote_style, charset, double_encode) {

        var optTemp = 0,
            i = 0,
            noquotes = false;
        if (typeof quote_style === 'undefined' || quote_style === null) {
            quote_style = 2;
        }
        string = string.toString();
        if (double_encode !== false) { // Put this first to avoid double-encoding
            string = string.replace(/&/g, '&amp;');
        }
        string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
 
        var OPTS = {
            'ENT_NOQUOTES': 0,
            'ENT_HTML_QUOTE_SINGLE': 1,
            'ENT_HTML_QUOTE_DOUBLE': 2,
            'ENT_COMPAT': 2,
            'ENT_QUOTES': 3,
            'ENT_IGNORE': 4
        };

        if (quote_style === 0) {
            noquotes = true;
        }

        if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
            quote_style = [].concat(quote_style);
            for (i = 0; i < quote_style.length; i++) {
                // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
                if (OPTS[quote_style[i]] === 0) {
                    noquotes = true;
                } else if (OPTS[quote_style[i]]) {
                    optTemp = optTemp | OPTS[quote_style[i]];
                }
            }
            quote_style = optTemp;
        }

        if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
            string = string.replace(/'/g, '&#039;');
        }

        if (!noquotes) {
            string = string.replace(/"/g, '&quot;');
        }
 
        return string;
    },

    parseUrl: function (str) {

        var key = ['source', 'scheme', 'authority', 'userInfo', 'user', 'pass', 'host', 'port', 
                            'relative', 'path', 'directory', 'file', 'query', 'fragment'],
            parser = /^(?:([^:\/?#]+):)?(?:\/\/()(?:(?:()(?:([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?()(?:(()(?:(?:[^?#\/]*\/)*)()(?:[^?#]*))(?:\?([^#]*))?(?:#(.*))?)/;
 
        var m = parser.exec(str),
            uri = {},
            i = 14;

        while (i--) {
            if (m[i]) {
                uri[key[i]] = m[i];  
            }
        }

        delete uri.source;
        return uri;
    },

    buildUrl: function (params) {
        return (params.scheme ? params.scheme + '://' : '')
            + (params.user ? params.user + (params.pass ? ':' + params.pass : '') + '@' : '')
            + (params.host ? params.host : '')
            + (params.port ? ':' + params.port : '')
            + (params.path ? params.path : '')
            + (params.query ? '?' + params.query : '')
            + (params.fragment ? '#' + params.fragment : '');
    },

    fixPragraph: function (text) {
        text = this.trim(text);

        if (null == text.match(new RegExp('^<(' + this.block + ')(\\s|>)', 'i'))) {
            text = '<p>' + text;
        }

        if (null == text.match(new RegExp('</(' + this.block + ')>$', 'i'))) {
            text = text + '</p>';
        }

        return text;
    },

    replaceBlockCallback: function (matches) {
        var tagMatch = '|' + matches[1] + '|',
            text = matches[4], key = '';

        if ('|li|dd|dt|td|p|a|span|cite|strong|sup|sub|small|del|u|i|b|h1|h2|h3|h4|h5|h6|'
            .indexOf(tagMatch) >= 0) {
            text = Typecho.preview.trim(text).replace(/\n/g, '<br />');
        } else if ('|div|blockquote|form|'.indexOf(tagMatch) >= 0) {
            text = Typecho.preview.cutByBlock(text);
            if (text.indexOf('</p><p>') >= 0) {
                text = Typecho.preview.fixPragraph(text);
            }
        }

        if ('|a|span|cite|strong|sup|sub|small|del|u|i|b|'.indexOf(tagMatch)) {
            key = '<b' + matches[2] + '/>';
        } else {
            key = '<p' + matches[2] + '/>';
        }

        Typecho.preview.blockKeys.push(key);
        Typecho.preview.blockValues.push('<' + matches[1] + matches[3] + '>' + text + '</' + matches[1] + '>');

        return key;
    },

    autop: function (text) {
        this.uniqueId = 0;
        this.blockKeys = [];
        this.blockValues = [];

        text = this.trim(text).replace(/<\/p>\s+<p(\s*)/ig, '</p><p$1')
        .replace(/\s*<br\s*\/?>\s*/ig, '<br />');

        var foundTagCount = 0, textLength = text.length, 
            uniqueIdKeys = [], uniqueIdValues = [];

        list = this.pregMatchAll(new RegExp('</\\s*([a-z0-9]+)>', 'ig'), text);

        for (var i = 0; i < list.length; i++) {
            var matches = list[i], tag = matches[1],
                leftOffset = matches.index - textLength,
                posSingle = this.strrpos(text, '<' + tag + '>', leftOffset),
                posFix = this.strrpos(text, '<' + tag + ' ', leftOffset)
                pos = false;

            if (false === posSingle && false !== posFix) {
                pos = Math.max(posSingle, posFix);
            } else if (false === posSingle && false !== posFix) {
                pos = posFix;
            } else if (false !== posSingle && false === posFix) {
                pos = posSingle;
            }

            if (false !== pos) {
                var uniqueId = this.makeUniqueId();
                uniqueIdKeys.push(uniqueId);
                uniqueIdValues.push(tag);
                tagLength = tag.length;

                text = this.substrReplace(text, uniqueId, pos + 1 + tagLength, 0);
                text = this.substrReplace(text, uniqueId, matches.index + 7 + foundTagCount * 10 
                    + tagLength, 0);

                foundTagCount ++;
            }
        }

        for (var i = 0; i < uniqueIdKeys.length; i ++) {
            text = this.pregReplaceCallback(new RegExp('<(' + uniqueIdValues[i] + ')(' + uniqueIdKeys[i]
                + ')([^>]*)>([\\s\\S]*)</' + uniqueIdValues[i] + uniqueIdKeys[i] + '>', 'ig'), this.replaceBlockCallback, text , 1);
        }

        text = this.cutByBlock(text);
        var blockKeys = this.arrayReverse(this.blockKeys), 
            blockValues = this.arrayReverse(this.blockValues);

        for (var i = 0; i < this.blockKeys.length; i ++) {
            text = text.replace(blockKeys[i], blockValues[i]);
        }

        return this.fixPragraph(text);
    }
};

