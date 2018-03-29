/**
 * @author: Dennis Hernández
 * @webSite: http://djhvscf.github.io/Blog
 * @version: v1.0.0
 */

!function ($) {

    'use strict';

    document.onselectstart = function() {
        return false;
    };

    var getTableObjectFromCurrentTarget = function (currentTarget) {
        currentTarget = $(currentTarget);
        return currentTarget.is("table") ? currentTarget : currentTarget.parents().find(".table");
    };

    var getRow = function (target) {
        target = $(target);
        return target.parent().parent();
    };

    var onRowClick = function (e) {
        var that = getTableObjectFromCurrentTarget(e.currentTarget);

        if (window.event.ctrlKey) {
            toggleRow(e.currentTarget, that, false, false);
        }

        if (window.event.button === 0) {
            if (!window.event.ctrlKey && !window.event.shiftKey) {
                clearAll(that);
                toggleRow(e.currentTarget, that, false, false);
            }

            if (window.event.shiftKey) {
                selectRowsBetweenIndexes([that.bootstrapTable("getOptions").multipleSelectRowLastSelectedRow.rowIndex, e.currentTarget.rowIndex], that)
            }
        }
    };

    var onCheckboxChange = function (e) {
        var that = getTableObjectFromCurrentTarget(e.currentTarget);
        clearAll(that);
        toggleRow(getRow(e.currentTarget), that, false, false);
    };

    var toggleRow = function (row, that, clearAll, useShift) {
        if (clearAll) {
            row = $(row);
            that.bootstrapTable("getOptions").multipleSelectRowLastSelectedRow = undefined;
            row.removeClass(that.bootstrapTable("getOptions").multipleSelectRowCssClass);
            that.bootstrapTable("uncheck", row.data("index"));    
        } else {
            that.bootstrapTable("getOptions").multipleSelectRowLastSelectedRow = row;
            row = $(row);
            if (useShift) {
                row.addClass(that.bootstrapTable("getOptions").multipleSelectRowCssClass);
                that.bootstrapTable("check", row.data("index"));  
            } else {
                if(row.hasClass(that.bootstrapTable("getOptions").multipleSelectRowCssClass)) {
                    row.removeClass(that.bootstrapTable("getOptions").multipleSelectRowCssClass)
                    that.bootstrapTable("uncheck", row.data("index"));  
                } else {
                    row.addClass(that.bootstrapTable("getOptions").multipleSelectRowCssClass);
                    that.bootstrapTable("check", row.data("index"));  
                }
            }
        }
    };

    var selectRowsBetweenIndexes = function (indexes, that) {
        indexes.sort(function(a, b) {
            return a - b;
        });

        for (var i = indexes[0]; i <= indexes[1]; i++) {
            toggleRow(that.bootstrapTable("getOptions").multipleSelectRowRows[i-1], that, false, true);
        }
    };

    var clearAll = function (that) {
        for (var i = 0; i < that.bootstrapTable("getOptions").multipleSelectRowRows.length; i++) {
            toggleRow(that.bootstrapTable("getOptions").multipleSelectRowRows[i], that, true, false);
        }
    };
    
    $.extend($.fn.bootstrapTable.defaults, {
        multipleSelectRow: false,
        multipleSelectRowCssClass: 'multiple-select-row-selected',
        //internal variables used by the extension
        multipleSelectRowLastSelectedRow: undefined,
        multipleSelectRowRows: []
    });

    var BootstrapTable = $.fn.bootstrapTable.Constructor,
        _init = BootstrapTable.prototype.init,
        _initBody = BootstrapTable.prototype.initBody;

    BootstrapTable.prototype.init = function () {
        if (this.options.multipleSelectRow) {
            var that = this;

            //Make sure that the internal variables have the correct value
            this.options.multipleSelectRowLastSelectedRow = undefined;
            this.options.multipleSelectRowRows = [];
            
            this.$el.on("post-body.bs.table", function (e) {
                setTimeout(function () {
                    that.options.multipleSelectRowRows = that.$body.children();
                    that.options.multipleSelectRowRows.click(onRowClick);
                    that.options.multipleSelectRowRows.find("input[type=checkbox]").change(onCheckboxChange);
                }, 1);
            });
        }

        _init.apply(this, Array.prototype.slice.apply(arguments));
    };

    BootstrapTable.prototype.clearAllMultipleSelectionRow = function () {
        clearAll(this);
    };

    $.fn.bootstrapTable.methods.push('clearAllMultipleSelectionRow');
}(jQuery);
