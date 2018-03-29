/*!
 * jQuery.filer
 * Copyright (c) 2016 CreativeDream
 * Website: https://github.com/CreativeDream/jquery.filer
 * Version: 1.3 (14-Sep-2016)
 * Requires: jQuery v1.7.1 or later
 */
(function($) {
    "use strict";
    $.fn.filer = function(q) {
        return this.each(function(t, r) {
            var s = $(r),
                b = '.jFiler',
                p = $(),
                o = $(),
                l = $(),
                sl = [],
                n_f = $.isFunction(q) ? q(s, $.fn.filer.defaults) : q,
                n = n_f && $.isPlainObject(n_f) ? $.extend(true, {}, $.fn.filer.defaults, n_f) : $.fn.filer.defaults,
                f = {
                    init: function() {
                        s.wrap('<div class="jFiler"></div>');
                        f._set('props');
                        s.prop("jFiler").boxEl = p = s.closest(b);
                        f._changeInput();
                    },
                    _bindInput: function() {
                        if (n.changeInput && o.length > 0) {
                            o.on("click", f._clickHandler);
                        }
                        s.on({
                            "focus": function() {
                                o.addClass('focused');
                            },
                            "blur": function() {
                                o.removeClass('focused');
                            },
                            "change": f._onChange
                        });
                        if (n.dragDrop) {
                            n.dragDrop.dragContainer.on("drag dragstart dragend dragover dragenter dragleave drop", function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                            });
                            n.dragDrop.dragContainer.on("drop", f._dragDrop.drop);
                            n.dragDrop.dragContainer.on("dragover", f._dragDrop.dragEnter);
                            n.dragDrop.dragContainer.on("dragleave", f._dragDrop.dragLeave);
                        }
                        if (n.uploadFile && n.clipBoardPaste) {
                            $(window)
                                .on("paste", f._clipboardPaste);
                        }
                    },
                    _unbindInput: function(all) {
                        if (n.changeInput && o.length > 0) {
                            o.off("click", f._clickHandler);
                        }

                        if (all) {
                            s.off("change", f._onChange);
                            if (n.dragDrop) {
                                n.dragDrop.dragContainer.off("drop", f._dragDrop.drop);
                                n.dragDrop.dragContainer.off("dragover", f._dragDrop.dragEnter);
                                n.dragDrop.dragContainer.off("dragleave", f._dragDrop.dragLeave);
                            }
                            if (n.uploadFile && n.clipBoardPaste) {
                                $(window)
                                    .off("paste", f._clipboardPaste);
                            }
                        }
                    },
                    _clickHandler: function() {
                        if (!n.uploadFile && n.addMore && s.val().length != 0) {
                            f._unbindInput(true);
                            var elem = $('<input type="file" />');
                            var attributes = s.prop("attributes");
                            $.each(attributes, function() {
                                if (this.name == "required") return;
                                elem.attr(this.name, this.value);
                            });
                            s.after(elem);
                            sl.push(elem);
                            s = elem;
                            f._bindInput();
                            f._set('props');
                        }
                        s.click()
                    },
                    _applyAttrSettings: function() {
                        var d = ["name", "limit", "maxSize", "fileMaxSize", "extensions", "changeInput", "showThumbs", "appendTo", "theme", "addMore", "excludeName", "files", "uploadUrl", "uploadData", "options"];
                        for (var k in d) {
                            var j = "data-jfiler-" + d[k];
                            if (f._assets.hasAttr(j)) {
                                switch (d[k]) {
                                    case "changeInput":
                                    case "showThumbs":
                                    case "addMore":
                                        n[d[k]] = (["true", "false"].indexOf(s.attr(j)) > -1 ? s.attr(j) == "true" : s.attr(j));
                                        break;
                                    case "extensions":
                                        n[d[k]] = s.attr(j)
                                            .replace(/ /g, '')
                                            .split(",");
                                        break;
                                    case "uploadUrl":
                                        if (n.uploadFile) n.uploadFile.url = s.attr(j);
                                        break;
                                    case "uploadData":
                                        if (n.uploadFile) n.uploadFile.data = JSON.parse(s.attr(j));
                                        break;
                                    case "files":
                                    case "options":
                                        n[d[k]] = JSON.parse(s.attr(j));
                                        break;
                                    default:
                                        n[d[k]] = s.attr(j);
                                }
                                s.removeAttr(j);
                            }
                        }
                    },
                    _changeInput: function() {
                        f._applyAttrSettings();
                        n.beforeRender != null && typeof n.beforeRender == "function" ? n.beforeRender(p, s) : null;
                        if (n.theme) {
                            p.addClass('jFiler-theme-' + n.theme);
                        }
                        if (s.get(0)
                            .tagName.toLowerCase() != "input" && s.get(0)
                            .type != "file") {
                            o = s;
                            s = $("<input type=\"file\" name=\"" + n.name + "\" />");
                            s.css({
                                position: "absolute",
                                left: "-9999px",
                                top: "-9999px",
                                "z-index": "-9999"
                            });
                            p.prepend(s);
                            f._isGn = s;
                        } else {
                            if (n.changeInput) {
                                switch (typeof n.changeInput) {
                                    case "boolean":
                                        o = $('<div class="jFiler-input"><div class="jFiler-input-caption"><span>' + n.captions.feedback + '</span></div><div class="jFiler-input-button">' + n.captions.button + '</div></div>"');
                                        break;
                                    case "string":
                                    case "object":
                                        o = $(n.changeInput);
                                        break;
                                    case "function":
                                        o = $(n.changeInput(p, s, n));
                                        break;
                                }
                                s.after(o);
                                s.css({
                                    position: "absolute",
                                    left: "-9999px",
                                    top: "-9999px",
                                    "z-index": "-9999"
                                });
                            }
                        }
                        s.prop("jFiler").newInputEl = o;
                        if (n.dragDrop) {
                            n.dragDrop.dragContainer = n.dragDrop.dragContainer ? $(n.dragDrop.dragContainer) : o;
                        }
                        if (!n.limit || (n.limit && n.limit >= 2)) {
                            s.attr("multiple", "multiple");
                            s.attr("name")
                                .slice(-2) != "[]" ? s.attr("name", s.attr("name") + "[]") : null;
                        }
                        if (!s.attr("disabled") && !n.disabled) {
                            n.disabled = false;
                            f._bindInput();
                            p.removeClass("jFiler-disabled");
                        } else {
                            n.disabled = true;
                            f._unbindInput(true);
                            p.addClass("jFiler-disabled");
                        }
                        if (n.files) {
                            f._append(false, {
                                files: n.files
                            });
                        }
                        n.afterRender != null && typeof n.afterRender == "function" ? n.afterRender(l, p, o, s) : null;
                    },
                    _clear: function() {
                        f.files = null;
                        s.prop("jFiler")
                            .files = null;
                        if (!n.uploadFile && !n.addMore) {
                            f._reset();
                        }
                        f._set('feedback', (f._itFl && f._itFl.length > 0 ? f._itFl.length + ' ' + n.captions.feedback2 : n.captions.feedback));
                        n.onEmpty != null && typeof n.onEmpty == "function" ? n.onEmpty(p, o, s) : null
                    },
                    _reset: function(a) {
                        if (!a) {
                            if (!n.uploadFile && n.addMore) {
                                for (var i = 0; i < sl.length; i++) {
                                    sl[i].remove();
                                }
                                sl = [];
                                f._unbindInput(true);
                                if (f._isGn) {
                                    s = f._isGn;
                                } else {
                                    s = $(r);
                                }
                                f._bindInput();
                            }
                            f._set('input', '');
                        }
                        f._itFl = [];
                        f._itFc = null;
                        f._ajFc = 0;
                        f._set('props');
                        s.prop("jFiler")
                            .files_list = f._itFl;
                        s.prop("jFiler")
                            .current_file = f._itFc;
                        f._itFr = [];
                        p.find("input[name^='jfiler-items-exclude-']:hidden")
                            .remove();
                        l.fadeOut("fast", function() {
                            $(this)
                                .remove();
                        });
                        s.prop("jFiler").listEl = l = $();
                    },
                    _set: function(element, value) {
                        switch (element) {
                            case 'input':
                                s.val(value);
                                break;
                            case 'feedback':
                                if (o.length > 0) {
                                    o.find('.jFiler-input-caption span')
                                        .html(value);
                                }
                                break;
                            case 'props':
                                if (!s.prop("jFiler")) {
                                    s.prop("jFiler", {
                                        options: n,
                                        listEl: l,
                                        boxEl: p,
                                        newInputEl: o,
                                        inputEl: s,
                                        files: f.files,
                                        files_list: f._itFl,
                                        current_file: f._itFc,
                                        append: function(data) {
                                            return f._append(false, {
                                                files: [data]
                                            });
                                        },
                                        enable: function() {
                                            if (!n.disabled)
                                                return;
                                            n.disabled = false;
                                            s.removeAttr("disabled");
                                            p.removeClass("jFiler-disabled");
                                            f._bindInput();
                                        },
                                        disable: function() {
                                            if (n.disabled)
                                                return;
                                            n.disabled = true;
                                            p.addClass("jFiler-disabled");
                                            f._unbindInput(true);
                                        },
                                        remove: function(id) {
                                            f._remove(null, {
                                                binded: true,
                                                data: {
                                                    id: id
                                                }
                                            });
                                            return true;
                                        },
                                        reset: function() {
                                            f._reset();
                                            f._clear();
                                            return true;
                                        },
                                        retry: function(data) {
                                            return f._retryUpload(data);
                                        }
                                    })
                                }
                        }
                    },
                    _filesCheck: function() {
                        var s = 0;
                        if (n.limit && f.files.length + f._itFl.length > n.limit) {
                            n.dialogs.alert(f._assets.textParse(n.captions.errors.filesLimit));
                            return false
                        }
                        for (var t = 0; t < f.files.length; t++) {
                            var file = f.files[t],
                                x = file.name.split(".")
                                .pop()
                                .toLowerCase(),
                                m = {
                                    name: file.name,
                                    size: file.size,
                                    size2: f._assets.bytesToSize(file.size),
                                    type: file.type,
                                    ext: x
                                };
                            if (n.extensions != null && $.inArray(x, n.extensions) == -1 && $.inArray(m.type, n.extensions) == -1) {
                                n.dialogs.alert(f._assets.textParse(n.captions.errors.filesType, m));
                                return false;
                            }
                            if ((n.maxSize != null && f.files[t].size > n.maxSize * 1048576) || (n.fileMaxSize != null && f.files[t].size > n.fileMaxSize * 1048576)) {
                                n.dialogs.alert(f._assets.textParse(n.captions.errors.filesSize, m));
                                return false;
                            }
                            if (file.size == 4096 && file.type.length == 0) {
                                n.dialogs.alert(f._assets.textParse(n.captions.errors.folderUpload, m));
                                return false;
                            }
                            if (n.onFileCheck != null && typeof n.onFileCheck == "function" ? n.onFileCheck(m, n, f._assets.textParse) === false : null) {
                                return false;
                            }

                            if ((n.uploadFile || n.addMore) && !n.allowDuplicates) {
                                var m = f._itFl.filter(function(a, b) {
                                    if (a.file.name == file.name && a.file.size == file.size && a.file.type == file.type && (file.lastModified ? a.file.lastModified == file.lastModified : true)) {
                                        return true;
                                    }
                                });
                                if (m.length > 0) {
                                    if (f.files.length == 1) {
                                        return false;
                                    } else {
                                        file._pendRemove = true;
                                    }
                                }
                            }

                            s += f.files[t].size
                        }
                        if (n.maxSize != null && s >= Math.round(n.maxSize * 1048576)) {
                            n.dialogs.alert(f._assets.textParse(n.captions.errors.filesSizeAll));
                            return false
                        }
                        return true;
                    },
                    _thumbCreator: {
                        create: function(i) {
                            var file = f.files[i],
                                id = (f._itFc ? f._itFc.id : i),
                                name = file.name,
                                size = file.size,
                                url = file.file,
                                type = file.type ? file.type.split("/", 1) : ""
                                .toString()
                                .toLowerCase(),
                                ext = name.indexOf(".") != -1 ? name.split(".")
                                .pop()
                                .toLowerCase() : "",
                                progressBar = n.uploadFile ? '<div class="jFiler-jProgressBar">' + n.templates.progressBar + '</div>' : '',
                                opts = {
                                    id: id,
                                    name: name,
                                    size: size,
                                    size2: f._assets.bytesToSize(size),
                                    url: url,
                                    type: type,
                                    extension: ext,
                                    icon: f._assets.getIcon(ext, type),
                                    icon2: f._thumbCreator.generateIcon({
                                        type: type,
                                        extension: ext
                                    }),
                                    image: '<div class="jFiler-item-thumb-image fi-loading"></div>',
                                    progressBar: progressBar,
                                    _appended: file._appended
                                },
                                html = "";
                            if (file.opts) {
                                opts = $.extend({}, file.opts, opts);
                            }
                            html = $(f._thumbCreator.renderContent(opts))
                                .attr("data-jfiler-index", id);
                            html.get(0)
                                .jfiler_id = id;
                            f._thumbCreator.renderFile(file, html, opts);
                            if (file.forList) {
                                return html;
                            }
                            f._itFc.html = html;
                            html.hide()[n.templates.itemAppendToEnd ? "appendTo" : "prependTo"](l.find(n.templates._selectors.list))
                                .show();
                            if (!file._appended) {
                                f._onSelect(i);
                            }
                        },
                        renderContent: function(opts) {
                            return f._assets.textParse((opts._appended ? n.templates.itemAppend : n.templates.item), opts);
                        },
                        renderFile: function(file, html, opts) {
                            if (html.find('.jFiler-item-thumb-image')
                                .length == 0) {
                                return false;
                            }
                            if (file.file && opts.type == "image") {
                                var g = '<img src="' + file.file + '" draggable="false" />',
                                    m = html.find('.jFiler-item-thumb-image.fi-loading');
                                $(g)
                                    .error(function() {
                                        g = f._thumbCreator.generateIcon(opts);
                                        html.addClass('jFiler-no-thumbnail');
                                        m.removeClass('fi-loading')
                                            .html(g);
                                    })
                                    .load(function() {
                                        m.removeClass('fi-loading')
                                            .html(g);
                                    });
                                return true;
                            }
                            if (window.File && window.FileList && window.FileReader && opts.type == "image" && opts.size < 1e+7) {
                                var y = new FileReader;
                                y.onload = function(e) {
                                    var m = html.find('.jFiler-item-thumb-image.fi-loading');
                                    if (n.templates.canvasImage) {
                                        var canvas = document.createElement('canvas'),
                                            context = canvas.getContext('2d'),
                                            img = new Image();

                                        img.onload = function() {
                                            var height = m.height(),
                                                width = m.width(),
                                                heightRatio = img.height / height,
                                                widthRatio = img.width / width,
                                                optimalRatio = heightRatio < widthRatio ? heightRatio : widthRatio,
                                                optimalHeight = img.height / optimalRatio,
                                                optimalWidth = img.width / optimalRatio,
                                                steps = Math.ceil(Math.log(img.width / optimalWidth) / Math.log(2));

                                            canvas.height = height;
                                            canvas.width = width;

                                            if (img.width < canvas.width || img.height < canvas.height || steps <= 1) {
                                                var x = img.width < canvas.width ? canvas.width / 2 - img.width / 2 : img.width > canvas.width ? -(img.width - canvas.width) / 2 : 0,
                                                    y = img.height < canvas.height ? canvas.height / 2 - img.height / 2 : 0
                                                context.drawImage(img, x, y, img.width, img.height);
                                            } else {
                                                var oc = document.createElement('canvas'),
                                                    octx = oc.getContext('2d');
                                                oc.width = img.width * 0.5;
                                                oc.height = img.height * 0.5;
                                                octx.fillStyle = "#fff";
                                                octx.fillRect(0, 0, oc.width, oc.height);
                                                octx.drawImage(img, 0, 0, oc.width, oc.height);
                                                octx.drawImage(oc, 0, 0, oc.width * 0.5, oc.height * 0.5);

                                                context.drawImage(oc, optimalWidth > canvas.width ? optimalWidth - canvas.width : 0, 0, oc.width * 0.5, oc.height * 0.5, 0, 0, optimalWidth, optimalHeight);
                                            }
                                            m.removeClass('fi-loading').html('<img src="' + canvas.toDataURL("image/png") + '" draggable="false" />');
                                        }
                                        img.onerror = function() {
                                            html.addClass('jFiler-no-thumbnail');
                                            m.removeClass('fi-loading')
                                                .html(f._thumbCreator.generateIcon(opts));
                                        }
                                        img.src = e.target.result;
                                    } else {
                                        m.removeClass('fi-loading').html('<img src="' + e.target.result + '" draggable="false" />');
                                    }
                                }
                                y.readAsDataURL(file);
                            } else {
                                var g = f._thumbCreator.generateIcon(opts),
                                    m = html.find('.jFiler-item-thumb-image.fi-loading');
                                html.addClass('jFiler-no-thumbnail');
                                m.removeClass('fi-loading')
                                    .html(g);
                            }
                        },
                        generateIcon: function(obj) {
                            var m = new Array(3);
                            if (obj && obj.type && obj.type[0] && obj.extension) {
                                switch (obj.type[0]) {
                                    case "image":
                                        m[0] = "f-image";
                                        m[1] = "<i class=\"icon-jfi-file-image\"></i>"
                                        break;
                                    case "video":
                                        m[0] = "f-video";
                                        m[1] = "<i class=\"icon-jfi-file-video\"></i>"
                                        break;
                                    case "audio":
                                        m[0] = "f-audio";
                                        m[1] = "<i class=\"icon-jfi-file-audio\"></i>"
                                        break;
                                    default:
                                        m[0] = "f-file f-file-ext-" + obj.extension;
                                        m[1] = (obj.extension.length > 0 ? "." + obj.extension : "");
                                        m[2] = 1
                                }
                            } else {
                                m[0] = "f-file";
                                m[1] = (obj.extension && obj.extension.length > 0 ? "." + obj.extension : "");
                                m[2] = 1
                            }
                            var el = '<span class="jFiler-icon-file ' + m[0] + '">' + m[1] + '</span>';
                            if (m[2] == 1) {
                                var c = f._assets.text2Color(obj.extension);
                                if (c) {
                                    var j = $(el)
                                        .appendTo("body");

                                    j.css('background-color', f._assets.text2Color(obj.extension));
                                    el = j.prop('outerHTML');
                                    j.remove();
                                }
                            }
                            return el;
                        },
                        _box: function(params) {
                            if (n.beforeShow != null && typeof n.beforeShow == "function" ? !n.beforeShow(f.files, l, p, o, s) : false) {
                                return false
                            }
                            if (l.length < 1) {
                                if (n.appendTo) {
                                    var appendTo = $(n.appendTo);
                                } else {
                                    var appendTo = p;
                                }
                                appendTo.find('.jFiler-items')
                                    .remove();
                                l = $('<div class="jFiler-items jFiler-row"></div>');
                                s.prop("jFiler").listEl = l;
                                l.append(f._assets.textParse(n.templates.box))
                                    .appendTo(appendTo);
                                l.on('click', n.templates._selectors.remove, function(e) {
                                    e.preventDefault();
                                    var m = [params ? params.remove.event : e, params ? params.remove.el : $(this).closest(n.templates._selectors.item)],
                                        c = function(a) {
                                            f._remove(m[0], m[1]);
                                        };
                                    if (n.templates.removeConfirmation) {
                                        n.dialogs.confirm(n.captions.removeConfirmation, c);
                                    } else {
                                        c();
                                    }
                                });
                            }
                            for (var i = 0; i < f.files.length; i++) {
                                if (!f.files[i]._appended) f.files[i]._choosed = true;
                                f._addToMemory(i);
                                f._thumbCreator.create(i);
                            }
                        }
                    },
                    _upload: function(i) {
                        var c = f._itFl[i],
                            el = c.html,
                            formData = new FormData();
                        formData.append(s.attr('name'), c.file, (c.file.name ? c.file.name : false));
                        if (n.uploadFile.data != null && $.isPlainObject(typeof(n.uploadFile.data) == "function" ? n.uploadFile.data(c.file) : n.uploadFile.data)) {
                            for (var k in n.uploadFile.data) {
                                formData.append(k, n.uploadFile.data[k])
                            }
                        }

                        f._ajax.send(el, formData, c);
                    },
                    _ajax: {
                        send: function(el, formData, c) {
                            c.ajax = $.ajax({
                                url: n.uploadFile.url,
                                data: formData,
                                type: n.uploadFile.type,
                                enctype: n.uploadFile.enctype,
                                xhr: function() {
                                    var myXhr = $.ajaxSettings.xhr();
                                    if (myXhr.upload) {
                                        myXhr.upload.addEventListener("progress", function(e) {
                                            f._ajax.progressHandling(e, el)
                                        }, false)
                                    }
                                    return myXhr
                                },
                                complete: function(jqXHR, textStatus) {
                                    c.ajax = false;
                                    f._ajFc++;

                                    if (n.uploadFile.synchron && c.id + 1 < f._itFl.length) {
                                        f._upload(c.id + 1);
                                    }

                                    if (f._ajFc >= f.files.length) {
                                        f._ajFc = 0;
                                        s.get(0).value = "";
                                        n.uploadFile.onComplete != null && typeof n.uploadFile.onComplete == "function" ? n.uploadFile.onComplete(l, p, o, s, jqXHR, textStatus) : null;
                                    }
                                },
                                beforeSend: function(jqXHR, settings) {
                                    return n.uploadFile.beforeSend != null && typeof n.uploadFile.beforeSend == "function" ? n.uploadFile.beforeSend(el, l, p, o, s, c.id, jqXHR, settings) : true;
                                },
                                success: function(data, textStatus, jqXHR) {
                                    c.uploaded = true;
                                    n.uploadFile.success != null && typeof n.uploadFile.success == "function" ? n.uploadFile.success(data, el, l, p, o, s, c.id, textStatus, jqXHR) : null
                                },
                                error: function(jqXHR, textStatus, errorThrown) {
                                    c.uploaded = false;
                                    n.uploadFile.error != null && typeof n.uploadFile.error == "function" ? n.uploadFile.error(el, l, p, o, s, c.id, jqXHR, textStatus, errorThrown) : null
                                },
                                statusCode: n.uploadFile.statusCode,
                                cache: false,
                                contentType: false,
                                processData: false
                            });
                            return c.ajax;
                        },
                        progressHandling: function(e, el) {
                            if (e.lengthComputable) {
                                var t = Math.round(e.loaded * 100 / e.total)
                                    .toString();
                                n.uploadFile.onProgress != null && typeof n.uploadFile.onProgress == "function" ? n.uploadFile.onProgress(t, el, l, p, o, s) : null;
                                el.find('.jFiler-jProgressBar')
                                    .find(n.templates._selectors.progressBar)
                                    .css("width", t + "%")
                            }
                        }
                    },
                    _dragDrop: {
                        dragEnter: function(e) {
                            clearTimeout(f._dragDrop._drt);
                            n.dragDrop.dragContainer.addClass('dragged');
                            f._set('feedback', n.captions.drop);
                            n.dragDrop.dragEnter != null && typeof n.dragDrop.dragEnter == "function" ? n.dragDrop.dragEnter(e, o, s, p) : null;
                        },
                        dragLeave: function(e) {
                            clearTimeout(f._dragDrop._drt);
                            f._dragDrop._drt = setTimeout(function(e) {
                                if (!f._dragDrop._dragLeaveCheck(e)) {
                                    f._dragDrop.dragLeave(e);
                                    return false;
                                }
                                n.dragDrop.dragContainer.removeClass('dragged');
                                f._set('feedback', n.captions.feedback);
                                n.dragDrop.dragLeave != null && typeof n.dragDrop.dragLeave == "function" ? n.dragDrop.dragLeave(e, o, s, p) : null;
                            }, 100, e);
                        },
                        drop: function(e) {
                            clearTimeout(f._dragDrop._drt);
                            n.dragDrop.dragContainer.removeClass('dragged');
                            f._set('feedback', n.captions.feedback);
                            if (e && e.originalEvent && e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files && e.originalEvent.dataTransfer.files.length > 0) {
                                f._onChange(e, e.originalEvent.dataTransfer.files);
                            }
                            n.dragDrop.drop != null && typeof n.dragDrop.drop == "function" ? n.dragDrop.drop(e.originalEvent.dataTransfer.files, e, o, s, p) : null;
                        },
                        _dragLeaveCheck: function(e) {
                            var related = $(e.currentTarget),
                                insideEls = 0;
                            if (!related.is(o)) {
                                insideEls = o.find(related).length;

                                if (insideEls > 0) {
                                    debugger;
                                    return false;
                                }
                            }
                            return true;
                        }
                    },
                    _clipboardPaste: function(e, fromDrop) {
                        if (!fromDrop && (!e.originalEvent.clipboardData && !e.originalEvent.clipboardData.items)) {
                            return
                        }
                        if (fromDrop && (!e.originalEvent.dataTransfer && !e.originalEvent.dataTransfer.items)) {
                            return
                        }
                        if (f._clPsePre) {
                            return
                        }
                        var items = (fromDrop ? e.originalEvent.dataTransfer.items : e.originalEvent.clipboardData.items),
                            b64toBlob = function(b64Data, contentType, sliceSize) {
                                contentType = contentType || '';
                                sliceSize = sliceSize || 512;
                                var byteCharacters = atob(b64Data);
                                var byteArrays = [];
                                for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
                                    var slice = byteCharacters.slice(offset, offset + sliceSize);
                                    var byteNumbers = new Array(slice.length);
                                    for (var i = 0; i < slice.length; i++) {
                                        byteNumbers[i] = slice.charCodeAt(i);
                                    }
                                    var byteArray = new Uint8Array(byteNumbers);
                                    byteArrays.push(byteArray);
                                }
                                var blob = new Blob(byteArrays, {
                                    type: contentType
                                });
                                return blob;
                            };
                        if (items) {
                            for (var i = 0; i < items.length; i++) {
                                if (items[i].type.indexOf("image") !== -1 || items[i].type.indexOf("text/uri-list") !== -1) {
                                    if (fromDrop) {
                                        try {
                                            window.atob(e.originalEvent.dataTransfer.getData("text/uri-list")
                                                .toString()
                                                .split(',')[1]);
                                        } catch (e) {
                                            return;
                                        }
                                    }
                                    var blob = (fromDrop ? b64toBlob(e.originalEvent.dataTransfer.getData("text/uri-list")
                                        .toString()
                                        .split(',')[1], "image/png") : items[i].getAsFile());
                                    blob.name = Math.random()
                                        .toString(36)
                                        .substring(5);
                                    blob.name += blob.type.indexOf("/") != -1 ? "." + blob.type.split("/")[1].toString()
                                        .toLowerCase() : ".png";
                                    f._onChange(e, [blob]);
                                    f._clPsePre = setTimeout(function() {
                                        delete f._clPsePre
                                    }, 1000);
                                }
                            }
                        }
                    },
                    _onSelect: function(i) {
                        if (n.uploadFile && !$.isEmptyObject(n.uploadFile)) {
                            if (!n.uploadFile.synchron || (n.uploadFile.synchron && $.grep(f._itFl, function(a) {
                                    return a.ajax
                                }).length == 0)) {
                                f._upload(f._itFc.id)
                            }
                        }
                        if (f.files[i]._pendRemove) {
                            f._itFc.html.hide();
                            f._remove(null, {
                                binded: true,
                                data: {
                                    id: f._itFc.id
                                }
                            });
                        }
                        n.onSelect != null && typeof n.onSelect == "function" ? n.onSelect(f.files[i], f._itFc.html, l, p, o, s) : null;
                        if (i + 1 >= f.files.length) {
                            n.afterShow != null && typeof n.afterShow == "function" ? n.afterShow(l, p, o, s) : null
                        }
                    },
                    _onChange: function(e, d) {
                        if (!d) {
                            if (!s.get(0)
                                .files || typeof s.get(0)
                                .files == "undefined" || s.get(0)
                                .files.length == 0) {
                                if (!n.uploadFile && !n.addMore) {
                                    f._set('input', '');
                                    f._clear();
                                }
                                return false
                            }
                            f.files = s.get(0)
                                .files;
                        } else {
                            if (!d || d.length == 0) {
                                f._set('input', '');
                                f._clear();
                                return false
                            }
                            f.files = d;
                        }
                        if (!n.uploadFile && !n.addMore) {
                            f._reset(true);
                        }
                        s.prop("jFiler")
                            .files = f.files;
                        if (!f._filesCheck() || (n.beforeSelect != null && typeof n.beforeSelect == "function" ? !n.beforeSelect(f.files, l, p, o, s) : false)) {
                            f._set('input', '');
                            f._clear();
                            if (n.addMore && sl.length > 0) {
                                f._unbindInput(true);
                                sl[sl.length - 1].remove();
                                sl.splice(sl.length - 1, 1);
                                s = sl.length > 0 ? sl[sl.length - 1] : $(r);
                                f._bindInput();
                            }
                            return false
                        }
                        f._set('feedback', f.files.length + f._itFl.length + ' ' + n.captions.feedback2);
                        if (n.showThumbs) {
                            f._thumbCreator._box();
                        } else {
                            for (var i = 0; i < f.files.length; i++) {
                                f.files[i]._choosed = true;
                                f._addToMemory(i);
                                f._onSelect(i);
                            }
                        }
                    },
                    _append: function(e, data) {
                        var files = (!data ? false : data.files);
                        if (!files || files.length <= 0) {
                            return;
                        }
                        f.files = files;
                        s.prop("jFiler")
                            .files = f.files;
                        if (n.showThumbs) {
                            for (var i = 0; i < f.files.length; i++) {
                                f.files[i]._appended = true;
                            }
                            f._thumbCreator._box();
                        }
                    },
                    _getList: function(e, data) {
                        var files = (!data ? false : data.files);
                        if (!files || files.length <= 0) {
                            return;
                        }
                        f.files = files;
                        s.prop("jFiler")
                            .files = f.files;
                        if (n.showThumbs) {
                            var returnData = [];
                            for (var i = 0; i < f.files.length; i++) {
                                f.files[i].forList = true;
                                returnData.push(f._thumbCreator.create(i));
                            }
                            if (data.callback) {
                                data.callback(returnData, l, p, o, s);
                            }
                        }
                    },
                    _retryUpload: function(e, data) {
                        var id = parseInt(typeof data == "object" ? data.attr("data-jfiler-index") : data),
                            obj = f._itFl.filter(function(value, key) {
                                return value.id == id;
                            });
                        if (obj.length > 0) {
                            if (n.uploadFile && !$.isEmptyObject(n.uploadFile) && !obj[0].uploaded) {
                                f._itFc = obj[0];
                                s.prop("jFiler")
                                    .current_file = f._itFc;
                                f._upload(id);
                                return true;
                            }
                        } else {
                            return false;
                        }
                    },
                    _remove: function(e, el) {
                        if (el.binded) {
                            if (typeof(el.data.id) != "undefined") {
                                el = l.find(n.templates._selectors.item + "[data-jfiler-index='" + el.data.id + "']");
                                if (el.length == 0) {
                                    return false
                                }
                            }
                            if (el.data.el) {
                                el = el.data.el;
                            }
                        }
                        var excl_input = function(val) {
                                var input = p.find("input[name^='jfiler-items-exclude-']:hidden")
                                    .first();

                                if (input.length == 0) {
                                    input = $('<input type="hidden" name="jfiler-items-exclude-' + (n.excludeName ? n.excludeName : (s.attr("name")
                                        .slice(-2) != "[]" ? s.attr("name") : s.attr("name")
                                        .substring(0, s.attr("name")
                                            .length - 2)) + "-" + t) + '">');
                                    input.appendTo(p);
                                }

                                if (val && $.isArray(val)) {
                                    val = JSON.stringify(val);
                                    input.val(val);
                                }
                            },
                            callback = function(el, id) {
                                var item = f._itFl[id],
                                    val = [];

                                if (item.file._choosed || item.file._appended || item.uploaded) {
                                    f._itFr.push(item);

                                    var m = f._itFl.filter(function(a) {
                                        return a.file.name == item.file.name;
                                    });

                                    for (var i = 0; i < f._itFr.length; i++) {
                                        if (n.addMore && f._itFr[i] == item && m.length > 0) {
                                            f._itFr[i].remove_name = m.indexOf(item) + "://" + f._itFr[i].file.name;
                                        }
                                        val.push(f._itFr[i].remove_name ? f._itFr[i].remove_name : f._itFr[i].file.name);
                                    }
                                }
                                excl_input(val);
                                f._itFl.splice(id, 1);
                                if (f._itFl.length < 1) {
                                    f._reset();
                                    f._clear();
                                } else {
                                    f._set('feedback', f._itFl.length + ' ' + n.captions.feedback2);
                                }
                                el.fadeOut("fast", function() {
                                    $(this)
                                        .remove();
                                });
                            };

                        var attrId = el.get(0)
                            .jfiler_id || el.attr('data-jfiler-index'),
                            id = null;

                        for (var key in f._itFl) {
                            if (key === 'length' || !f._itFl.hasOwnProperty(key)) continue;
                            if (f._itFl[key].id == attrId) {
                                id = key;
                            }
                        }
                        if (!f._itFl.hasOwnProperty(id)) {
                            return false
                        }
                        if (f._itFl[id].ajax) {
                            f._itFl[id].ajax.abort();
                            callback(el, id);
                            return;
                        }
                        if (n.onRemove != null && typeof n.onRemove == "function" ? n.onRemove(el, f._itFl[id].file, id, l, p, o, s) !== false : true) {
                            callback(el, id);
                        }
                    },
                    _addToMemory: function(i) {
                        f._itFl.push({
                            id: f._itFl.length,
                            file: f.files[i],
                            html: $(),
                            ajax: false,
                            uploaded: false,
                        });
                        if (n.addMore || f.files[i]._appended) f._itFl[f._itFl.length - 1].input = s;
                        f._itFc = f._itFl[f._itFl.length - 1];
                        s.prop("jFiler")
                            .files_list = f._itFl;
                        s.prop("jFiler")
                            .current_file = f._itFc;
                    },
                    _assets: {
                        bytesToSize: function(bytes) {
                            if (bytes == 0) return '0 Byte';
                            var k = 1000;
                            var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
                            var i = Math.floor(Math.log(bytes) / Math.log(k));
                            return (bytes / Math.pow(k, i))
                                .toPrecision(3) + ' ' + sizes[i];
                        },
                        hasAttr: function(attr, el) {
                            var el = (!el ? s : el),
                                a = el.attr(attr);
                            if (!a || typeof a == "undefined") {
                                return false;
                            } else {
                                return true;
                            }
                        },
                        getIcon: function(ext, type) {
                            var types = ["audio", "image", "text", "video"];
                            if ($.inArray(type, types) > -1) {
                                return '<i class="icon-jfi-file-' + type + ' jfi-file-ext-' + ext + '"></i>';
                            }
                            return '<i class="icon-jfi-file-o jfi-file-type-' + type + ' jfi-file-ext-' + ext + '"></i>';
                        },
                        textParse: function(text, opts) {
                            opts = $.extend({}, {
                                limit: n.limit,
                                maxSize: n.maxSize,
                                fileMaxSize: n.fileMaxSize,
                                extensions: n.extensions ? n.extensions.join(',') : null,
                            }, (opts && $.isPlainObject(opts) ? opts : {}), n.options);
                            switch (typeof(text)) {
                                case "string":
                                    return text.replace(/\{\{fi-(.*?)\}\}/g, function(match, a) {
                                        a = a.replace(/ /g, '');
                                        if (a.match(/(.*?)\|limitTo\:(\d+)/)) {
                                            return a.replace(/(.*?)\|limitTo\:(\d+)/, function(match, a, b) {
                                                var a = (opts[a] ? opts[a] : ""),
                                                    str = a.substring(0, b);
                                                str = (a.length > str.length ? str.substring(0, str.length - 3) + "..." : str);
                                                return str;
                                            });
                                        } else {
                                            return (opts[a] ? opts[a] : "");
                                        }
                                    });
                                    break;
                                case "function":
                                    return text(opts);
                                    break;
                                default:
                                    return text;
                            }
                        },
                        text2Color: function(str) {
                            if (!str || str.length == 0) {
                                return false
                            }
                            for (var i = 0, hash = 0; i < str.length; hash = str.charCodeAt(i++) + ((hash << 5) - hash));
                            for (var i = 0, colour = "#"; i < 3; colour += ("00" + ((hash >> i++ * 2) & 0xFF)
                                    .toString(16))
                                .slice(-2));
                            return colour;
                        }
                    },
                    files: null,
                    _itFl: [],
                    _itFc: null,
                    _itFr: [],
                    _itPl: [],
                    _ajFc: 0
                }

            s.on("filer.append", function(e, data) {
                f._append(e, data)
            }).on("filer.remove", function(e, data) {
                data.binded = true;
                f._remove(e, data);
            }).on("filer.reset", function(e) {
                f._reset();
                f._clear();
                return true;
            }).on("filer.generateList", function(e, data) {
                return f._getList(e, data)
            }).on("filer.retry", function(e, data) {
                return f._retryUpload(e, data)
            });

            f.init();

            return this;
        });
    };
    $.fn.filer.defaults = {
        limit: null,
        maxSize: null,
        fileMaxSize: null,
        extensions: null,
        changeInput: true,
        showThumbs: false,
        appendTo: null,
        theme: 'default',
        templates: {
            box: '<ul class="jFiler-items-list jFiler-items-default"></ul>',
            item: '<li class="jFiler-item"><div class="jFiler-item-container"><div class="jFiler-item-inner"><div class="jFiler-item-icon pull-left">{{fi-icon}}</div><div class="jFiler-item-info pull-left"><div class="jFiler-item-title" title="{{fi-name}}">{{fi-name | limitTo:30}}</div><div class="jFiler-item-others"><span>size: {{fi-size2}}</span><span>type: {{fi-extension}}</span><span class="jFiler-item-status">{{fi-progressBar}}</span></div><div class="jFiler-item-assets"><ul class="list-inline"><li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li></ul></div></div></div></div></li>',
            itemAppend: '<li class="jFiler-item"><div class="jFiler-item-container"><div class="jFiler-item-inner"><div class="jFiler-item-icon pull-left">{{fi-icon}}</div><div class="jFiler-item-info pull-left"><div class="jFiler-item-title">{{fi-name | limitTo:35}}</div><div class="jFiler-item-others"><span>size: {{fi-size2}}</span><span>type: {{fi-extension}}</span><span class="jFiler-item-status"></span></div><div class="jFiler-item-assets"><ul class="list-inline"><li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li></ul></div></div></div></div></li>',
            progressBar: '<div class="bar"></div>',
            itemAppendToEnd: false,
            removeConfirmation: true,
            canvasImage: true,
            _selectors: {
                list: '.jFiler-items-list',
                item: '.jFiler-item',
                progressBar: '.bar',
                remove: '.jFiler-item-trash-action'
            }
        },
        files: null,
        uploadFile: null,
        dragDrop: null,
        addMore: false,
        allowDuplicates: false,
        clipBoardPaste: true,
        excludeName: null,
        beforeRender: null,
        afterRender: null,
        beforeShow: null,
        beforeSelect: null,
        onSelect: null,
        onFileCheck: null,
        afterShow: null,
        onRemove: null,
        onEmpty: null,
        options: null,
        dialogs: {
            alert: function(text) {
                return alert(text);
            },
            confirm: function(text, callback) {
                confirm(text) ? callback() : null;
            }
        },
        captions: {
            button: "Choose Files",
            feedback: "Choose files To Upload",
            feedback2: "files were chosen",
            drop: "Drop file here to Upload",
            removeConfirmation: "Are you sure you want to remove this file?",
            errors: {
                filesLimit: "Only {{fi-limit}} files are allowed to be uploaded.",
                filesType: "Only Images are allowed to be uploaded.",
                filesSize: "{{fi-name}} is too large! Please upload file up to {{fi-fileMaxSize}} MB.",
                filesSizeAll: "Files you've choosed are too large! Please upload files up to {{fi-maxSize}} MB.",
                folderUpload: "You are not allowed to upload folders."
            }
        }
    }
})(jQuery);
