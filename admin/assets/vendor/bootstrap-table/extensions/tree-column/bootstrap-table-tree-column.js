/**
 * @author: KingYang
 * @webSite: https://github.com/kingyang
 * @version: v1.0.0
 */

! function ($) {

    'use strict';

    $.extend($.fn.bootstrapTable.defaults, {
        treeShowField: null,
        idField: 'id',
        parentIdField: 'pid',
        treeVerticalcls: 'vertical',
        treeVerticalLastcls: 'vertical last',
        treeSpacecls: 'space',
        treeNodecls: 'node',
        treeCellcls: 'treenode',
        treeTextcls: 'text',
        onTreeFormatter: function (row) {
            var that = this,
                options = that.options,
                level = row._level || 0,
                plevel = row._parent && row._parent._level || 0,
                paddings = [];
            for (var i = 0; i < plevel; i++) {
                paddings.push('<i class="' + options.treeVerticalcls + '"></i>');
                paddings.push('<i class="' + options.treeSpacecls + '"></i>');
            }

            for (var i = plevel; i < level; i++) {
                if (row._last && i === (level - 1)) {
                    paddings.push('<i class="' + options.treeVerticalLastcls + '"></i>');
                } else {
                    paddings.push('<i class="' + options.treeVerticalcls + '"></i>');
                }
                paddings.push('<i class="' + options.treeNodecls + '"></i>');
            }
            return paddings.join('');
        }, onGetNodes: function (row, data) {
            var that = this;
            var nodes = [];
            $.each(data, function (i, item) {
                if (row[that.options.idField] === item[that.options.parentIdField]) {
                    nodes.push(item);
                }
            });
            return nodes;
        },
        onCheckLeaf: function (row, data) {
            if (row.isLeaf !== undefined) {
                return row.isLeaf;
            }
            return !row._nodes || !row._nodes.length;
        }, onCheckRoot: function (row, data) {
            var that = this;
            return !row[that.options.parentIdField];
        }
    });

    var BootstrapTable = $.fn.bootstrapTable.Constructor,
        _initRow = BootstrapTable.prototype.initRow,
        _initHeader = BootstrapTable.prototype.initHeader;

    BootstrapTable.prototype.initHeader = function () {
        var that = this;
        _initHeader.apply(that, Array.prototype.slice.apply(arguments));
        var treeShowField = that.options.treeShowField;
        if (treeShowField) {
            $.each(this.header.fields, function (i, field) {
                if (treeShowField === field) {
                    that.treeEnable = true;
                    var _formatter = that.header.formatters[i];
                    var _class = [that.options.treeCellcls];
                    if (that.header.classes[i]) {
                        _class.push(that.header.classes[i].split('"')[1] || '');
                    }
                    that.header.classes[i] = 'class="' + _class.join(' ') + '"';
                    that.header.formatters[i] = function (value, row, index) {
                        var colTree = [that.options.onTreeFormatter.apply(that, [row])];
                        colTree.push('<span class="' + that.options.treeTextcls + '">');
                        if (_formatter) {
                            colTree.push(_formatter.apply(this, Array.prototype.slice.apply(arguments)));
                        } else {
                            colTree.push(value);
                        }
                        colTree.push('</span>');
                        return colTree.join('');
                    };
                    return false;
                }
            });
        }
    };

    var initNode = function (item, idx, data, parentDom) {
        var that = this;
        var nodes = that.options.onGetNodes.apply(that, [item, data]);
        item._nodes = nodes;
        parentDom.append(_initRow.apply(that, [item, idx, data, parentDom]));
        var len = nodes.length - 1;
        for (var i = 0; i <= len; i++) {
            var node = nodes[i];
            node._level = item._level + 1;
            node._parent = item;
            if (i === len)
                node._last = 1;
            initNode.apply(that, [node, $.inArray(node, data), data, parentDom]);
        }
    };


    BootstrapTable.prototype.initRow = function (item, idx, data, parentDom) {
        var that = this;
        if (that.treeEnable) {
            if (that.options.onCheckRoot.apply(that, [item, data])) {
                if (item._level === undefined) {
                    item._level = 0;
                }
                initNode.apply(that, [item, idx, data, parentDom]);
                return true;
            }
            return false;

        }
        return _initRow.apply(that, Array.prototype.slice.apply(arguments));
    };

} (jQuery);