/*!
 *
 * Jquery Mapael - Dynamic maps jQuery plugin (based on raphael.js)
 * Requires jQuery, raphael.js and jquery.mousewheel
 *
 * Version: 2.1.0
 *
 * Copyright (c) 2017 Vincent Brouté (https://www.vincentbroute.fr/mapael)
 * Licensed under the MIT license (http://www.opensource.org/licenses/mit-license.php).
 *
 * Thanks to Indigo744
 *
 */
(function (factory) {
    if (typeof exports === 'object') {
        // CommonJS
        module.exports = factory(require('jquery'), require('raphael'), require('jquery-mousewheel'));
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'raphael', 'mousewheel'], factory);
    } else {
        // Browser globals
        factory(jQuery, Raphael, jQuery.fn.mousewheel);
    }
}(function ($, Raphael, mousewheel, undefined) {

    "use strict";

    // The plugin name (used on several places)
    var pluginName = "mapael";

    // Version number of jQuery Mapael. See http://semver.org/ for more information.
    var version = "2.1.0";

    /*
     * Mapael constructor
     * Init instance vars and call init()
     * @param container the DOM element on which to apply the plugin
     * @param options the complete options to use
     */
    var Mapael = function (container, options) {
        var self = this;

        // the global container (DOM element object)
        self.container = container;

        // the global container (jQuery object)
        self.$container = $(container);

        // the global options
        self.options = self.extendDefaultOptions(options);

        // zoom TimeOut handler (used to set and clear)
        self.zoomTO = 0;

        // zoom center coordinate (set at touchstart)
        self.zoomCenterX = 0;
        self.zoomCenterY = 0;

        // Zoom pinch (set at touchstart and touchmove)
        self.previousPinchDist = 0;

        // Zoom data
        self.zoomData = {
            zoomLevel: 0,
            zoomX: 0,
            zoomY: 0,
            panX: 0,
            panY: 0
        };

        // resize TimeOut handler (used to set and clear)
        self.resizeTO = 0;

        // Panning: tell if panning action is in progress
        self.panning = false;

        // Panning TimeOut handler (used to set and clear)
        self.panningTO = 0;

        // Animate view box Interval handler (used to set and clear)
        self.animationIntervalID = null;

        // Map subcontainer jQuery object
        self.$map = $("." + self.options.map.cssClass, self.container);

        // Save initial HTML content (used by destroy method)
        self.initialMapHTMLContent = self.$map.html();

        // Allow to store legend containers and initial contents (used by destroy method)
        self.createdLegends = {};

        // The tooltip jQuery object
        self.$tooltip = {};

        // The paper Raphael object
        self.paper = {};

        // The areas object list
        self.areas = {};

        // The plots object list
        self.plots = {};

        // The links object list
        self.links = {};

        // The map configuration object (taken from map file)
        self.mapConf = {};

        // Let's start the initialization
        self.init();
    };

    /*
     * Mapael Prototype
     * Defines all methods and properties needed by Mapael
     * Each mapael object inherits their properties and methods from this prototype
     */
    Mapael.prototype = {

        /*
         * Version number
         */
        version: version,

        /*
         * Initialize the plugin
         * Called by the constructor
         */
        init: function () {
            var self = this;

            // Init check for class existence
            if (self.options.map.cssClass === "" || $("." + self.options.map.cssClass, self.container).length === 0) {
                throw new Error("The map class `" + self.options.map.cssClass + "` doesn't exists");
            }

            // Create the tooltip container
            self.$tooltip = $("<div>").addClass(self.options.map.tooltip.cssClass).css("display", "none");

            // Get the map container, empty it then append tooltip
            self.$map.empty().append(self.$tooltip);

            // Get the map from $.mapael or $.fn.mapael (backward compatibility)
            if ($[pluginName] && $[pluginName].maps && $[pluginName].maps[self.options.map.name]) {
                // Mapael version >= 2.x
                self.mapConf = $[pluginName].maps[self.options.map.name];
            } else if ($.fn[pluginName] && $.fn[pluginName].maps && $.fn[pluginName].maps[self.options.map.name]) {
                // Mapael version <= 1.x - DEPRECATED
                self.mapConf = $.fn[pluginName].maps[self.options.map.name];
                if (window.console && window.console.warn) {
                    window.console.warn("Extending $.fn.mapael is deprecated (map '" + self.options.map.name + "')");
                }
            } else {
                throw new Error("Unknown map '" + self.options.map.name + "'");
            }

            // Create Raphael paper
            self.paper = new Raphael(self.$map[0], self.mapConf.width, self.mapConf.height);

            // issue #135: Check for Raphael bug on text element boundaries
            if (self.isRaphaelBBoxBugPresent() === true) {
                self.destroy();
                throw new Error("Can't get boundary box for text (is your container hidden? See #135)");
            }

            // add plugin class name on element
            self.$container.addClass(pluginName);

            if (self.options.map.tooltip.css) self.$tooltip.css(self.options.map.tooltip.css);
            self.paper.setViewBox(0, 0, self.mapConf.width, self.mapConf.height, false);

            // Handle map size
            if (self.options.map.width) {
                // NOT responsive: map has a fixed width
                self.paper.setSize(self.options.map.width, self.mapConf.height * (self.options.map.width / self.mapConf.width));

                // Create the legends for plots taking into account the scale of the map
                self.createLegends("plot", self.plots, (self.options.map.width / self.mapConf.width));
            } else {
                // Responsive: handle resizing of the map
                self.handleMapResizing();
            }

            // Draw map areas
            $.each(self.mapConf.elems, function (id) {
                var elemOptions = self.getElemOptions(
                    self.options.map.defaultArea,
                    (self.options.areas[id] ? self.options.areas[id] : {}),
                    self.options.legend.area
                );
                self.areas[id] = {"mapElem": self.paper.path(self.mapConf.elems[id]).attr(elemOptions.attrs)};
            });

            // Hook that allows to add custom processing on the map
            if (self.options.map.beforeInit) self.options.map.beforeInit(self.$container, self.paper, self.options);

            // Init map areas in a second loop (prevent texts to be hidden by map elements)
            $.each(self.mapConf.elems, function (id) {
                var elemOptions = self.getElemOptions(
                    self.options.map.defaultArea,
                    (self.options.areas[id] ? self.options.areas[id] : {}),
                    self.options.legend.area
                );
                self.initElem(self.areas[id], elemOptions, id);
            });

            // Draw links
            self.links = self.drawLinksCollection(self.options.links);

            // Draw plots
            $.each(self.options.plots, function (id) {
                self.plots[id] = self.drawPlot(id);
            });

            // Attach zoom event
            self.$container.on("zoom." + pluginName, function (e, zoomOptions) {
                self.onZoomEvent(e, zoomOptions);
            });

            if (self.options.map.zoom.enabled) {
                // Enable zoom
                self.initZoom(self.mapConf.width, self.mapConf.height, self.options.map.zoom);
            }

            // Set initial zoom
            if (self.options.map.zoom.init !== undefined) {
                if (self.options.map.zoom.init.animDuration === undefined) {
                    self.options.map.zoom.init.animDuration = 0;
                }
                self.$container.trigger("zoom", self.options.map.zoom.init);
            }

            // Create the legends for areas
            self.createLegends("area", self.areas, 1);

            // Attach update event
            self.$container.on("update." + pluginName, function (e, opt) {
                self.onUpdateEvent(e, opt);
            });

            // Attach showElementsInRange event
            self.$container.on("showElementsInRange." + pluginName, function (e, opt) {
                self.onShowElementsInRange(e, opt);
            });

            // Hook that allows to add custom processing on the map
            if (self.options.map.afterInit) self.options.map.afterInit(self.$container, self.paper, self.areas, self.plots, self.options);

            $(self.paper.desc).append(" and Mapael " + self.version + " (https://www.vincentbroute.fr/mapael/)");
        },

        /*
         * Destroy mapael
         * This function effectively detach mapael from the container
         *   - Set the container back to the way it was before mapael instanciation
         *   - Remove all data associated to it (memory can then be free'ed by browser)
         *
         * This method can be call directly by user:
         *     $(".mapcontainer").data("mapael").destroy();
         *
         * This method is also automatically called if the user try to call mapael
         * on a container already containing a mapael instance
         */
        destroy: function () {
            var self = this;

            // Detach all event listeners attached to the container
            self.$container.off("." + pluginName);
            self.$map.off("." + pluginName);

            // Detach the global resize event handler
            if (self.onResizeEvent) $(window).off("resize." + pluginName, self.onResizeEvent);

            // Empty the container (this will also detach all event listeners)
            self.$map.empty();

            // Replace initial HTML content
            self.$map.html(self.initialMapHTMLContent);

            // Empty legend containers and replace initial HTML content
            for (var id in self.createdLegends) {
                self.createdLegends[id].container.empty();
                self.createdLegends[id].container.html(self.createdLegends[id].initialHTMLContent);
            }

            // Remove mapael class
            self.$container.removeClass(pluginName);

            // Remove the data
            self.$container.removeData(pluginName);

            // Remove all internal reference
            self.container = undefined;
            self.$container = undefined;
            self.options = undefined;
            self.paper = undefined;
            self.$map = undefined;
            self.$tooltip = undefined;
            self.mapConf = undefined;
            self.areas = undefined;
            self.plots = undefined;
            self.links = undefined;
        },

        handleMapResizing: function () {
            var self = this;

            // onResizeEvent: call when the window element trigger the resize event
            // We create it inside this function (and not in the prototype) in order to have a closure
            // Otherwise, in the prototype, 'this' when triggered is *not* the mapael object but the global window
            self.onResizeEvent = function () {
                // Clear any previous setTimeout (avoid too much triggering)
                clearTimeout(self.resizeTO);
                // setTimeout to wait for the user to finish its resizing
                self.resizeTO = setTimeout(function () {
                    self.$map.trigger("resizeEnd");
                }, 150);
            };

            // Attach resize handler
            $(window).on("resize." + pluginName, self.onResizeEvent);

            // Attach resize end handler, and call it once
            self.$map.on("resizeEnd." + pluginName, function (e, isInit) {
                var containerWidth = self.$map.width();

                if (self.paper.width != containerWidth) {
                    var newScale = containerWidth / self.mapConf.width;
                    // Set new size
                    self.paper.setSize(containerWidth, self.mapConf.height * newScale);

                    // Create plots legend again to take into account the new scale
                    if (isInit || self.options.legend.redrawOnResize) {
                        self.createLegends("plot", self.plots, newScale);
                    }
                }
            }).trigger("resizeEnd", [true]);
        },

        /*
         * Extend the user option with the default one
         * @param options the user options
         * @return new options object
         */
        extendDefaultOptions: function (options) {

            // Extend default options with user options
            options = $.extend(true, {}, Mapael.prototype.defaultOptions, options);

            // Extend legend default options
            $.each(['area', 'plot'], function (key, type) {
                if ($.isArray(options.legend[type])) {
                    for (var i = 0; i < options.legend[type].length; ++i)
                        options.legend[type][i] = $.extend(true, {}, Mapael.prototype.legendDefaultOptions[type], options.legend[type][i]);
                } else {
                    options.legend[type] = $.extend(true, {}, Mapael.prototype.legendDefaultOptions[type], options.legend[type]);
                }
            });

            return options;
        },

        /*
         * Init the element "elem" on the map (drawing, setting attributes, events, tooltip, ...)
         */
        initElem: function (elem, elemOptions, id) {
            var self = this;
            var bbox = {};
            var textPosition = {};

            // Assign value attribute to element
            if (elemOptions.value !== undefined){
                elem.value = elemOptions.value;
            }

            // Init the label related to the element
            if (elemOptions.text && elemOptions.text.content !== undefined) {
                // Set a text label in the area
                bbox = elem.mapElem.getBBox();
                textPosition = self.getTextPosition(bbox, elemOptions.text.position, elemOptions.text.margin);
                elemOptions.text.attrs["text-anchor"] = textPosition.textAnchor;
                elem.textElem = self.paper.text(textPosition.x, textPosition.y, elemOptions.text.content).attr(elemOptions.text.attrs);
                $(elem.textElem.node).attr("data-id", id);
            }

            // Set user event handlers
            if (elemOptions.eventHandlers) self.setEventHandlers(id, elemOptions, elem.mapElem, elem.textElem);

            // Set hover option for mapElem
            self.setHoverOptions(elem.mapElem, elemOptions.attrs, elemOptions.attrsHover);

            // Set hover option for textElem
            if (elem.textElem) self.setHoverOptions(elem.textElem, elemOptions.text.attrs, elemOptions.text.attrsHover);

            // Set hover behavior only if attrsHover is set for area or for text
            if (($.isEmptyObject(elemOptions.attrsHover) === false) ||
                (elem.textElem && $.isEmptyObject(elemOptions.text.attrsHover) === false)) {
                // Set hover behavior
                self.setHover(elem.mapElem, elem.textElem);
            }

            // Init the tooltip
            if (elemOptions.tooltip) {
                elem.mapElem.tooltip = elemOptions.tooltip;
                self.setTooltip(elem.mapElem);

                if (elemOptions.text && elemOptions.text.content !== undefined) {
                    elem.textElem.tooltip = elemOptions.tooltip;
                    self.setTooltip(elem.textElem);
                }
            }

            // Init the link
            if (elemOptions.href) {
                elem.mapElem.href = elemOptions.href;
                elem.mapElem.target = elemOptions.target;
                self.setHref(elem.mapElem);

                if (elemOptions.text && elemOptions.text.content !== undefined) {
                    elem.textElem.href = elemOptions.href;
                    elem.textElem.target = elemOptions.target;
                    self.setHref(elem.textElem);
                }
            }

            if (elemOptions.cssClass !== undefined) {
                $(elem.mapElem.node).addClass(elemOptions.cssClass);
            }

            $(elem.mapElem.node).attr("data-id", id);
        },

        /*
         * Init zoom and panning for the map
         * @param mapWidth
         * @param mapHeight
         * @param zoomOptions
         */
        initZoom: function (mapWidth, mapHeight, zoomOptions) {
            var self = this;
            var mousedown = false;
            var previousX = 0;
            var previousY = 0;
            var fnZoomButtons = {
                "reset": function () {
                    self.$container.trigger("zoom", {"level": 0});
                },
                "in": function () {
                    self.$container.trigger("zoom", {"level": "+1"});
                },
                "out": function () {
                    self.$container.trigger("zoom", {"level": -1});
                }
            };

            // init Zoom data
            $.extend(self.zoomData, {
                zoomLevel: 0,
                panX: 0,
                panY: 0
            });

            // init zoom buttons
            $.each(zoomOptions.buttons, function(type, opt) {
                if (fnZoomButtons[type] === undefined) throw new Error("Unknown zoom button '" + type + "'");
                // Create div with classes, contents and title (for tooltip)
                var $button = $("<div>").addClass(opt.cssClass)
                    .html(opt.content)
                    .attr("title", opt.title);
                // Assign click event
                $button.on("click." + pluginName, fnZoomButtons[type]);
                // Append to map
                self.$map.append($button);
            });

            // Update the zoom level of the map on mousewheel
            if (self.options.map.zoom.mousewheel) {
                self.$map.on("mousewheel." + pluginName, function (e) {
                    var zoomLevel = (e.deltaY > 0) ? 1 : -1;
                    var coord = self.mapPagePositionToXY(e.pageX, e.pageY);

                    self.$container.trigger("zoom", {
                        "fixedCenter": true,
                        "level": self.zoomData.zoomLevel + zoomLevel,
                        "x": coord.x,
                        "y": coord.y
                    });

                    e.preventDefault();
                });
            }

            // Update the zoom level of the map on touch pinch
            if (self.options.map.zoom.touch) {
                self.$map.on("touchstart." + pluginName, function (e) {
                    if (e.originalEvent.touches.length === 2) {
                        self.zoomCenterX = (e.originalEvent.touches[0].pageX + e.originalEvent.touches[1].pageX) / 2;
                        self.zoomCenterY = (e.originalEvent.touches[0].pageY + e.originalEvent.touches[1].pageY) / 2;
                        self.previousPinchDist = Math.sqrt(Math.pow((e.originalEvent.touches[1].pageX - e.originalEvent.touches[0].pageX), 2) + Math.pow((e.originalEvent.touches[1].pageY - e.originalEvent.touches[0].pageY), 2));
                    }
                });

                self.$map.on("touchmove." + pluginName, function (e) {
                    var pinchDist = 0;
                    var zoomLevel = 0;

                    if (e.originalEvent.touches.length === 2) {
                        pinchDist = Math.sqrt(Math.pow((e.originalEvent.touches[1].pageX - e.originalEvent.touches[0].pageX), 2) + Math.pow((e.originalEvent.touches[1].pageY - e.originalEvent.touches[0].pageY), 2));

                        if (Math.abs(pinchDist - self.previousPinchDist) > 15) {
                            var coord = self.mapPagePositionToXY(self.zoomCenterX, self.zoomCenterY);
                            zoomLevel = (pinchDist - self.previousPinchDist) / Math.abs(pinchDist - self.previousPinchDist);
                            self.$container.trigger("zoom", {
                                "fixedCenter": true,
                                "level": self.zoomData.zoomLevel + zoomLevel,
                                "x": coord.x,
                                "y": coord.y
                            });
                            self.previousPinchDist = pinchDist;
                        }
                        return false;
                    }
                });
            }

            // When the user drag the map, prevent to move the clicked element instead of dragging the map (behaviour seen with Firefox)
            self.$map.on("dragstart", function() {
                return false;
            });

            // Panning
            $("body").on("mouseup." + pluginName + (zoomOptions.touch ? " touchend." + pluginName : ""), function () {
                mousedown = false;
                setTimeout(function () {
                    self.panning = false;
                }, 50);
            });

            self.$map.on("mousedown." + pluginName + (zoomOptions.touch ? " touchstart." + pluginName : ""), function (e) {
                if (e.pageX !== undefined) {
                    mousedown = true;
                    previousX = e.pageX;
                    previousY = e.pageY;
                } else {
                    if (e.originalEvent.touches.length === 1) {
                        mousedown = true;
                        previousX = e.originalEvent.touches[0].pageX;
                        previousY = e.originalEvent.touches[0].pageY;
                    }
                }
            }).on("mousemove." + pluginName + (zoomOptions.touch ? " touchmove." + pluginName : ""), function (e) {
                var currentLevel = self.zoomData.zoomLevel;
                var pageX = 0;
                var pageY = 0;

                if (e.pageX !== undefined) {
                    pageX = e.pageX;
                    pageY = e.pageY;
                } else {
                    if (e.originalEvent.touches.length === 1) {
                        pageX = e.originalEvent.touches[0].pageX;
                        pageY = e.originalEvent.touches[0].pageY;
                    } else {
                        mousedown = false;
                    }
                }

                if (mousedown && currentLevel !== 0) {
                    var offsetX = (previousX - pageX) / (1 + (currentLevel * zoomOptions.step)) * (mapWidth / self.paper.width);
                    var offsetY = (previousY - pageY) / (1 + (currentLevel * zoomOptions.step)) * (mapHeight / self.paper.height);
                    var panX = Math.min(Math.max(0, self.paper._viewBox[0] + offsetX), (mapWidth - self.paper._viewBox[2]));
                    var panY = Math.min(Math.max(0, self.paper._viewBox[1] + offsetY), (mapHeight - self.paper._viewBox[3]));

                    if (Math.abs(offsetX) > 5 || Math.abs(offsetY) > 5) {
                        $.extend(self.zoomData, {
                            panX: panX,
                            panY: panY,
                            zoomX: panX + self.paper._viewBox[2] / 2,
                            zoomY: panY + self.paper._viewBox[3] / 2
                        });
                        self.paper.setViewBox(panX, panY, self.paper._viewBox[2], self.paper._viewBox[3]);

                        clearTimeout(self.panningTO);
                        self.panningTO = setTimeout(function () {
                            self.$map.trigger("afterPanning", {
                                x1: panX,
                                y1: panY,
                                x2: (panX + self.paper._viewBox[2]),
                                y2: (panY + self.paper._viewBox[3])
                            });
                        }, 150);

                        previousX = pageX;
                        previousY = pageY;
                        self.panning = true;
                    }
                    return false;
                }
            });
        },

        /*
         * Map a mouse position to a map position
         *      Transformation principle:
         *          ** start with (pageX, pageY) absolute mouse coordinate
         *          - Apply translation: take into accounts the map offset in the page
         *          ** from this point, we have relative mouse coordinate
         *          - Apply homothetic transformation: take into accounts initial factor of map sizing (fullWidth / actualWidth)
         *          - Apply homothetic transformation: take into accounts the zoom factor
         *          ** from this point, we have relative map coordinate
         *          - Apply translation: take into accounts the current panning of the map
         *          ** from this point, we have absolute map coordinate
         * @param pageX: mouse client coordinate on X
         * @param pageY: mouse client coordinate on Y
         * @return map coordinate {x, y}
         */
        mapPagePositionToXY: function(pageX, pageY) {
            var self = this;
            var offset = self.$map.offset();
            var initFactor = (self.options.map.width) ? (self.mapConf.width / self.options.map.width) : (self.mapConf.width / self.$map.width());
            var zoomFactor = 1 / (1 + (self.zoomData.zoomLevel * self.options.map.zoom.step));
            return {
                x: (zoomFactor * initFactor * (pageX - offset.left)) + self.zoomData.panX,
                y: (zoomFactor * initFactor * (pageY - offset.top)) + self.zoomData.panY
            };
        },

        /*
         * Zoom on the map at a specific level focused on specific coordinates
         * If no coordinates are specified, the zoom will be focused on the center of the map
         * options :
         *    "level" : level of the zoom between minLevel and maxLevel
         *    "x" or "latitude" : x coordinate or latitude of the point to focus on
         *    "y" or "longitude" : y coordinate or longitude of the point to focus on
         *    "fixedCenter" : set to true in order to preserve the position of x,y in the canvas when zoomed
         *    "animDuration" : zoom duration
         */
        onZoomEvent: function (e, zoomOptions) {
            var self = this;
            var newLevel = self.zoomData.zoomLevel;
            var panX = 0;
            var panY = 0;
            var previousZoomLevel = (1 + self.zoomData.zoomLevel * self.options.map.zoom.step);
            var zoomLevel = 0;
            var animDuration = (zoomOptions.animDuration !== undefined) ? zoomOptions.animDuration : self.options.map.zoom.animDuration;
            var offsetX = 0;
            var offsetY = 0;
            var coords = {};

            // Get user defined zoom level
            if (zoomOptions.level !== undefined) {
                if (typeof zoomOptions.level === "string") {
                    // level is a string, either "n", "+n" or "-n"
                    if ((zoomOptions.level.slice(0, 1) === '+') || (zoomOptions.level.slice(0, 1) === '-')) {
                        // zoomLevel is relative
                        newLevel = self.zoomData.zoomLevel + parseInt(zoomOptions.level);
                    } else {
                        // zoomLevel is absolute
                        newLevel = parseInt(zoomOptions.level);
                    }
                } else {
                    // level is integer
                    if (zoomOptions.level < 0) {
                        // zoomLevel is relative
                        newLevel = self.zoomData.zoomLevel + zoomOptions.level;
                    } else {
                        // zoomLevel is absolute
                        newLevel = zoomOptions.level;
                    }
                }
                // Make sure we stay in the boundaries
                newLevel = Math.min(Math.max(newLevel, self.options.map.zoom.minLevel), self.options.map.zoom.maxLevel);
            }

            zoomLevel = (1 + newLevel * self.options.map.zoom.step);

            if (zoomOptions.latitude !== undefined && zoomOptions.longitude !== undefined) {
                coords = self.mapConf.getCoords(zoomOptions.latitude, zoomOptions.longitude);
                zoomOptions.x = coords.x;
                zoomOptions.y = coords.y;
            }

            if (zoomOptions.x === undefined)
                zoomOptions.x = self.paper._viewBox[0] + self.paper._viewBox[2] / 2;

            if (zoomOptions.y === undefined)
                zoomOptions.y = (self.paper._viewBox[1] + self.paper._viewBox[3] / 2);

            if (newLevel === 0) {
                panX = 0;
                panY = 0;
            } else if (zoomOptions.fixedCenter !== undefined && zoomOptions.fixedCenter === true) {
                offsetX = self.zoomData.panX + ((zoomOptions.x - self.zoomData.panX) * (zoomLevel - previousZoomLevel)) / zoomLevel;
                offsetY = self.zoomData.panY + ((zoomOptions.y - self.zoomData.panY) * (zoomLevel - previousZoomLevel)) / zoomLevel;

                panX = Math.min(Math.max(0, offsetX), (self.mapConf.width - (self.mapConf.width / zoomLevel)));
                panY = Math.min(Math.max(0, offsetY), (self.mapConf.height - (self.mapConf.height / zoomLevel)));
            } else {
                panX = Math.min(Math.max(0, zoomOptions.x - (self.mapConf.width / zoomLevel) / 2), (self.mapConf.width - (self.mapConf.width / zoomLevel)));
                panY = Math.min(Math.max(0, zoomOptions.y - (self.mapConf.height / zoomLevel) / 2), (self.mapConf.height - (self.mapConf.height / zoomLevel)));
            }

            // Update zoom level of the map
            if (zoomLevel == previousZoomLevel && panX == self.zoomData.panX && panY == self.zoomData.panY) return;

            if (animDuration > 0) {
                self.animateViewBox(panX, panY, self.mapConf.width / zoomLevel, self.mapConf.height / zoomLevel, animDuration, self.options.map.zoom.animEasing);
            } else {
                self.paper.setViewBox(panX, panY, self.mapConf.width / zoomLevel, self.mapConf.height / zoomLevel);
                clearTimeout(self.zoomTO);
                self.zoomTO = setTimeout(function () {
                    self.$map.trigger("afterZoom", {
                        x1: panX,
                        y1: panY,
                        x2: (panX + (self.mapConf.width / zoomLevel)),
                        y2: (panY + (self.mapConf.height / zoomLevel))
                    });
                }, 150);
            }

            $.extend(self.zoomData, {
                zoomLevel: newLevel,
                panX: panX,
                panY: panY,
                zoomX: panX + self.paper._viewBox[2] / 2,
                zoomY: panY + self.paper._viewBox[3] / 2
            });
        },

        /*
         * Show some element in range defined by user
         * Triggered by user $(".mapcontainer").trigger("showElementsInRange", [opt]);
         *
         * @param opt the options
         *  opt.hiddenOpacity opacity for hidden element (default = 0.3)
         *  opt.animDuration animation duration in ms (default = 0)
         *  opt.afterShowRange callback
         *  opt.ranges the range to show:
         *  Example:
         *  opt.ranges = {
         *      'plot' : {
         *          0 : {                        // valueIndex
         *              'min': 1000,
         *              'max': 1200
         *          },
         *          1 : {                        // valueIndex
         *              'min': 10,
         *              'max': 12
         *          }
         *      },
         *      'area' : {
         *          {'min': 10, 'max': 20}    // No valueIndex, only an object, use 0 as valueIndex (easy case)
         *      }
         *  }
         */
        onShowElementsInRange: function(e, opt) {
            var self = this;

            // set animDuration to default if not defined
            if (opt.animDuration === undefined) {
                opt.animDuration = 0;
            }

            // set hiddenOpacity to default if not defined
            if (opt.hiddenOpacity === undefined) {
                opt.hiddenOpacity = 0.3;
            }

            // handle area
            if (opt.ranges && opt.ranges.area) {
                self.showElemByRange(opt.ranges.area, self.areas, opt.hiddenOpacity, opt.animDuration);
            }

            // handle plot
            if (opt.ranges && opt.ranges.plot) {
                self.showElemByRange(opt.ranges.plot, self.plots, opt.hiddenOpacity, opt.animDuration);
            }

            // handle link
            if (opt.ranges && opt.ranges.link) {
                self.showElemByRange(opt.ranges.link, self.links, opt.hiddenOpacity, opt.animDuration);
            }

            // Call user callback
            if (opt.afterShowRange) opt.afterShowRange();
        },

        /*
         * Show some element in range
         * @param ranges: the ranges
         * @param elems: list of element on which to check against previous range
         * @hiddenOpacity: the opacity when hidden
         * @animDuration: the animation duration
         */
        showElemByRange: function(ranges, elems, hiddenOpacity, animDuration) {
            var self = this;
            // Hold the final opacity value for all elements consolidated after applying each ranges
            // This allow to set the opacity only once for each elements
            var elemsFinalOpacity = {};

            // set object with one valueIndex to 0 if we have directly the min/max
            if (ranges.min !== undefined || ranges.max !== undefined) {
                ranges = {0: ranges};
            }

            // Loop through each valueIndex
            $.each(ranges, function (valueIndex) {
                var range = ranges[valueIndex];
                // Check if user defined at least a min or max value
                if (range.min === undefined && range.max === undefined) {
                    return true; // skip this iteration (each loop), goto next range
                }
                // Loop through each elements
                $.each(elems, function (id) {
                    var elemValue = elems[id].value;
                    // set value with one valueIndex to 0 if not object
                    if (typeof elemValue !== "object") {
                        elemValue = [elemValue];
                    }
                    // Check existence of this value index
                    if (elemValue[valueIndex] === undefined) {
                        return true; // skip this iteration (each loop), goto next element
                    }
                    // Check if in range
                    if ((range.min !== undefined && elemValue[valueIndex] < range.min) ||
                        (range.max !== undefined && elemValue[valueIndex] > range.max)) {
                        // Element not in range
                        elemsFinalOpacity[id] = hiddenOpacity;
                    } else {
                        // Element in range
                        elemsFinalOpacity[id] = 1;
                    }
                });
            });
            // Now that we looped through all ranges, we can really assign the final opacity
            $.each(elemsFinalOpacity, function (id) {
                self.setElementOpacity(elems[id], elemsFinalOpacity[id], animDuration);
            });
        },

        /*
         * Set element opacity
         * Handle elem.mapElem and elem.textElem
         * @param elem the element
         * @param opacity the opacity to apply
         * @param animDuration the animation duration to use
         */
        setElementOpacity: function(elem, opacity, animDuration) {
            // Ensure no animation is running
            //elem.mapElem.stop();
            //if (elem.textElem) elem.textElem.stop();

            // If final opacity is not null, ensure element is shown before proceeding
            if (opacity > 0) {
                elem.mapElem.show();
                if (elem.textElem) elem.textElem.show();
            }
            if (animDuration > 0) {
                // Animate attribute
                elem.mapElem.animate({"opacity": opacity}, animDuration, "linear", function () {
                    // If final attribute is 0, hide
                    if (opacity === 0) elem.mapElem.hide();
                });
                // Handle text element
                if (elem.textElem) {
                    // Animate attribute
                    elem.textElem.animate({"opacity": opacity}, animDuration, "linear", function () {
                        // If final attribute is 0, hide
                        if (opacity === 0) elem.textElem.hide();
                    });
                }
            } else {
                // Set attribute
                elem.mapElem.attr({"opacity": opacity});
                // For null opacity, hide it
                if (opacity === 0) elem.mapElem.hide();
                // Handle text elemen
                if (elem.textElem) {
                    // Set attribute
                    elem.textElem.attr({"opacity": opacity});
                    // For null opacity, hide it
                    if (opacity === 0) elem.textElem.hide();
                }
            }
        },

        /*
         *
         * Update the current map
         * Refresh attributes and tooltips for areas and plots
         * @param opt option for the refresh :
         *  opt.mapOptions: options to update for plots and areas
         *  opt.replaceOptions: whether mapsOptions should entirely replace current map options, or just extend it
         *  opt.opt.newPlots new plots to add to the map
         *  opt.newLinks new links to add to the map
         *  opt.deletePlotKeys plots to delete from the map (array, or "all" to remove all plots)
         *  opt.deleteLinkKeys links to remove from the map (array, or "all" to remove all links)
         *  opt.setLegendElemsState the state of legend elements to be set : show (default) or hide
         *  opt.animDuration animation duration in ms (default = 0)
         *  opt.afterUpdate Hook that allows to add custom processing on the map
         */
        onUpdateEvent: function (e, opt) {
            var self = this;
            // Abort if opt is undefined
            if (typeof opt !== "object")  return;

            var i = 0;
            var animDuration = (opt.animDuration) ? opt.animDuration : 0;

            // This function remove an element using animation (or not, depending on animDuration)
            // Used for deletePlotKeys and deleteLinkKeys
            var fnRemoveElement = function (elem) {
                // Unset all event handlers
                self.unsetHover(elem.mapElem, elem.textElem);
                if (animDuration > 0) {
                    elem.mapElem.animate({"opacity": 0}, animDuration, "linear", function () {
                        elem.mapElem.remove();
                    });
                    if (elem.textElem) {
                        elem.textElem.animate({"opacity": 0}, animDuration, "linear", function () {
                            elem.textElem.remove();
                        });
                    }
                } else {
                    elem.mapElem.remove();
                    if (elem.textElem) {
                        elem.textElem.remove();
                    }
                }
            };

            // This function show an element using animation
            // Used for newPlots and newLinks
            var fnShowElement = function (elem) {
                // Starts with hidden elements
                elem.mapElem.attr({opacity: 0});
                if (elem.textElem) elem.textElem.attr({opacity: 0});
                // Set final element opacity
                self.setElementOpacity(
                    elem,
                    (elem.mapElem.originalAttrs.opacity !== undefined) ? elem.mapElem.originalAttrs.opacity : 1,
                    animDuration
                );
            };

            if (typeof opt.mapOptions === "object") {
                if (opt.replaceOptions === true) self.options = self.extendDefaultOptions(opt.mapOptions);
                else $.extend(true, self.options, opt.mapOptions);

                // IF we update areas, plots or legend, then reset all legend state to "show"
                if (opt.mapOptions.areas !== undefined || opt.mapOptions.plots !== undefined || opt.mapOptions.legend !== undefined) {
                    $("[data-type='elem']", self.$container).each(function (id, elem) {
                        if ($(elem).attr('data-hidden') === "1") {
                            // Toggle state of element by clicking
                            $(elem).trigger("click", [false, animDuration]);
                        }
                    });
                }
            }

            // Delete plots by name if deletePlotKeys is array
            if (typeof opt.deletePlotKeys === "object") {
                for (; i < opt.deletePlotKeys.length; i++) {
                    if (self.plots[opt.deletePlotKeys[i]] !== undefined) {
                        fnRemoveElement(self.plots[opt.deletePlotKeys[i]]);
                        delete self.plots[opt.deletePlotKeys[i]];
                    }
                }
                // Delete ALL plots if deletePlotKeys is set to "all"
            } else if (opt.deletePlotKeys === "all") {
                $.each(self.plots, function (id, elem) {
                    fnRemoveElement(elem);
                });
                // Empty plots object
                self.plots = {};
            }

            // Delete links by name if deleteLinkKeys is array
            if (typeof opt.deleteLinkKeys === "object") {
                for (i = 0; i < opt.deleteLinkKeys.length; i++) {
                    if (self.links[opt.deleteLinkKeys[i]] !== undefined) {
                        fnRemoveElement(self.links[opt.deleteLinkKeys[i]]);
                        delete self.links[opt.deleteLinkKeys[i]];
                    }
                }
                // Delete ALL links if deleteLinkKeys is set to "all"
            } else if (opt.deleteLinkKeys === "all") {
                $.each(self.links, function (id, elem) {
                    fnRemoveElement(elem);
                });
                // Empty links object
                self.links = {};
            }

            // New plots
            if (typeof opt.newPlots === "object") {
                $.each(opt.newPlots, function (id) {
                    if (self.plots[id] === undefined) {
                        self.options.plots[id] = opt.newPlots[id];
                        self.plots[id] = self.drawPlot(id);
                        if (animDuration > 0) {
                            fnShowElement(self.plots[id]);
                        }
                    }
                });
            }

            // New links
            if (typeof opt.newLinks === "object") {
                var newLinks = self.drawLinksCollection(opt.newLinks);
                $.extend(self.links, newLinks);
                $.extend(self.options.links, opt.newLinks);
                if (animDuration > 0) {
                    $.each(newLinks, function (id) {
                        fnShowElement(newLinks[id]);
                    });
                }
            }

            // Update areas attributes and tooltips
            $.each(self.areas, function (id) {
                // Avoid updating unchanged elements
                if ((typeof opt.mapOptions === "object" &&
                    (
                        (typeof opt.mapOptions.map === "object" && typeof opt.mapOptions.map.defaultArea === "object")
                        || (typeof opt.mapOptions.areas === "object" && typeof opt.mapOptions.areas[id] === "object")
                        || (typeof opt.mapOptions.legend === "object" && typeof opt.mapOptions.legend.area === "object")
                    ))
                    || opt.replaceOptions === true
                ) {
                    var elemOptions = self.getElemOptions(
                        self.options.map.defaultArea,
                        (self.options.areas[id] ? self.options.areas[id] : {}),
                        self.options.legend.area
                    );
                    self.updateElem(elemOptions, self.areas[id], animDuration);
                }
            });

            // Update plots attributes and tooltips
            $.each(self.plots, function (id) {
                // Avoid updating unchanged elements
                if ((typeof opt.mapOptions ==="object" &&
                    (
                        (typeof opt.mapOptions.map === "object" && typeof opt.mapOptions.map.defaultPlot === "object")
                        || (typeof opt.mapOptions.plots === "object" && typeof opt.mapOptions.plots[id] === "object")
                        || (typeof opt.mapOptions.legend === "object" && typeof opt.mapOptions.legend.plot === "object")
                    ))
                    || opt.replaceOptions === true
                ) {
                    var elemOptions = self.getElemOptions(
                        self.options.map.defaultPlot,
                        (self.options.plots[id] ? self.options.plots[id] : {}),
                        self.options.legend.plot
                    );
                    if (elemOptions.type == "square") {
                        elemOptions.attrs.width = elemOptions.size;
                        elemOptions.attrs.height = elemOptions.size;
                        elemOptions.attrs.x = self.plots[id].mapElem.attrs.x - (elemOptions.size - self.plots[id].mapElem.attrs.width) / 2;
                        elemOptions.attrs.y = self.plots[id].mapElem.attrs.y - (elemOptions.size - self.plots[id].mapElem.attrs.height) / 2;
                    } else if (elemOptions.type == "image") {
                        elemOptions.attrs.width = elemOptions.width;
                        elemOptions.attrs.height = elemOptions.height;
                        elemOptions.attrs.x = self.plots[id].mapElem.attrs.x - (elemOptions.width - self.plots[id].mapElem.attrs.width) / 2;
                        elemOptions.attrs.y = self.plots[id].mapElem.attrs.y - (elemOptions.height - self.plots[id].mapElem.attrs.height) / 2;
                    } else if (elemOptions.type == "svg") {
                        if (elemOptions.attrs.transform !== undefined) {
                            elemOptions.attrs.transform = self.plots[id].mapElem.baseTransform + elemOptions.attrs.transform;
                        }
                    }else { // Default : circle
                        elemOptions.attrs.r = elemOptions.size / 2;
                    }

                    self.updateElem(elemOptions, self.plots[id], animDuration);
                }
            });

            // Update links attributes and tooltips
            $.each(self.links, function (id) {
                // Avoid updating unchanged elements
                if ((typeof opt.mapOptions === "object" &&
                    (
                        (typeof opt.mapOptions.map === "object" && typeof opt.mapOptions.map.defaultLink === "object")
                        || (typeof opt.mapOptions.links === "object" && typeof opt.mapOptions.links[id] === "object")
                    ))
                    || opt.replaceOptions === true
                ) {
                    var elemOptions = self.getElemOptions(
                        self.options.map.defaultLink,
                        (self.options.links[id] ? self.options.links[id] : {}),
                        {}
                    );

                    self.updateElem(elemOptions, self.links[id], animDuration);
                }
            });

            // Update legends
            if (opt.mapOptions && (
                    (typeof opt.mapOptions.legend === "object")
                    || (typeof opt.mapOptions.map === "object" && typeof opt.mapOptions.map.defaultArea === "object")
                    || (typeof opt.mapOptions.map === "object" && typeof opt.mapOptions.map.defaultPlot === "object")
                )) {
                // Show all elements on the map before updating the legends
                $("[data-type='elem']", self.$container).each(function (id, elem) {
                    if ($(elem).attr('data-hidden') === "1") {
                        $(elem).trigger("click", [false, animDuration]);
                    }
                });

                self.createLegends("area", self.areas, 1);
                if (self.options.map.width) {
                    self.createLegends("plot", self.plots, (self.options.map.width / self.mapConf.width));
                } else {
                    self.createLegends("plot", self.plots, (self.$map.width() / self.mapConf.width));
                }
            }

            // Hide/Show all elements based on showlegendElems
            //      Toggle (i.e. click) only if:
            //          - slice legend is shown AND we want to hide
            //          - slice legend is hidden AND we want to show
            if (typeof opt.setLegendElemsState === "object") {
                // setLegendElemsState is an object listing the legend we want to hide/show
                $.each(opt.setLegendElemsState, function (legendCSSClass, action) {
                    // Search for the legend
                    var $legend = self.$container.find("." + legendCSSClass)[0];
                    if ($legend !== undefined) {
                        // Select all elem inside this legend
                        $("[data-type='elem']", $legend).each(function (id, elem) {
                            if (($(elem).attr('data-hidden') === "0" && action === "hide") ||
                                ($(elem).attr('data-hidden') === "1" && action === "show")) {
                                // Toggle state of element by clicking
                                $(elem).trigger("click", [false, animDuration]);
                            }
                        });
                    }
                });
            } else {
                // setLegendElemsState is a string, or is undefined
                // Default : "show"
                var action = (opt.setLegendElemsState === "hide") ? "hide" : "show";

                $("[data-type='elem']", self.$container).each(function (id, elem) {
                    if (($(elem).attr('data-hidden') === "0" && action === "hide") ||
                        ($(elem).attr('data-hidden') === "1" && action === "show")) {
                        // Toggle state of element by clicking
                        $(elem).trigger("click", [false, animDuration]);
                    }
                });
            }
            if (opt.afterUpdate) opt.afterUpdate(self.$container, self.paper, self.areas, self.plots, self.options);
        },

        /*
         * Draw all links between plots on the paper
         */
        drawLinksCollection: function (linksCollection) {
            var self = this;
            var p1 = {};
            var p2 = {};
            var coordsP1 = {};
            var coordsP2 = {};
            var links = {};

            $.each(linksCollection, function (id) {
                var elemOptions = self.getElemOptions(self.options.map.defaultLink, linksCollection[id], {});

                if (typeof linksCollection[id].between[0] == 'string') {
                    p1 = self.options.plots[linksCollection[id].between[0]];
                } else {
                    p1 = linksCollection[id].between[0];
                }

                if (typeof linksCollection[id].between[1] == 'string') {
                    p2 = self.options.plots[linksCollection[id].between[1]];
                } else {
                    p2 = linksCollection[id].between[1];
                }

                if (p1.latitude !== undefined && p1.longitude !== undefined) {
                    coordsP1 = self.mapConf.getCoords(p1.latitude, p1.longitude);
                } else {
                    coordsP1.x = p1.x;
                    coordsP1.y = p1.y;
                }

                if (p2.latitude !== undefined && p2.longitude !== undefined) {
                    coordsP2 = self.mapConf.getCoords(p2.latitude, p2.longitude);
                } else {
                    coordsP2.x = p2.x;
                    coordsP2.y = p2.y;
                }
                links[id] = self.drawLink(id, coordsP1.x, coordsP1.y, coordsP2.x, coordsP2.y, elemOptions);
            });
            return links;
        },

        /*
         * Draw a curved link between two couples of coordinates a(xa,ya) and b(xb, yb) on the paper
         */
        drawLink: function (id, xa, ya, xb, yb, elemOptions) {
            var self = this;
            var elem = {};
            // Compute the "curveto" SVG point, d(x,y)
            // c(xc, yc) is the center of (xa,ya) and (xb, yb)
            var xc = (xa + xb) / 2;
            var yc = (ya + yb) / 2;

            // Equation for (cd) : y = acd * x + bcd (d is the cure point)
            var acd = -1 / ((yb - ya) / (xb - xa));
            var bcd = yc - acd * xc;

            // dist(c,d) = dist(a,b) (=abDist)
            var abDist = Math.sqrt((xb - xa) * (xb - xa) + (yb - ya) * (yb - ya));

            // Solution for equation dist(cd) = sqrt((xd - xc)² + (yd - yc)²)
            // dist(c,d)² = (xd - xc)² + (yd - yc)²
            // We assume that dist(c,d) = dist(a,b)
            // so : (xd - xc)² + (yd - yc)² - dist(a,b)² = 0
            // With the factor : (xd - xc)² + (yd - yc)² - (factor*dist(a,b))² = 0
            // (xd - xc)² + (acd*xd + bcd - yc)² - (factor*dist(a,b))² = 0
            var a = 1 + acd * acd;
            var b = -2 * xc + 2 * acd * bcd - 2 * acd * yc;
            var c = xc * xc + bcd * bcd - bcd * yc - yc * bcd + yc * yc - ((elemOptions.factor * abDist) * (elemOptions.factor * abDist));
            var delta = b * b - 4 * a * c;
            var x = 0;
            var y = 0;

            // There are two solutions, we choose one or the other depending on the sign of the factor
            if (elemOptions.factor > 0) {
                x = (-b + Math.sqrt(delta)) / (2 * a);
                y = acd * x + bcd;
            } else {
                x = (-b - Math.sqrt(delta)) / (2 * a);
                y = acd * x + bcd;
            }

            elem.mapElem = self.paper.path("m " + xa + "," + ya + " C " + x + "," + y + " " + xb + "," + yb + " " + xb + "," + yb + "").attr(elemOptions.attrs);
            self.initElem(elem, elemOptions, id);

            return elem;
        },

        /*
         * Check wether newAttrs object bring modifications to originalAttrs object
         */
        isAttrsChanged: function(originalAttrs, newAttrs) {
            for (var key in newAttrs) {
                if (typeof originalAttrs[key] === 'undefined' || newAttrs[key] !== originalAttrs[key]) {
                    return true;
                }
            }
            return false;
        },

        /*
         * Update the element "elem" on the map with the new elemOptions options
         */
        updateElem: function (elemOptions, elem, animDuration) {
            var self = this;
            var bbox;
            var textPosition;
            var plotOffsetX;
            var plotOffsetY;

            if (elemOptions.value !== undefined)
                elem.value = elemOptions.value;

            if (elemOptions.toFront === true) {
                elem.mapElem.toFront();
            }

            // Update the label
            if (elem.textElem) {
                if (elemOptions.text !== undefined && elemOptions.text.content !== undefined && elemOptions.text.content != elem.textElem.attrs.text)
                    elem.textElem.attr({text: elemOptions.text.content});

                bbox = elem.mapElem.getBBox();

                if (elemOptions.size || (elemOptions.width && elemOptions.height)) {
                    if (elemOptions.type == "image" || elemOptions.type == "svg") {
                        plotOffsetX = (elemOptions.width - bbox.width) / 2;
                        plotOffsetY = (elemOptions.height - bbox.height) / 2;
                    } else {
                        plotOffsetX = (elemOptions.size - bbox.width) / 2;
                        plotOffsetY = (elemOptions.size - bbox.height) / 2;
                    }
                    bbox.x -= plotOffsetX;
                    bbox.x2 += plotOffsetX;
                    bbox.y -= plotOffsetY;
                    bbox.y2 += plotOffsetY;
                }

                textPosition = self.getTextPosition(bbox, elemOptions.text.position, elemOptions.text.margin);
                if (textPosition.x != elem.textElem.attrs.x || textPosition.y != elem.textElem.attrs.y) {
                    if (animDuration > 0) {
                        elem.textElem.attr({"text-anchor": textPosition.textAnchor});
                        elem.textElem.animate({x: textPosition.x, y: textPosition.y}, animDuration);
                    } else
                        elem.textElem.attr({
                            x: textPosition.x,
                            y: textPosition.y,
                            "text-anchor": textPosition.textAnchor
                        });
                }

                self.setHoverOptions(elem.textElem, elemOptions.text.attrs, elemOptions.text.attrsHover);
                if (animDuration > 0)
                    elem.textElem.animate(elemOptions.text.attrs, animDuration);
                else
                    elem.textElem.attr(elemOptions.text.attrs);
            }

            // Update elements attrs and attrsHover
            self.setHoverOptions(elem.mapElem, elemOptions.attrs, elemOptions.attrsHover);

            if (self.isAttrsChanged(elem.mapElem.attrs, elemOptions.attrs)) {
                if (animDuration > 0)
                    elem.mapElem.animate(elemOptions.attrs, animDuration);
                else
                    elem.mapElem.attr(elemOptions.attrs);
            }

            // Update dimensions of SVG plots
            if (elemOptions.type == "svg") {

                if (bbox === undefined) {
                    bbox = elem.mapElem.getBBox();
                }
                elem.mapElem.transform("m" + (elemOptions.width / elem.mapElem.originalWidth) + ",0,0," + (elemOptions.height / elem.mapElem.originalHeight) + "," + bbox.x + "," + bbox.y);
            }

            // Update the tooltip
            if (elemOptions.tooltip) {
                if (elem.mapElem.tooltip === undefined) {
                    self.setTooltip(elem.mapElem);
                    if (elem.textElem) self.setTooltip(elem.textElem);
                }
                elem.mapElem.tooltip = elemOptions.tooltip;
                if (elem.textElem) elem.textElem.tooltip = elemOptions.tooltip;
            }

            // Update the link
            if (elemOptions.href !== undefined) {
                if (elem.mapElem.href === undefined) {
                    self.setHref(elem.mapElem);
                    if (elem.textElem) self.setHref(elem.textElem);
                }
                elem.mapElem.href = elemOptions.href;
                elem.mapElem.target = elemOptions.target;
                if (elem.textElem) {
                    elem.textElem.href = elemOptions.href;
                    elem.textElem.target = elemOptions.target;
                }
            }
        },

        /*
         * Draw the plot
         */
        drawPlot: function (id) {
            var self = this;
            var plot = {};
            var coords = {};
            var elemOptions = self.getElemOptions(
                self.options.map.defaultPlot,
                (self.options.plots[id] ? self.options.plots[id] : {}),
                self.options.legend.plot
            );

            if (elemOptions.x !== undefined && elemOptions.y !== undefined)
                coords = {x: elemOptions.x, y: elemOptions.y};
            else if (elemOptions.plotsOn !== undefined && self.areas[elemOptions.plotsOn].mapElem !== undefined){
                var path = self.areas[elemOptions.plotsOn].mapElem;
                var bbox = path.getBBox();
                var _x = Math.floor(bbox.x + bbox.width/2.0);
                var _y = Math.floor(bbox.y + bbox.height/2.0);
                coords = {x: _x, y: _y};
            }
            else
                coords = self.mapConf.getCoords(elemOptions.latitude, elemOptions.longitude);

            if (elemOptions.type == "square") {
                plot = {
                    "mapElem": self.paper.rect(
                        coords.x - (elemOptions.size / 2),
                        coords.y - (elemOptions.size / 2),
                        elemOptions.size,
                        elemOptions.size
                    ).attr(elemOptions.attrs)
                };
            } else if (elemOptions.type == "image") {
                plot = {
                    "mapElem": self.paper.image(
                        elemOptions.url,
                        coords.x - elemOptions.width / 2,
                        coords.y - elemOptions.height / 2,
                        elemOptions.width,
                        elemOptions.height
                    ).attr(elemOptions.attrs)
                };
            } else if (elemOptions.type == "svg") {
                if (elemOptions.attrs.transform === undefined) {
                    elemOptions.attrs.transform = "";
                }

                plot = {"mapElem": self.paper.path(elemOptions.path)};
                plot.mapElem.originalWidth = plot.mapElem.getBBox().width;
                plot.mapElem.originalHeight = plot.mapElem.getBBox().height;

                plot.mapElem.baseTransform = "m" + (elemOptions.width / plot.mapElem.originalWidth) + ",0,0," + (elemOptions.height / plot.mapElem.originalHeight) + "," + (coords.x - elemOptions.width / 2) + "," + (coords.y - elemOptions.height / 2);
                elemOptions.attrs.transform = plot.mapElem.baseTransform + elemOptions.attrs.transform;
                plot.mapElem.attr(elemOptions.attrs);
            } else { // Default = circle
                plot = {"mapElem": self.paper.circle(coords.x, coords.y, elemOptions.size / 2).attr(elemOptions.attrs)};
            }
            self.initElem(plot, elemOptions, id);
            return plot;
        },

        /*
         * Set target link on elem
         */
        setHref: function (elem) {
            var self = this;
            elem.attr({cursor: "pointer"});
            $(elem.node).on("click." + pluginName, function () {
                if (!self.panning && elem.href)
                    window.open(elem.href, elem.target);
            });
        },

        /*
         * Set a tooltip for the areas and plots
         * @param elem area or plot element
         * @param content the content to set in the tooltip
         */
        setTooltip: function (elem) {
            var self = this;
            var tooltipTO = 0;
            var cssClass = self.$tooltip.attr('class');



            var updateTooltipPosition = function (x, y) {

                var offsetLeft = 10;
                var offsetTop = 20;

                if (typeof elem.tooltip.offset === "object") {
                    if (typeof elem.tooltip.offset.left !== "undefined") {
                        offsetLeft = elem.tooltip.offset.left;
                    }
                    if (typeof elem.tooltip.offset.top !== "undefined") {
                        offsetTop = elem.tooltip.offset.top;
                    }
                }

                var tooltipPosition = {
                    "left": Math.min(self.$map.width() - self.$tooltip.outerWidth() - 5, x - self.$map.offset().left + offsetLeft),
                    "top": Math.min(self.$map.height() - self.$tooltip.outerHeight() - 5, y - self.$map.offset().top + offsetTop)
                };

                if (typeof elem.tooltip.overflow === "object") {
                    if (elem.tooltip.overflow.right === true) {
                        tooltipPosition.left = x - self.$map.offset().left + 10;
                    }
                    if (selem.tooltip.overflow.bottom === true) {
                        tooltipPosition.top = y - self.$map.offset().top + 20;
                    }
                }

                self.$tooltip.css(tooltipPosition);
            };

            $(elem.node).on("mouseover." + pluginName, function (e) {
                tooltipTO = setTimeout(
                    function () {
                        self.$tooltip.attr("class", cssClass);
                        if (elem.tooltip !== undefined) {
                            if (elem.tooltip.content !== undefined) {
                                // if tooltip.content is function, call it. Otherwise, assign it directly.
                                var content = (typeof elem.tooltip.content === "function") ? elem.tooltip.content(elem) : elem.tooltip.content;
                                self.$tooltip.html(content).css("display", "block");
                            }
                            if (elem.tooltip.cssClass !== undefined) {
                                self.$tooltip.addClass(elem.tooltip.cssClass);
                            }
                        }
                        updateTooltipPosition(e.pageX, e.pageY);
                    }, 120
                );
            }).on("mouseout." + pluginName, function () {
                clearTimeout(tooltipTO);
                self.$tooltip.css("display", "none");
            }).on("mousemove." + pluginName, function (e) {
                updateTooltipPosition(e.pageX, e.pageY);
            });
        },

        /*
         * Set user defined handlers for events on areas and plots
         * @param id the id of the element
         * @param elemOptions the element parameters
         * @param mapElem the map element to set callback on
         * @param textElem the optional text within the map element
         */
        setEventHandlers: function (id, elemOptions, mapElem, textElem) {
            var self = this;
            $.each(elemOptions.eventHandlers, function (event) {
                (function (event) {
                    $(mapElem.node).on(event, function (e) {
                        if (!self.panning) elemOptions.eventHandlers[event](e, id, mapElem, textElem, elemOptions);
                    });
                    if (textElem) {
                        $(textElem.node).on(event, function (e) {
                            if (!self.panning) elemOptions.eventHandlers[event](e, id, mapElem, textElem, elemOptions);
                        });
                    }
                })(event);
            });
        },

        /*
         * Draw a legend for areas and / or plots
         * @param legendOptions options for the legend to draw
         * @param legendType the type of the legend : "area" or "plot"
         * @param elems collection of plots or areas on the maps
         * @param legendIndex index of the legend in the conf array
         */
        drawLegend: function (legendOptions, legendType, elems, scale, legendIndex) {
            var self = this;
            var $legend = {};
            var legendPaper = {};
            var width = 0;
            var height = 0;
            var title = null;
            var elem = {};
            var elemBBox = {};
            var label = {};
            var i = 0;
            var x = 0;
            var y = 0;
            var yCenter = 0;
            var sliceOptions = [];
            var length = 0;

            $legend = $("." + legendOptions.cssClass, self.$container);

            if (typeof self.createdLegends[legendOptions.cssClass] ==='undefined') {
                self.createdLegends[legendOptions.cssClass] = {
                    container: $legend,
                    initialHTMLContent: $legend.html()
                };
            }

            $legend.empty();

            legendPaper = new Raphael($legend.get(0));
            // Set some data to object
            $(legendPaper.canvas).attr({"data-type": legendType, "data-index": legendIndex});

            height = width = 0;

            // Set the title of the legend
            if (legendOptions.title && legendOptions.title !== "") {
                title = legendPaper.text(legendOptions.marginLeftTitle, 0, legendOptions.title).attr(legendOptions.titleAttrs);
                title.attr({y: 0.5 * title.getBBox().height});

                width = legendOptions.marginLeftTitle + title.getBBox().width;
                height += legendOptions.marginBottomTitle + title.getBBox().height;
            }

            // Calculate attrs (and width, height and r (radius)) for legend elements, and yCenter for horizontal legends

            for (i = 0, length = legendOptions.slices.length; i < length; ++i) {
                var yCenterCurrent = 0;

                sliceOptions[i] = $.extend(true, {}, (legendType == "plot") ? self.options.map.defaultPlot : self.options.map.defaultArea, legendOptions.slices[i]);

                if (legendOptions.slices[i].legendSpecificAttrs === undefined) {
                    legendOptions.slices[i].legendSpecificAttrs = {};
                }

                $.extend(true, sliceOptions[i].attrs, legendOptions.slices[i].legendSpecificAttrs);

                if (legendType == "area") {
                    if (sliceOptions[i].attrs.width === undefined)
                        sliceOptions[i].attrs.width = 30;
                    if (sliceOptions[i].attrs.height === undefined)
                        sliceOptions[i].attrs.height = 20;
                } else if (sliceOptions[i].type == "square") {
                    if (sliceOptions[i].attrs.width === undefined)
                        sliceOptions[i].attrs.width = sliceOptions[i].size;
                    if (sliceOptions[i].attrs.height === undefined)
                        sliceOptions[i].attrs.height = sliceOptions[i].size;
                } else if (sliceOptions[i].type == "image" || sliceOptions[i].type == "svg") {
                    if (sliceOptions[i].attrs.width === undefined)
                        sliceOptions[i].attrs.width = sliceOptions[i].width;
                    if (sliceOptions[i].attrs.height === undefined)
                        sliceOptions[i].attrs.height = sliceOptions[i].height;
                } else {
                    if (sliceOptions[i].attrs.r === undefined)
                        sliceOptions[i].attrs.r = sliceOptions[i].size / 2;
                }

                // Compute yCenter for this legend slice
                yCenterCurrent = legendOptions.marginBottomTitle;
                // Add title height if it exists
                if (title) {
                    yCenterCurrent += title.getBBox().height;
                }
                if (legendType == "plot" && (sliceOptions[i].type === undefined || sliceOptions[i].type == "circle")) {
                    yCenterCurrent += scale * sliceOptions[i].attrs.r;
                } else {
                    yCenterCurrent += scale * sliceOptions[i].attrs.height / 2;
                }
                // Update yCenter if current larger
                yCenter = Math.max(yCenter, yCenterCurrent);
            }

            if (legendOptions.mode == "horizontal") {
                width = legendOptions.marginLeft;
            }

            // Draw legend elements (circle, square or image in vertical or horizontal mode)
            for (i = 0, length = sliceOptions.length; i < length; ++i) {
                if (sliceOptions[i].display === undefined || sliceOptions[i].display === true) {
                    if (legendType == "area") {
                        if (legendOptions.mode == "horizontal") {
                            x = width + legendOptions.marginLeft;
                            y = yCenter - (0.5 * scale * sliceOptions[i].attrs.height);
                        } else {
                            x = legendOptions.marginLeft;
                            y = height;
                        }

                        elem = legendPaper.rect(x, y, scale * (sliceOptions[i].attrs.width), scale * (sliceOptions[i].attrs.height));
                    } else if (sliceOptions[i].type == "square") {
                        if (legendOptions.mode == "horizontal") {
                            x = width + legendOptions.marginLeft;
                            y = yCenter - (0.5 * scale * sliceOptions[i].attrs.height);
                        } else {
                            x = legendOptions.marginLeft;
                            y = height;
                        }

                        elem = legendPaper.rect(x, y, scale * (sliceOptions[i].attrs.width), scale * (sliceOptions[i].attrs.height));

                    } else if (sliceOptions[i].type == "image" || sliceOptions[i].type == "svg") {
                        if (legendOptions.mode == "horizontal") {
                            x = width + legendOptions.marginLeft;
                            y = yCenter - (0.5 * scale * sliceOptions[i].attrs.height);
                        } else {
                            x = legendOptions.marginLeft;
                            y = height;
                        }

                        if (sliceOptions[i].type == "image") {
                            elem = legendPaper.image(
                                sliceOptions[i].url, x, y, scale * sliceOptions[i].attrs.width, scale * sliceOptions[i].attrs.height);
                        } else {
                            elem = legendPaper.path(sliceOptions[i].path);

                            if (sliceOptions[i].attrs.transform === undefined) {
                                sliceOptions[i].attrs.transform = "";
                            }
                            sliceOptions[i].attrs.transform = "m" + ((scale * sliceOptions[i].width) / elem.getBBox().width) + ",0,0," + ((scale * sliceOptions[i].height) / elem.getBBox().height) + "," + x + "," + y + sliceOptions[i].attrs.transform;
                        }
                    } else {
                        if (legendOptions.mode == "horizontal") {
                            x = width + legendOptions.marginLeft + scale * (sliceOptions[i].attrs.r);
                            y = yCenter;
                        } else {
                            x = legendOptions.marginLeft + scale * (sliceOptions[i].attrs.r);
                            y = height + scale * (sliceOptions[i].attrs.r);
                        }
                        elem = legendPaper.circle(x, y, scale * (sliceOptions[i].attrs.r));
                    }

                    // Set attrs to the element drawn above
                    delete sliceOptions[i].attrs.width;
                    delete sliceOptions[i].attrs.height;
                    delete sliceOptions[i].attrs.r;
                    elem.attr(sliceOptions[i].attrs);
                    elemBBox = elem.getBBox();

                    // Draw the label associated with the element
                    if (legendOptions.mode == "horizontal") {
                        x = width + legendOptions.marginLeft + elemBBox.width + legendOptions.marginLeftLabel;
                        y = yCenter;
                    } else {
                        x = legendOptions.marginLeft + elemBBox.width + legendOptions.marginLeftLabel;
                        y = height + (elemBBox.height / 2);
                    }

                    label = legendPaper.text(x, y, sliceOptions[i].label).attr(legendOptions.labelAttrs);

                    // Update the width and height for the paper
                    if (legendOptions.mode == "horizontal") {
                        var currentHeight = legendOptions.marginBottom + elemBBox.height;
                        width += legendOptions.marginLeft + elemBBox.width + legendOptions.marginLeftLabel + label.getBBox().width;
                        if (sliceOptions[i].type != "image" && legendType != "area") {
                            currentHeight += legendOptions.marginBottomTitle;
                        }
                        // Add title height if it exists
                        if (title) {
                            currentHeight += title.getBBox().height;
                        }
                        height = Math.max(height, currentHeight);
                    } else {
                        width = Math.max(width, legendOptions.marginLeft + elemBBox.width + legendOptions.marginLeftLabel + label.getBBox().width);
                        height += legendOptions.marginBottom + elemBBox.height;
                    }

                    $(elem.node).attr({"data-type": "elem", "data-index": i, "data-hidden": 0});
                    $(label.node).attr({"data-type": "label", "data-index": i, "data-hidden": 0});

                    // Hide map elements when the user clicks on a legend item
                    if (legendOptions.hideElemsOnClick.enabled) {
                        // Hide/show elements when user clicks on a legend element
                        label.attr({cursor: "pointer"});
                        elem.attr({cursor: "pointer"});

                        self.setHoverOptions(elem, sliceOptions[i].attrs, sliceOptions[i].attrs);
                        self.setHoverOptions(label, legendOptions.labelAttrs, legendOptions.labelAttrsHover);
                        self.setHover(elem, label);
                        self.handleClickOnLegendElem(legendOptions, legendOptions.slices[i], label, elem, elems, legendIndex);
                    }
                }
            }

            // VMLWidth option allows you to set static width for the legend
            // only for VML render because text.getBBox() returns wrong values on IE6/7
            if (Raphael.type != "SVG" && legendOptions.VMLWidth)
                width = legendOptions.VMLWidth;

            legendPaper.setSize(width, height);
        },

        /*
         * Allow to hide elements of the map when the user clicks on a related legend item
         * @param legendOptions options for the legend to draw
         * @param sliceOptions options of the slice
         * @param label label of the legend item
         * @param elem element of the legend item
         * @param elems collection of plots or areas displayed on the map
         * @param legendIndex index of the legend in the conf array
         */
        handleClickOnLegendElem: function (legendOptions, sliceOptions, label, elem, elems, legendIndex) {
            var self = this;

            /**
             *
             * @param e
             * @param hideOtherElems : option used for the 'exclusive' mode to enabled only one item from the legend
             * at once
             * @param animDuration : used in the 'update' event in order to apply the same animDuration on the legend items
             */
            var hideMapElems = function (e, hideOtherElems, animDuration) {
                var elemValue = 0;
                var hidden = $(label.node).attr('data-hidden');
                var hiddenNewAttr = (hidden === '0') ? {"data-hidden": '1'} : {"data-hidden": '0'};

                // Check animDuration: if not set, this is a regular click, use the value specified in options
                if (animDuration === undefined) animDuration = legendOptions.hideElemsOnClick.animDuration;

                if (hidden === '0') {
                    if (animDuration > 0) label.animate({"opacity": 0.5}, animDuration);
                    else label.attr({"opacity": 0.5});
                } else {
                    if (animDuration > 0) label.animate({"opacity": 1}, animDuration);
                    else label.attr({"opacity": 1});
                }

                $.each(elems, function (id) {
                    // Retreive stored data of element
                    //      'hidden-by' contains the list of legendIndex that is hiding this element
                    var hiddenBy = elems[id].mapElem.data('hidden-by');
                    // Set to empty object if undefined
                    if (hiddenBy === undefined) hiddenBy = {};

                    if ($.isArray(elems[id].value)) {
                        elemValue = elems[id].value[legendIndex];
                    } else {
                        elemValue = elems[id].value;
                    }

                    // Hide elements whose value matches with the slice of the clicked legend item
                    if (self.getLegendSlice(elemValue, legendOptions) === sliceOptions) {
                        (function (id) {
                            if (hidden === '0') { // we want to hide this element
                                hiddenBy[legendIndex] = true; // add legendIndex to the data object for later use
                                self.setElementOpacity(elems[id], legendOptions.hideElemsOnClick.opacity, animDuration);
                            } else { // We want to show this element
                                delete hiddenBy[legendIndex]; // Remove this legendIndex from object
                                // Check if another legendIndex is defined
                                // We will show this element only if no legend is no longer hiding it
                                if ($.isEmptyObject(hiddenBy)) {
                                    self.setElementOpacity(
                                        elems[id],
                                        elems[id].mapElem.originalAttrs.opacity !== undefined ? elems[id].mapElem.originalAttrs.opacity : 1,
                                        animDuration
                                    );
                                }
                            }
                            // Update elem data with new values
                            elems[id].mapElem.data('hidden-by', hiddenBy);
                        })(id);
                    }
                });

                $(elem.node).attr(hiddenNewAttr);
                $(label.node).attr(hiddenNewAttr);

                if ((hideOtherElems === undefined || hideOtherElems === true)
                    && legendOptions.exclusive !== undefined && legendOptions.exclusive === true
                ) {
                    $("[data-type='elem'][data-hidden=0]", self.$container).each(function () {
                        if ($(this).attr('data-index') !== $(elem.node).attr('data-index')) {
                            $(this).trigger("click", false);
                        }
                    });
                }
            };
            $(label.node).on("click." + pluginName, hideMapElems);
            $(elem.node).on("click." + pluginName, hideMapElems);

            if (sliceOptions.clicked !== undefined && sliceOptions.clicked === true) {
                $(elem.node).trigger("click", false);
            }
        },

        /*
         * Create all legends for a specified type (area or plot)
         * @param legendType the type of the legend : "area" or "plot"
         * @param elems collection of plots or areas displayed on the map
         * @param scale scale ratio of the map
         */
        createLegends: function (legendType, elems, scale) {
            var self = this;
            var legendsOptions = self.options.legend[legendType];

            if (!$.isArray(self.options.legend[legendType])) {
                legendsOptions = [self.options.legend[legendType]];
            }

            for (var j = 0; j < legendsOptions.length; ++j) {
                // Check for class existence
                if (legendsOptions[j].cssClass === "" || $("." + legendsOptions[j].cssClass, self.$container).length === 0) {
                    throw new Error("The legend class `" + legendsOptions[j].cssClass + "` doesn't exists.");
                }
                if (legendsOptions[j].display === true && $.isArray(legendsOptions[j].slices) && legendsOptions[j].slices.length > 0) {
                    self.drawLegend(legendsOptions[j], legendType, elems, scale, j);
                }
            }
        },

        /*
         * Set the attributes on hover and the attributes to restore for a map element
         * @param elem the map element
         * @param originalAttrs the original attributes to restore on mouseout event
         * @param attrsHover the attributes to set on mouseover event
         */
        setHoverOptions: function (elem, originalAttrs, attrsHover) {
            // Disable transform option on hover for VML (IE<9) because of several bugs
            if (Raphael.type != "SVG") delete attrsHover.transform;
            elem.attrsHover = attrsHover;

            if (elem.attrsHover.transform) elem.originalAttrs = $.extend({transform: "s1"}, originalAttrs);
            else elem.originalAttrs = originalAttrs;
        },

        /*
         * Set the hover behavior (mouseover & mouseout) for plots and areas
         * @param mapElem the map element
         * @param textElem the optional text element (within the map element)
         */
        setHover: function (mapElem, textElem) {
            var self = this;
            var $mapElem = {};
            var $textElem = {};
            var mouseoverTimeout = 0;
            var mouseoutTimeout = 0;
            var overBehaviour = function () {
                clearTimeout(mouseoutTimeout);
                mouseoverTimeout = setTimeout(function () {
                    self.elemHover(mapElem, textElem);
                }, 120);
            };
            var outBehaviour = function () {
                clearTimeout(mouseoverTimeout);
                mouseoutTimeout = setTimeout(function(){
                    self.elemOut(mapElem, textElem);
                }, 120);
            };

            $mapElem = $(mapElem.node);
            $mapElem.on("mouseover." + pluginName, overBehaviour);
            $mapElem.on("mouseout." + pluginName, outBehaviour);

            if (textElem) {
                $textElem = $(textElem.node);
                $textElem.on("mouseover." + pluginName, overBehaviour);
                $(textElem.node).on("mouseout." + pluginName, outBehaviour);
            }
        },

        /*
         * Remove the hover behavior for plots and areas
         * @param mapElem the map element
         * @param textElem the optional text element (within the map element)
         */
        unsetHover: function (mapElem, textElem) {
            $(mapElem.node).off("." + pluginName);
            if (textElem) $(textElem.node).off("." + pluginName);
        },

        /*
         * Set he behaviour for "mouseover" event
         * @param mapElem mapElem the map element
         * @param textElem the optional text element (within the map element)
         */
        elemHover: function (mapElem, textElem) {
            var self = this;
            // Set mapElem
            if (mapElem.attrsHover.animDuration > 0) mapElem.animate(mapElem.attrsHover, mapElem.attrsHover.animDuration);
            else mapElem.attr(mapElem.attrsHover);
            // Set textElem
            if (textElem) {
                if (textElem.attrsHover.animDuration > 0) textElem.animate(textElem.attrsHover, textElem.attrsHover.animDuration);
                else textElem.attr(textElem.attrsHover);
            }
            // workaround for older version of Raphael
            if (self.paper.safari) self.paper.safari();
        },

        /*
         * Set he behaviour for "mouseout" event
         * @param mapElem the map element
         * @param textElem the optional text element (within the map element)
         */
        elemOut: function (mapElem, textElem) {
            var self = this;
            // Set mapElem
            if (mapElem.attrsHover.animDuration > 0) mapElem.animate(mapElem.originalAttrs, mapElem.attrsHover.animDuration);
            else mapElem.attr(mapElem.originalAttrs);
            // Set textElem
            if (textElem) {
                if (textElem.attrsHover.animDuration > 0) textElem.animate(textElem.originalAttrs, textElem.attrsHover.animDuration);
                else textElem.attr(textElem.originalAttrs);
            }

            // workaround for older version of Raphael
            if (self.paper.safari) self.paper.safari();
        },

        /*
         * Get element options by merging default options, element options and legend options
         * @param defaultOptions
         * @param elemOptions
         * @param legendOptions
         */
        getElemOptions: function (defaultOptions, elemOptions, legendOptions) {
            var self = this;
            var options = $.extend(true, {}, defaultOptions, elemOptions);
            if (options.value !== undefined) {
                if ($.isArray(legendOptions)) {
                    for (var i = 0, length = legendOptions.length; i < length; ++i) {
                        options = $.extend(true, {}, options, self.getLegendSlice(options.value[i], legendOptions[i]));
                    }
                } else {
                    options = $.extend(true, {}, options, self.getLegendSlice(options.value, legendOptions));
                }
            }
            return options;
        },

        /*
         * Get the coordinates of the text relative to a bbox and a position
         * @param bbox the boundary box of the element
         * @param textPosition the wanted text position (inner, right, left, top or bottom)
         * @param margin number or object {x: val, y:val} margin between the bbox and the text
         */
        getTextPosition: function (bbox, textPosition, margin) {
            var textX = 0;
            var textY = 0;
            var textAnchor = "";

            if (typeof margin === "number") {
                if (textPosition === "bottom" || textPosition === "top") {
                    margin = {x: 0, y: margin};
                } else if (textPosition === "right" || textPosition === "left") {
                    margin = {x: margin, y: 0};
                } else {
                    margin = {x: 0, y: 0};
                }
            }

            switch (textPosition) {
                case "bottom" :
                    textX = ((bbox.x + bbox.x2) / 2) + margin.x;
                    textY = bbox.y2 + margin.y;
                    textAnchor = "middle";
                    break;
                case "top" :
                    textX = ((bbox.x + bbox.x2) / 2) + margin.x;
                    textY = bbox.y - margin.y;
                    textAnchor = "middle";
                    break;
                case "left" :
                    textX = bbox.x - margin.x;
                    textY = ((bbox.y + bbox.y2) / 2) + margin.y;
                    textAnchor = "end";
                    break;
                case "right" :
                    textX = bbox.x2 + margin.x;
                    textY = ((bbox.y + bbox.y2) / 2) + margin.y;
                    textAnchor = "start";
                    break;
                default : // "inner" position
                    textX = ((bbox.x + bbox.x2) / 2) + margin.x;
                    textY = ((bbox.y + bbox.y2) / 2) + margin.y;
                    textAnchor = "middle";
            }
            return {"x": textX, "y": textY, "textAnchor": textAnchor};
        },

        /*
         * Get the legend conf matching with the value
         * @param value the value to match with a slice in the legend
         * @param legend the legend params object
         * @return the legend slice matching with the value
         */
        getLegendSlice: function (value, legend) {
            for (var i = 0, length = legend.slices.length; i < length; ++i) {
                if ((legend.slices[i].sliceValue !== undefined && value == legend.slices[i].sliceValue)
                    || ((legend.slices[i].sliceValue === undefined)
                    && (legend.slices[i].min === undefined || value >= legend.slices[i].min)
                    && (legend.slices[i].max === undefined || value <= legend.slices[i].max))
                ) {
                    return legend.slices[i];
                }
            }
            return {};
        },

        /*
         * Animated view box changes
         * As from http://code.voidblossom.com/animating-viewbox-easing-formulas/,
         * (from https://github.com/theshaun works on mapael)
         * @param x coordinate of the point to focus on
         * @param y coordinate of the point to focus on
         * @param w map defined width
         * @param h map defined height
         * @param duration defined length of time for animation
         * @param easingFunction defined Raphael supported easing_formula to use
         * @param callback method when animated action is complete
         */
        animateViewBox: function (x, y, w, h, duration, easingFunction) {
            var self = this;
            var cx = self.paper._viewBox ? self.paper._viewBox[0] : 0;
            var dx = x - cx;
            var cy = self.paper._viewBox ? self.paper._viewBox[1] : 0;
            var dy = y - cy;
            var cw = self.paper._viewBox ? self.paper._viewBox[2] : self.paper.width;
            var dw = w - cw;
            var ch = self.paper._viewBox ? self.paper._viewBox[3] : self.paper.height;
            var dh = h - ch;
            var interval = 25;
            var steps = duration / interval;
            var currentStep = 0;
            var easingFormula;

            easingFunction = easingFunction || "linear";
            easingFormula = Raphael.easing_formulas[easingFunction];

            clearInterval(self.animationIntervalID);

            self.animationIntervalID = setInterval(function () {
                    var ratio = currentStep / steps;
                    self.paper.setViewBox(cx + dx * easingFormula(ratio),
                        cy + dy * easingFormula(ratio),
                        cw + dw * easingFormula(ratio),
                        ch + dh * easingFormula(ratio), false);
                    if (currentStep++ >= steps) {
                        clearInterval(self.animationIntervalID);
                        clearTimeout(self.zoomTO);
                        self.zoomTO = setTimeout(function () {
                            self.$map.trigger("afterZoom", {x1: x, y1: y, x2: (x + w), y2: (y + h)});
                        }, 150);
                    }
                }, interval
            );
        },

        /*
         * Check for Raphael bug regarding drawing while beeing hidden (under display:none)
         * See https://github.com/neveldo/jQuery-Mapael/issues/135
         * @return true/false
         *
         * Wants to override this behavior? Use prototype overriding:
         *     $.mapael.prototype.isRaphaelBBoxBugPresent = function() {return false;};
         */
        isRaphaelBBoxBugPresent: function(){
            var self = this;
            // Draw text, then get its boundaries
            var text_elem = self.paper.text(-50, -50, "TEST");
            var text_elem_bbox = text_elem.getBBox();
            // remove element
            text_elem.remove();
            // If it has no height and width, then the paper is hidden
            return (text_elem_bbox.width === 0 && text_elem_bbox.height === 0);
        },

        // Default map options
        defaultOptions: {
            map: {
                cssClass: "map",
                tooltip: {
                    cssClass: "mapTooltip"
                },
                defaultArea: {
                    attrs: {
                        fill: "#343434",
                        stroke: "#5d5d5d",
                        "stroke-width": 1,
                        "stroke-linejoin": "round"
                    },
                    attrsHover: {
                        fill: "#f38a03",
                        animDuration: 300
                    },
                    text: {
                        position: "inner",
                        margin: 10,
                        attrs: {
                            "font-size": 15,
                            fill: "#c7c7c7"
                        },
                        attrsHover: {
                            fill: "#eaeaea",
                            "animDuration": 300
                        }
                    },
                    target: "_self",
                    cssClass: "area"
                },
                defaultPlot: {
                    type: "circle",
                    size: 15,
                    attrs: {
                        fill: "#0088db",
                        stroke: "#fff",
                        "stroke-width": 0,
                        "stroke-linejoin": "round"
                    },
                    attrsHover: {
                        "stroke-width": 3,
                        animDuration: 300
                    },
                    text: {
                        position: "right",
                        margin: 10,
                        attrs: {
                            "font-size": 15,
                            fill: "#c7c7c7"
                        },
                        attrsHover: {
                            fill: "#eaeaea",
                            animDuration: 300
                        }
                    },
                    target: "_self",
                    cssClass: "plot"
                },
                defaultLink: {
                    factor: 0.5,
                    attrs: {
                        stroke: "#0088db",
                        "stroke-width": 2
                    },
                    attrsHover: {
                        animDuration: 300
                    },
                    text: {
                        position: "inner",
                        margin: 10,
                        attrs: {
                            "font-size": 15,
                            fill: "#c7c7c7"
                        },
                        attrsHover: {
                            fill: "#eaeaea",
                            animDuration: 300
                        }
                    },
                    target: "_self",
                    cssClass: "link"
                },
                zoom: {
                    enabled: false,
                    minLevel: 0,
                    maxLevel: 10,
                    step: 0.25,
                    mousewheel: true,
                    touch: true,
                    animDuration: 200,
                    animEasing: "linear",
                    buttons: {
                        "reset": {
                            cssClass: "zoomButton zoomReset",
                            content: "&#8226;", // bullet sign
                            title: "Reset zoom"
                        },
                        "in": {
                            cssClass: "zoomButton zoomIn",
                            content: "+",
                            title: "Zoom in"
                        },
                        "out": {
                            cssClass: "zoomButton zoomOut",
                            content: "&#8722;", // minus sign
                            title: "Zoom out"
                        }
                    }
                }
            },
            legend: {
                redrawOnResize: true,
                area: [],
                plot: []
            },
            areas: {},
            plots: {},
            links: {}
        },

        // Default legends option
        legendDefaultOptions: {
            area: {
                cssClass: "areaLegend",
                display: true,
                marginLeft: 10,
                marginLeftTitle: 5,
                marginBottomTitle: 10,
                marginLeftLabel: 10,
                marginBottom: 10,
                titleAttrs: {
                    "font-size": 16,
                    fill: "#343434",
                    "text-anchor": "start"
                },
                labelAttrs: {
                    "font-size": 12,
                    fill: "#343434",
                    "text-anchor": "start"
                },
                labelAttrsHover: {
                    fill: "#787878",
                    animDuration: 300
                },
                hideElemsOnClick: {
                    enabled: true,
                    opacity: 0.2,
                    animDuration: 300
                },
                slices: [],
                mode: "vertical"
            },
            plot: {
                cssClass: "plotLegend",
                display: true,
                marginLeft: 10,
                marginLeftTitle: 5,
                marginBottomTitle: 10,
                marginLeftLabel: 10,
                marginBottom: 10,
                titleAttrs: {
                    "font-size": 16,
                    fill: "#343434",
                    "text-anchor": "start"
                },
                labelAttrs: {
                    "font-size": 12,
                    fill: "#343434",
                    "text-anchor": "start"
                },
                labelAttrsHover: {
                    fill: "#787878",
                    animDuration: 300
                },
                hideElemsOnClick: {
                    enabled: true,
                    opacity: 0.2,
                    animDuration: 300
                },
                slices: [],
                mode: "vertical"
            }
        }

    };

    // Extend jQuery with Mapael
    if ($[pluginName] === undefined) $[pluginName] = Mapael;

    // Add jQuery DOM function
    $.fn[pluginName] = function (options) {
        // Call Mapael on each element
        return this.each(function () {
            // Avoid leaking problem on multiple instanciation by removing an old mapael object on a container
            if ($.data(this, pluginName)) {
                $.data(this, pluginName).destroy();
            }
            // Create Mapael and save it as jQuery data
            // This allow external access to Mapael using $(".mapcontainer").data("mapael")
            $.data(this, pluginName, new Mapael(this, options));
        });
    };

    return Mapael;

}));
