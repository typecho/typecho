(function (w) {
    w.Typecho = {
        insertFileToEditor  :   function () {}
    };
})(window);

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

            if (!check.length) {
                return;
            }

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
        }).click(function (e) {
            var target = $(e.toElement ? e.toElement : e.target),
                tagName = target.prop('tagName').toLowerCase();
            
            if ($.inArray(tagName, ['input', 'textarea', 'a', 'button']) >= 0
                && 'checkbox' != target.attr('type')) {
                e.stopPropagation();
            } else {
                clickRow(this);
            }
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

    $.fn.fileUpload = function (options) {
        var s  = $.extend({
            url         :   null,
            onUpload    :   null,
            onComplete  :   null,
            onError     :   null,
            types       :   [],
            name        :   'file',
            typesError  :   'file type error',
            single      :   false
        }, options), 
        p = this.parent().css('position', 'relative'),
        input = $('<input class="visuallyhidden" name="' + s.name + '" type="file" />').css({
            opacity     :   0,
            cursor      :   'pointer',
            position    :   'absolute',
            width       :   this.outerWidth(),
            height      :   this.outerHeight(),
            left        :   this.offset().left - p.offset().left,
            top         :   this.offset().top - p.offset().top
        }).insertAfter(this), queue = {}, prefix = 'queue-',
        index = 0;

        window.fileUploadComplete = function (id, url, data) {
            if (s.single) {
                input.prop('disabled', false);
            }

            if (!!id && queue[id]) {
                queue[id].remove();
                delete queue[id];

                if (s.onComplete) {
                    s.onComplete.call(input.get(0), id, url, data);
                }
            }
        };

        window.fileUploadError = function (id, word) {
            if (s.single) {
                input.prop('disabled', false);
            }

            if (!!id && queue[id]) {
                queue[id].remove();
                delete queue[id];

                if (s.onError) {
                    s.onError.call(input.get(0), id, word);
                }
            }
        };

        function upload (frame, id) {
            var form = $('<form action="' + s.url + '" method="post" enctype="multipart/form-data"><input type="hidden" name="_id" value="' + id + '" /></form>'),
            replace = input.clone(true).val(''),
            io = frame[0],
            doc = io.contentWindow ? io.contentWindow.document : io.contentDocument ? io.contentDocument : io.document;

            replace.insertBefore(input);
            form.append(input);
            $('body', doc).html('').append(form);
            input = replace;

            form.submit();
        }

        function checkTypes (file) {
            if (!s.types.length) {
                return true;
            }

            for (var i = 0; i < s.types.length; i ++) {
                var ext = s.types[i];

                if (file.length <= ext.length) {
                    continue;
                }

                if (ext == file.substring(file.length - ext.length)) {
                    return true;
                }
            }

            return false;
        }

        input.change(function () {
            var t = $(this), file = t.val();

            if (!file) {
                return;
            } else {
                file = file.split(/\\|\//).pop();
            }

            if (!checkTypes(file)) {
                alert(s.typesError.replace('%s', file));
                return;
            }

            if (s.single) {
                t.prop('disabled', true);
            }

            var id = prefix + index;
            index ++;

            queue[id] = $('<iframe style="display:none" id ="upload-'
                + id + '" src="about:blank"></iframe>').appendTo(document.body);

            if (s.onUpload) {
                s.onUpload.call(this, file, id);
            }

            upload(queue[id], id);
        });
    };
})($);


/**
 * TableDnD plug-in for JQuery, allows you to drag and drop table rows
 * You can set up various options to control how the system will work
 * Copyright © Denis Howlett <denish@isocra.com>
 * Licensed like jQuery, see http://docs.jquery.com/License.
 *
 * Configuration options:
 * 
 * onDragStyle
 *     This is the style that is assigned to the row during drag. There are limitations to the styles that can be
 *     associated with a row (such as you can't assign a borderâ€”well you can, but it won't be
 *     displayed). (So instead consider using onDragClass.) The CSS style to apply is specified as
 *     a map (as used in the jQuery css(...) function).
 * onDropStyle
 *     This is the style that is assigned to the row when it is dropped. As for onDragStyle, there are limitations
 *     to what you can do. Also this replaces the original style, so again consider using onDragClass which
 *     is simply added and then removed on drop.
 * onDragClass
 *     This class is added for the duration of the drag and then removed when the row is dropped. It is more
 *     flexible than using onDragStyle since it can be inherited by the row cells and other content. The default
 *     is class is tDnD_whileDrag. So to use the default, simply customise this CSS class in your
 *     stylesheet.
 * onDrop
 *     Pass a function that will be called when the row is dropped. The function takes 2 parameters: the table
 *     and the row that was dropped. You can work out the new order of the rows by using
 *     table.rows.
 * onDragStart
 *     Pass a function that will be called when the user starts dragging. The function takes 2 parameters: the
 *     table and the row which the user has started to drag.
 * onAllowDrop
 *     Pass a function that will be called as a row is over another row. If the function returns true, allow 
 *     dropping on that row, otherwise not. The function takes 2 parameters: the dragged row and the row under
 *     the cursor. It returns a boolean: true allows the drop, false doesn't allow it.
 * scrollAmount
 *     This is the number of pixels to scroll if the user moves the mouse cursor to the top or bottom of the
 *     window. The page should automatically scroll up or down as appropriate (tested in IE6, IE7, Safari, FF2,
 *     FF3 beta)
 * 
 * Other ways to control behaviour:
 *
 * Add class="nodrop" to any rows for which you don't want to allow dropping, and class="nodrag" to any rows
 * that you don't want to be draggable.
 *
 * Inside the onDrop method you can also call $.tableDnD.serialize() this returns a string of the form
 * <tableID>[]=<rowID1>&<tableID>[]=<rowID2> so that you can send this back to the server. The table must have
 * an ID as must all the rows.
 *
 * Known problems:
 * - Auto-scoll has some problems with IE7  (it scrolls even when it shouldn't), work-around: set scrollAmount to 0
 * 
 * Version 0.2: 2008-02-20 First public version
 * Version 0.3: 2008-02-07 Added onDragStart option
 *                         Made the scroll amount configurable (default is 5 as before)
 * Version 0.4: 2008-03-15 Changed the noDrag/noDrop attributes to nodrag/nodrop classes
 *                         Added onAllowDrop to control dropping
 *                         Fixed a bug which meant that you couldn't set the scroll amount in both directions
 *                         Added serialise method
 */
(function (jQuery) {
jQuery.tableDnD = {
    /** Keep hold of the current table being dragged */
    currentTable : null,
    /** Keep hold of the current drag object if any */
    dragObject: null,
    /** The current mouse offset */
    mouseOffset: null,
    /** Remember the old value of Y so that we don't do too much processing */
    oldY: 0,

    /** Actually build the structure */
    build: function(options) {
        // Make sure options exists
        options = options || {};
        // Set up the defaults if any

        this.each(function() {
            // Remember the options
            this.tableDnDConfig = {
                onDragStyle: options.onDragStyle,
                onDropStyle: options.onDropStyle,
				// Add in the default class for whileDragging
				onDragClass: options.onDragClass ? options.onDragClass : "tDnD_whileDrag",
                onDrop: options.onDrop,
                onDragStart: options.onDragStart,
                scrollAmount: options.scrollAmount ? options.scrollAmount : 5
            };
            // Now make the rows draggable
            jQuery.tableDnD.makeDraggable(this);

            // fix chrome border bug
            if (0 == $('tfoot', this).length
                && 0 < $('thead', this).length) {
                var h = $('thead', this), count = $('th', h).length,
                    f = $('<tfoot><tr><td style="padding:0;height:0;line-height:0;border:none" colspan="' + count 
                    + '"></td></tr></tfoot>').insertAfter(h),
                    l = $('tr:last', this);

                if (l.parent().prop('tagName').toLowerCase() != 'tfoot') {
                    var td = $('td', l), dh = td.height();
                    td.height(dh - f.outerHeight());
                }
            }
        });

        // Now we need to capture the mouse up and mouse move event
        // We can use bind so that we don't interfere with other event handlers
        jQuery(document)
            .bind('mousemove', jQuery.tableDnD.mousemove)
            .bind('mouseup', jQuery.tableDnD.mouseup);

        // Don't break the chain
        return this;
    },

    /** This function makes all the rows on the table draggable apart from those marked as "NoDrag" */
    makeDraggable: function(table) {
        // Now initialise the rows
        var rows = table.rows; //getElementsByTagName("tr")
        var config = table.tableDnDConfig;
        for (var i=0; i<rows.length; i++) {
            // To make non-draggable rows, add the nodrag class (eg for Category and Header rows) 
			// inspired by John Tarr and Famic
            var nodrag = $(rows[i]).hasClass("nodrag");
            if (! nodrag) { //There is no NoDnD attribute on rows I want to drag
                jQuery(rows[i]).mousedown(function(ev) {
                    if (ev.target.tagName == "TD") {
                        jQuery.tableDnD.dragObject = this;
                        jQuery.tableDnD.currentTable = table;
                        jQuery.tableDnD.mouseOffset = jQuery.tableDnD.getMouseOffset(this, ev);
                        if (config.onDragStart) {
                            // Call the onDrop method if there is one
                            config.onDragStart(table, this);
                        }
                        return false;
                    }
                }).css("cursor", "move"); // Store the tableDnD object
            }
        }
    },

    /** Get the mouse coordinates from the event (allowing for browser differences) */
    mouseCoords: function(ev){
        if(ev.pageX || ev.pageY){
            return {x:ev.pageX, y:ev.pageY};
        }
        return {
            x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
            y:ev.clientY + document.body.scrollTop  - document.body.clientTop
        };
    },

    /** Given a target element and a mouse event, get the mouse offset from that element.
        To do this we need the element's position and the mouse position */
    getMouseOffset: function(target, ev) {
        ev = ev || window.event;

        var docPos    = this.getPosition(target);
        var mousePos  = this.mouseCoords(ev);
        return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
    },

    /** Get the position of an element by going up the DOM tree and adding up all the offsets */
    getPosition: function(e){
        var left = 0;
        var top  = 0;
        /** Safari fix -- thanks to Luis Chato for this! */
        if (e.offsetHeight == 0) {
            /** Safari 2 doesn't correctly grab the offsetTop of a table row
            this is detailed here:
            http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari/
            the solution is likewise noted there, grab the offset of a table cell in the row - the firstChild.
            note that firefox will return a text node as a first child, so designing a more thorough
            solution may need to take that into account, for now this seems to work in firefox, safari, ie */
            e = e.firstChild; // a table cell
        }

        while (e.offsetParent){
            left += e.offsetLeft;
            top  += e.offsetTop;
            e     = e.offsetParent;
        }

        left += e.offsetLeft;
        top  += e.offsetTop;

        return {x:left, y:top};
    },

    mousemove: function(ev) {
        if (jQuery.tableDnD.dragObject == null) {
            return;
        }

        var dragObj = jQuery(jQuery.tableDnD.dragObject);
        var config = jQuery.tableDnD.currentTable.tableDnDConfig;
        var mousePos = jQuery.tableDnD.mouseCoords(ev);
        var y = mousePos.y - jQuery.tableDnD.mouseOffset.y;
        //auto scroll the window
	    var yOffset = window.pageYOffset;
	 	if (document.all) {
	        // Windows version
	        //yOffset=document.body.scrollTop;
	        if (typeof document.compatMode != 'undefined' &&
	             document.compatMode != 'BackCompat') {
	           yOffset = document.documentElement.scrollTop;
	        }
	        else if (typeof document.body != 'undefined') {
	           yOffset=document.body.scrollTop;
	        }

	    }
		    
		if (mousePos.y-yOffset < config.scrollAmount) {
	    	window.scrollBy(0, -config.scrollAmount);
	    } else {
            var windowHeight = window.innerHeight ? window.innerHeight
                    : document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
            if (windowHeight-(mousePos.y-yOffset) < config.scrollAmount) {
                window.scrollBy(0, config.scrollAmount);
            }
        }


        if (y != jQuery.tableDnD.oldY) {
            // work out if we're going up or down...
            var movingDown = y > jQuery.tableDnD.oldY;
            // update the old value
            jQuery.tableDnD.oldY = y;
            // update the style to show we're dragging
			if (config.onDragClass) {
				dragObj.addClass(config.onDragClass);
			} else {
	            dragObj.css(config.onDragStyle);
			}
            // If we're over a row then move the dragged row to there so that the user sees the
            // effect dynamically
            var currentRow = jQuery.tableDnD.findDropTargetRow(dragObj, y);
            if (currentRow) {
                // TODO worry about what happens when there are multiple TBODIES
                if (movingDown && jQuery.tableDnD.dragObject != currentRow) {
                    jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject, currentRow.nextSibling);
                } else if (! movingDown && jQuery.tableDnD.dragObject != currentRow) {
                    jQuery.tableDnD.dragObject.parentNode.insertBefore(jQuery.tableDnD.dragObject, currentRow);
                }
            }
        }

        return false;
    },

    /** We're only worried about the y position really, because we can only move rows up and down */
    findDropTargetRow: function(draggedRow, y) {
        var rows = jQuery.tableDnD.currentTable.rows;
        for (var i=0; i<rows.length; i++) {
            var row = rows[i];
            var rowY    = this.getPosition(row).y;
            var rowHeight = parseInt(row.offsetHeight)/2;
            if (row.offsetHeight == 0) {
                rowY = this.getPosition(row.firstChild).y;
                rowHeight = parseInt(row.firstChild.offsetHeight)/2;
            }
            // Because we always have to insert before, we need to offset the height a bit
            if ((y > rowY - rowHeight) && (y < (rowY + rowHeight))) {
                // that's the row we're over
				// If it's the same as the current row, ignore it
				if (row == draggedRow) {return null;}
                var config = jQuery.tableDnD.currentTable.tableDnDConfig;
                if (config.onAllowDrop) {
                    if (config.onAllowDrop(draggedRow, row)) {
                        return row;
                    } else {
                        return null;
                    }
                } else {
					// If a row has nodrop class, then don't allow dropping (inspired by John Tarr and Famic)
                    var nodrop = $(row).hasClass("nodrop");
                    if (! nodrop) {
                        return row;
                    } else {
                        return null;
                    }
                }
                return row;
            }
        }
        return null;
    },

    mouseup: function(e) {
        if (jQuery.tableDnD.currentTable && jQuery.tableDnD.dragObject) {
            var droppedRow = jQuery.tableDnD.dragObject;
            var config = jQuery.tableDnD.currentTable.tableDnDConfig;
            // If we have a dragObject, then we need to release it,
            // The row will already have been moved to the right place so we just reset stuff
			if (config.onDragClass) {
	            jQuery(droppedRow).removeClass(config.onDragClass);
			} else {
	            jQuery(droppedRow).css(config.onDropStyle);
			}
            jQuery.tableDnD.dragObject   = null;
            if (config.onDrop) {
                // Call the onDrop method if there is one
                config.onDrop(jQuery.tableDnD.currentTable, droppedRow);
            }
            jQuery.tableDnD.currentTable = null; // let go of the table too
        }
    },

    serialize: function() {
        if (jQuery.tableDnD.currentTable) {
            var result = "";
            var tableId = jQuery.tableDnD.currentTable.id;
            var rows = jQuery.tableDnD.currentTable.rows;
            for (var i=0; i<rows.length; i++) {
                if (result.length > 0) result += "&";
                result += tableId + '[]=' + rows[i].id;
            }
            return result;
        } else {
            return "Error: No Table id set, you need to set an id on your table and every row";
        }
    }
}

jQuery.fn.extend(
	{
		tableDnD : jQuery.tableDnD.build
	}
);
})($);

/*
	Masked Input plugin for jQuery
	Copyright (c) 2007-2013 Josh Bush (digitalbush.com)
	Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
	Version: 1.3.1
*/
(function($) {
	function getPasteEvent() {
    var el = document.createElement('input'),
        name = 'onpaste';
    el.setAttribute(name, '');
    return (typeof el[name] === 'function')?'paste':'input';             
}

var pasteEventName = getPasteEvent() + ".mask",
	ua = navigator.userAgent,
	iPhone = /iphone/i.test(ua),
	android=/android/i.test(ua),
	caretTimeoutId;

$.mask = {
	//Predefined character definitions
	definitions: {
		'9': "[0-9]",
		'a': "[A-Za-z]",
		'*': "[A-Za-z0-9]"
	},
	dataName: "rawMaskFn",
	placeholder: '_',
};

$.fn.extend({
	//Helper Function for Caret positioning
	caret: function(begin, end) {
		var range;

		if (this.length === 0 || this.is(":hidden")) {
			return;
		}

		if (typeof begin == 'number') {
			end = (typeof end === 'number') ? end : begin;
			return this.each(function() {
				if (this.setSelectionRange) {
					this.setSelectionRange(begin, end);
				} else if (this.createTextRange) {
					range = this.createTextRange();
					range.collapse(true);
					range.moveEnd('character', end);
					range.moveStart('character', begin);
					range.select();
				}
			});
		} else {
			if (this[0].setSelectionRange) {
				begin = this[0].selectionStart;
				end = this[0].selectionEnd;
			} else if (document.selection && document.selection.createRange) {
				range = document.selection.createRange();
				begin = 0 - range.duplicate().moveStart('character', -100000);
				end = begin + range.text.length;
			}
			return { begin: begin, end: end };
		}
	},
	unmask: function() {
		return this.trigger("unmask");
	},
	mask: function(mask, settings) {
		var input,
			defs,
			tests,
			partialPosition,
			firstNonMaskPos,
			len;

		if (!mask && this.length > 0) {
			input = $(this[0]);
			return input.data($.mask.dataName)();
		}
		settings = $.extend({
			placeholder: $.mask.placeholder, // Load default placeholder
			completed: null
		}, settings);


		defs = $.mask.definitions;
		tests = [];
		partialPosition = len = mask.length;
		firstNonMaskPos = null;

		$.each(mask.split(""), function(i, c) {
			if (c == '?') {
				len--;
				partialPosition = i;
			} else if (defs[c]) {
				tests.push(new RegExp(defs[c]));
				if (firstNonMaskPos === null) {
					firstNonMaskPos = tests.length - 1;
				}
			} else {
				tests.push(null);
			}
		});

		return this.trigger("unmask").each(function() {
			var input = $(this),
				buffer = $.map(
				mask.split(""),
				function(c, i) {
					if (c != '?') {
						return defs[c] ? settings.placeholder : c;
					}
				}),
				focusText = input.val();

			function seekNext(pos) {
				while (++pos < len && !tests[pos]);
				return pos;
			}

			function seekPrev(pos) {
				while (--pos >= 0 && !tests[pos]);
				return pos;
			}

			function shiftL(begin,end) {
				var i,
					j;

				if (begin<0) {
					return;
				}

				for (i = begin, j = seekNext(end); i < len; i++) {
					if (tests[i]) {
						if (j < len && tests[i].test(buffer[j])) {
							buffer[i] = buffer[j];
							buffer[j] = settings.placeholder;
						} else {
							break;
						}

						j = seekNext(j);
					}
				}
				writeBuffer();
				input.caret(Math.max(firstNonMaskPos, begin));
			}

			function shiftR(pos) {
				var i,
					c,
					j,
					t;

				for (i = pos, c = settings.placeholder; i < len; i++) {
					if (tests[i]) {
						j = seekNext(i);
						t = buffer[i];
						buffer[i] = c;
						if (j < len && tests[j].test(t)) {
							c = t;
						} else {
							break;
						}
					}
				}
			}

			function keydownEvent(e) {
				var k = e.which,
					pos,
					begin,
					end;

				//backspace, delete, and escape get special treatment
				if (k === 8 || k === 46 || (iPhone && k === 127)) {
					pos = input.caret();
					begin = pos.begin;
					end = pos.end;

					if (end - begin === 0) {
						begin=k!==46?seekPrev(begin):(end=seekNext(begin-1));
						end=k===46?seekNext(end):end;
					}
					clearBuffer(begin, end);
					shiftL(begin, end - 1);

					e.preventDefault();
				} else if (k == 27) {//escape
					input.val(focusText);
					input.caret(0, checkVal());
					e.preventDefault();
				}
			}

			function keypressEvent(e) {
				var k = e.which,
					pos = input.caret(),
					p,
					c,
					next;

				if (e.ctrlKey || e.altKey || e.metaKey || k < 32) {//Ignore
					return;
				} else if (k) {
					if (pos.end - pos.begin !== 0){
						clearBuffer(pos.begin, pos.end);
						shiftL(pos.begin, pos.end-1);
					}

					p = seekNext(pos.begin - 1);
					if (p < len) {
						c = String.fromCharCode(k);
						if (tests[p].test(c)) {
							shiftR(p);

							buffer[p] = c;
							writeBuffer();
							next = seekNext(p);

							if(android){
								setTimeout($.proxy($.fn.caret,input,next),0);
							}else{
								input.caret(next);
							}

							if (settings.completed && next >= len) {
								settings.completed.call(input);
							}
						}
					}
					e.preventDefault();
				}
			}

			function clearBuffer(start, end) {
				var i;
				for (i = start; i < end && i < len; i++) {
					if (tests[i]) {
						buffer[i] = settings.placeholder;
					}
				}
			}

			function writeBuffer() { input.val(buffer.join('')); }

			function checkVal(allow) {
				//try to place characters where they belong
				var test = input.val(),
					lastMatch = -1,
					i,
					c;

				for (i = 0, pos = 0; i < len; i++) {
					if (tests[i]) {
						buffer[i] = settings.placeholder;
						while (pos++ < test.length) {
							c = test.charAt(pos - 1);
							if (tests[i].test(c)) {
								buffer[i] = c;
								lastMatch = i;
								break;
							}
						}
						if (pos > test.length) {
							break;
						}
					} else if (buffer[i] === test.charAt(pos) && i !== partialPosition) {
						pos++;
						lastMatch = i;
					}
				}
				if (allow) {
					writeBuffer();
				} else if (lastMatch + 1 < partialPosition) {
					input.val("");
					clearBuffer(0, len);
				} else {
					writeBuffer();
					input.val(input.val().substring(0, lastMatch + 1));
				}
				return (partialPosition ? i : firstNonMaskPos);
			}

			input.data($.mask.dataName,function(){
				return $.map(buffer, function(c, i) {
					return tests[i]&&c!=settings.placeholder ? c : null;
				}).join('');
			});

			if (!input.attr("readonly"))
				input
				.one("unmask", function() {
					input
						.unbind(".mask")
						.removeData($.mask.dataName);
				})
				.bind("focus.mask", function() {
					clearTimeout(caretTimeoutId);
					var pos,
						moveCaret;

					focusText = input.val();
					pos = checkVal();
					
					caretTimeoutId = setTimeout(function(){
						writeBuffer();
						if (pos == mask.length) {
							input.caret(0, pos);
						} else {
							input.caret(pos);
						}
					}, 10);
				})
				.bind("blur.mask", function() {
					checkVal();
					if (input.val() != focusText)
						input.change();
				})
				.bind("keydown.mask", keydownEvent)
				.bind("keypress.mask", keypressEvent)
				.bind(pasteEventName, function() {
					setTimeout(function() { 
						var pos=checkVal(true);
						input.caret(pos); 
						if (settings.completed && pos == input.val().length)
							settings.completed.call(input);
					}, 0);
				});
			checkVal(); //Perform initial check for existing values
		});
	}
});


})(jQuery);

/*
 * jQuery plugin: fieldSelection - v0.1.1 - last change: 2006-12-16
 * (c) 2006 Alex Brem <alex@0xab.cd> - http://blog.0xab.cd
 */

jQuery.fn.extend({
    getSelection: function () {

        var e = this.get(0);
        if (!e) {
            return null;
        }

        return (

            /* mozilla / dom 3.0 */
            ('selectionStart' in e && function() {
            var l = e.selectionEnd - e.selectionStart;
                return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
            }) ||

            /* other */
            (window.getSelection() && function () {
                var selection = window.getSelection(), range = selection.getRangeAt(0);

                return { start: range.startOffset, end: range.endOffset, length: range.endOffset - range.startOffset, text: range.toString()};

            }) ||

            /* exploder */
            (document.selection && function() {

                e.focus();

                var r = document.selection.createRange();
                if (r === null) {
                    return { start: 0, end: e.value.length, length: 0 }
                }

                var re = e.createTextRange();
                var rc = re.duplicate();
                re.moveToBookmark(r.getBookmark());
                rc.setEndPoint('EndToStart', re);

                return { start: rc.text.length, end: rc.text.length + r.text.length, length: r.text.length, text: r.text };
            }) || 

            /* browser not supported */
            function() { return null; }

        )();
    },

    setSelection: function (start, end) {
        var e = this.get(0);
        if (!e) {
            return;
        }

        if ($.browser.opera && $.browser.version >= 9.5 && len == 0) {
			return false;
		}

        if (e.setSelectionRange) {
            e.focus();
            e.setSelectionRange(start, end);
        } else if (e.createTextRange) {
            var range = e.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }
    },

    replaceSelection: function () {

        var e = this.get(0);
        if (!e) {
            return null;
        }

        var text = arguments[0] || '';

        return (

            /* mozilla / dom 3.0 */
            ('selectionStart' in e && function() {
                e.value = e.value.substr(0, e.selectionStart) + text + e.value.substr(e.selectionEnd, e.value.length);
                return this;
            }) ||

            /* exploder */
            (document.selection && function() {
                e.focus();
                document.selection.createRange().text = text;
                return this;
            }) ||

            /* browser not supported */
            function() {
                e.value += text;
                return jQuery(e);
            }

        )();
    }
});

/**
 * jQuery Cookie plugin
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
jQuery.cookie = function (key, value, options) {

    // key and at least value given, set cookie...
    if (arguments.length > 1 && String(value) !== "[object Object]") {
        options = jQuery.extend({}, options);

        if (value === null || value === undefined) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }

        value = String(value);

        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? value : encodeURIComponent(value),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};

