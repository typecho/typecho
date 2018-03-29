(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.Push = f()}})(function(){var define,module,exports;return (function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
var errorPrefix = "PushError:";exports.default = { errors: { incompatible: "PushError: Push.js is incompatible with browser.", invalid_plugin: "PushError: plugin class missing from plugin manifest (invalid plugin). Please check the documentation.", invalid_title: "PushError: title of notification must be a string", permission_denied: "PushError: permission request declined", sw_notification_error: "PushError: could not show a ServiceWorker notification due to the following reason: ", sw_registration_error: "PushError: could not register the ServiceWorker due to the following reason: ", unknown_interface: "PushError: unable to create notification: unknown interface" } };

},{}],2:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Permission = function () {
  function Permission(i) {
    _classCallCheck(this, Permission);

    this._win = i, this.GRANTED = "granted", this.DEFAULT = "default", this.DENIED = "denied", this._permissions = [this.GRANTED, this.DEFAULT, this.DENIED];
  }

  _createClass(Permission, [{
    key: "request",
    value: function request(i, t) {
      return arguments.length > 0 ? this._requestWithCallback.apply(this, arguments) : this._requestAsPromise();
    }
  }, {
    key: "_requestWithCallback",
    value: function _requestWithCallback(i, t) {
      var _this = this;

      var s = this.get();var e = function e() {
        var s = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : _this._win.Notification.permission;
        void 0 === s && _this._win.webkitNotifications && (s = _this._win.webkitNotifications.checkPermission()), s === _this.GRANTED || 0 === s ? i && i() : t && t();
      };s !== this.DEFAULT ? e(s) : this._win.webkitNotifications && this._win.webkitNotifications.checkPermission ? this._win.webkitNotifications.requestPermission(e) : this._win.Notification && this._win.Notification.requestPermission ? this._win.Notification.requestPermission().then(e).catch(function () {
        t && t();
      }) : i && i();
    }
  }, {
    key: "_requestAsPromise",
    value: function _requestAsPromise() {
      var _this2 = this;

      var i = this.get();var t = function t(i) {
        return i === _this2.GRANTED || 0 === i;
      };var s = i !== this.DEFAULT,
          e = this._win.Notification && this._win.Notification.requestPermission,
          n = this._win.webkitNotifications && this._win.webkitNotifications.checkPermission;return new Promise(function (o, h) {
        var r = function r(i) {
          return t(i) ? o() : h();
        };s ? r(i) : n ? _this2._win.webkitNotifications.requestPermission(function (i) {
          r(i);
        }) : e ? _this2._win.Notification.requestPermission().then(function (i) {
          r(i);
        }).catch(h) : o();
      });
    }
  }, {
    key: "has",
    value: function has() {
      return this.get() === this.GRANTED;
    }
  }, {
    key: "get",
    value: function get() {
      var i = void 0;return i = this._win.Notification && this._win.Notification.permission ? this._win.Notification.permission : this._win.webkitNotifications && this._win.webkitNotifications.checkPermission ? this._permissions[this._win.webkitNotifications.checkPermission()] : navigator.mozNotification ? this.GRANTED : this._win.external && this._win.external.msIsSiteMode ? this._win.external.msIsSiteMode() ? this.GRANTED : this.DEFAULT : this.GRANTED;
    }
  }]);

  return Permission;
}();

exports.default = Permission;
;

},{}],3:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _Messages = require("./Messages");

var _Messages2 = _interopRequireDefault(_Messages);

var _Permission = require("./Permission");

var _Permission2 = _interopRequireDefault(_Permission);

var _Util = require("./Util");

var _Util2 = _interopRequireDefault(_Util);

var _DesktopAgent = require("./agents/DesktopAgent");

var _DesktopAgent2 = _interopRequireDefault(_DesktopAgent);

var _MobileChromeAgent = require("./agents/MobileChromeAgent");

var _MobileChromeAgent2 = _interopRequireDefault(_MobileChromeAgent);

var _MobileFirefoxAgent = require("./agents/MobileFirefoxAgent");

var _MobileFirefoxAgent2 = _interopRequireDefault(_MobileFirefoxAgent);

var _MSAgent = require("./agents/MSAgent");

var _MSAgent2 = _interopRequireDefault(_MSAgent);

var _WebKitAgent = require("./agents/WebKitAgent");

var _WebKitAgent2 = _interopRequireDefault(_WebKitAgent);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Push = function () {
  function Push(t) {
    _classCallCheck(this, Push);

    this._currentId = 0, this._notifications = {}, this._win = t, this.Permission = new _Permission2.default(t), this._agents = { desktop: new _DesktopAgent2.default(t), chrome: new _MobileChromeAgent2.default(t), firefox: new _MobileFirefoxAgent2.default(t), ms: new _MSAgent2.default(t), webkit: new _WebKitAgent2.default(t) }, this._configuration = { serviceWorker: "/serviceWorker.min.js", fallback: function fallback(t) {} };
  }

  _createClass(Push, [{
    key: "_closeNotification",
    value: function _closeNotification(t) {
      var i = !0;var e = this._notifications[t];if (void 0 !== e) {
        if (i = this._removeNotification(t), this._agents.desktop.isSupported()) this._agents.desktop.close(e);else if (this._agents.webkit.isSupported()) this._agents.webkit.close(e);else {
          if (!this._agents.ms.isSupported()) throw i = !1, new Error(_Messages2.default.errors.unknown_interface);this._agents.ms.close();
        }return i;
      }return !1;
    }
  }, {
    key: "_addNotification",
    value: function _addNotification(t) {
      var i = this._currentId;return this._notifications[i] = t, this._currentId++, i;
    }
  }, {
    key: "_removeNotification",
    value: function _removeNotification(t) {
      var i = !1;return this._notifications.hasOwnProperty(t) && (delete this._notifications[t], i = !0), i;
    }
  }, {
    key: "_prepareNotification",
    value: function _prepareNotification(t, i) {
      var _this = this;

      var e = void 0;return e = { get: function get() {
          return _this._notifications[t];
        }, close: function close() {
          _this._closeNotification(t);
        } }, i.timeout && setTimeout(function () {
        e.close();
      }, i.timeout), e;
    }
  }, {
    key: "_serviceWorkerCallback",
    value: function _serviceWorkerCallback(t, i, e) {
      var _this2 = this;

      var s = this._addNotification(t[t.length - 1]);navigator.serviceWorker.addEventListener("message", function (t) {
        var i = JSON.parse(t.data);"close" === i.action && Number.isInteger(i.id) && _this2._removeNotification(i.id);
      }), e(this._prepareNotification(s, i));
    }
  }, {
    key: "_createCallback",
    value: function _createCallback(t, i, e) {
      var _this3 = this;

      var s = void 0,
          o = null;if (i = i || {}, s = function s(t) {
        _this3._removeNotification(t), _Util2.default.isFunction(i.onClose) && i.onClose.call(_this3, o);
      }, this._agents.desktop.isSupported()) try {
        o = this._agents.desktop.create(t, i);
      } catch (s) {
        var _o = this._currentId,
            n = this.config().serviceWorker,
            r = function r(t) {
          return _this3._serviceWorkerCallback(t, i, e);
        };this._agents.chrome.isSupported() && this._agents.chrome.create(_o, t, i, n, r);
      } else this._agents.webkit.isSupported() ? o = this._agents.webkit.create(t, i) : this._agents.firefox.isSupported() ? this._agents.firefox.create(t, i) : this._agents.ms.isSupported() ? o = this._agents.ms.create(t, i) : (i.title = t, this.config().fallback(i));if (null !== o) {
        var _t = this._addNotification(o),
            _n = this._prepareNotification(_t, i);_Util2.default.isFunction(i.onShow) && o.addEventListener("show", i.onShow), _Util2.default.isFunction(i.onError) && o.addEventListener("error", i.onError), _Util2.default.isFunction(i.onClick) && o.addEventListener("click", i.onClick), o.addEventListener("close", function () {
          s(_t);
        }), o.addEventListener("cancel", function () {
          s(_t);
        }), e(_n);
      }e(null);
    }
  }, {
    key: "create",
    value: function create(t, i) {
      var _this4 = this;

      var e = void 0;if (!_Util2.default.isString(t)) throw new Error(_Messages2.default.errors.invalid_title);return e = this.Permission.has() ? function (e, s) {
        try {
          _this4._createCallback(t, i, e);
        } catch (t) {
          s(t);
        }
      } : function (e, s) {
        _this4.Permission.request().then(function () {
          _this4._createCallback(t, i, e);
        }).catch(function () {
          s(_Messages2.default.errors.permission_denied);
        });
      }, new Promise(e);
    }
  }, {
    key: "count",
    value: function count() {
      var t = void 0,
          i = 0;for (t in this._notifications) {
        this._notifications.hasOwnProperty(t) && i++;
      }return i;
    }
  }, {
    key: "close",
    value: function close(t) {
      var i = void 0,
          e = void 0;for (i in this._notifications) {
        if (this._notifications.hasOwnProperty(i) && (e = this._notifications[i]).tag === t) return this._closeNotification(i);
      }
    }
  }, {
    key: "clear",
    value: function clear() {
      var t = void 0,
          i = !0;for (t in this._notifications) {
        this._notifications.hasOwnProperty(t) && (i = i && this._closeNotification(t));
      }return i;
    }
  }, {
    key: "supported",
    value: function supported() {
      var t = !1;for (var i in this._agents) {
        this._agents.hasOwnProperty(i) && (t = t || this._agents[i].isSupported());
      }return t;
    }
  }, {
    key: "config",
    value: function config(t) {
      return (void 0 !== t || null !== t && _Util2.default.isObject(t)) && _Util2.default.objectMerge(this._configuration, t), this._configuration;
    }
  }, {
    key: "extend",
    value: function extend(t) {
      var i,
          e = {}.hasOwnProperty;if (!e.call(t, "plugin")) throw new Error(_Messages2.default.errors.invalid_plugin);e.call(t, "config") && _Util2.default.isObject(t.config) && null !== t.config && this.config(t.config), i = new (0, t.plugin)(this.config());for (var s in i) {
        e.call(i, s) && _Util2.default.isFunction(i[s]) && (this[s] = i[s]);
      }
    }
  }]);

  return Push;
}();

exports.default = Push;
;

},{"./Messages":1,"./Permission":2,"./Util":4,"./agents/DesktopAgent":6,"./agents/MSAgent":7,"./agents/MobileChromeAgent":8,"./agents/MobileFirefoxAgent":9,"./agents/WebKitAgent":10}],4:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Util = function () {
  function Util() {
    _classCallCheck(this, Util);
  }

  _createClass(Util, null, [{
    key: "isUndefined",
    value: function isUndefined(t) {
      return void 0 === t;
    }
  }, {
    key: "isString",
    value: function isString(t) {
      return "string" == typeof t;
    }
  }, {
    key: "isFunction",
    value: function isFunction(t) {
      return t && "[object Function]" === {}.toString.call(t);
    }
  }, {
    key: "isObject",
    value: function isObject(t) {
      return "object" == (typeof t === "undefined" ? "undefined" : _typeof(t));
    }
  }, {
    key: "objectMerge",
    value: function objectMerge(t, i) {
      for (var e in i) {
        t.hasOwnProperty(e) && this.isObject(t[e]) && this.isObject(i[e]) ? this.objectMerge(t[e], i[e]) : t[e] = i[e];
      }
    }
  }]);

  return Util;
}();

exports.default = Util;
;

},{}],5:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var AbstractAgent = function AbstractAgent(t) {
  _classCallCheck(this, AbstractAgent);

  this._win = t;
};

exports.default = AbstractAgent;
;

},{}],6:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _AbstractAgent2 = require("./AbstractAgent");

var _AbstractAgent3 = _interopRequireDefault(_AbstractAgent2);

var _Util = require("../Util");

var _Util2 = _interopRequireDefault(_Util);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var DesktopAgent = function (_AbstractAgent) {
  _inherits(DesktopAgent, _AbstractAgent);

  function DesktopAgent() {
    _classCallCheck(this, DesktopAgent);

    return _possibleConstructorReturn(this, (DesktopAgent.__proto__ || Object.getPrototypeOf(DesktopAgent)).apply(this, arguments));
  }

  _createClass(DesktopAgent, [{
    key: "isSupported",
    value: function isSupported() {
      return void 0 !== this._win.Notification;
    }
  }, {
    key: "create",
    value: function create(t, i) {
      return new this._win.Notification(t, { icon: _Util2.default.isString(i.icon) || _Util2.default.isUndefined(i.icon) ? i.icon : i.icon.x32, body: i.body, tag: i.tag, requireInteraction: i.requireInteraction });
    }
  }, {
    key: "close",
    value: function close(t) {
      t.close();
    }
  }]);

  return DesktopAgent;
}(_AbstractAgent3.default);

exports.default = DesktopAgent;
;

},{"../Util":4,"./AbstractAgent":5}],7:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _AbstractAgent2 = require("./AbstractAgent");

var _AbstractAgent3 = _interopRequireDefault(_AbstractAgent2);

var _Util = require("../Util");

var _Util2 = _interopRequireDefault(_Util);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var MSAgent = function (_AbstractAgent) {
  _inherits(MSAgent, _AbstractAgent);

  function MSAgent() {
    _classCallCheck(this, MSAgent);

    return _possibleConstructorReturn(this, (MSAgent.__proto__ || Object.getPrototypeOf(MSAgent)).apply(this, arguments));
  }

  _createClass(MSAgent, [{
    key: "isSupported",
    value: function isSupported() {
      return void 0 !== this._win.external && void 0 !== this._win.external.msIsSiteMode;
    }
  }, {
    key: "create",
    value: function create(e, t) {
      return this._win.external.msSiteModeClearIconOverlay(), this._win.external.msSiteModeSetIconOverlay(_Util2.default.isString(t.icon) || _Util2.default.isUndefined(t.icon) ? t.icon : t.icon.x16, e), this._win.external.msSiteModeActivate(), null;
    }
  }, {
    key: "close",
    value: function close() {
      this._win.external.msSiteModeClearIconOverlay();
    }
  }]);

  return MSAgent;
}(_AbstractAgent3.default);

exports.default = MSAgent;
;

},{"../Util":4,"./AbstractAgent":5}],8:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _AbstractAgent2 = require("./AbstractAgent");

var _AbstractAgent3 = _interopRequireDefault(_AbstractAgent2);

var _Util = require("../Util");

var _Util2 = _interopRequireDefault(_Util);

var _Messages = require("../Messages");

var _Messages2 = _interopRequireDefault(_Messages);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var MobileChromeAgent = function (_AbstractAgent) {
  _inherits(MobileChromeAgent, _AbstractAgent);

  function MobileChromeAgent() {
    _classCallCheck(this, MobileChromeAgent);

    return _possibleConstructorReturn(this, (MobileChromeAgent.__proto__ || Object.getPrototypeOf(MobileChromeAgent)).apply(this, arguments));
  }

  _createClass(MobileChromeAgent, [{
    key: "isSupported",
    value: function isSupported() {
      return void 0 !== this._win.navigator && void 0 !== this._win.navigator.serviceWorker;
    }
  }, {
    key: "getFunctionBody",
    value: function getFunctionBody(t) {
      return t.toString().match(/function[^{]+{([\s\S]*)}$/)[1];
    }
  }, {
    key: "create",
    value: function create(t, e, i, o, r) {
      var _this2 = this;

      this._win.navigator.serviceWorker.register(o), this._win.navigator.serviceWorker.ready.then(function (o) {
        var n = { id: t, link: i.link, origin: document.location.href, onClick: _Util2.default.isFunction(i.onClick) ? _this2.getFunctionBody(i.onClick) : "", onClose: _Util2.default.isFunction(i.onClose) ? _this2.getFunctionBody(i.onClose) : "" };void 0 !== i.data && null !== i.data && (n = Object.assign(n, i.data)), o.showNotification(e, { icon: i.icon, body: i.body, vibrate: i.vibrate, tag: i.tag, data: n, requireInteraction: i.requireInteraction, silent: i.silent }).then(function () {
          o.getNotifications().then(function (t) {
            o.active.postMessage(""), r(t);
          });
        }).catch(function (t) {
          throw new Error(_Messages2.default.errors.sw_notification_error + t.message);
        });
      }).catch(function (t) {
        throw new Error(_Messages2.default.errors.sw_registration_error + t.message);
      });
    }
  }, {
    key: "close",
    value: function close() {}
  }]);

  return MobileChromeAgent;
}(_AbstractAgent3.default);

exports.default = MobileChromeAgent;
;

},{"../Messages":1,"../Util":4,"./AbstractAgent":5}],9:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _AbstractAgent2 = require("./AbstractAgent");

var _AbstractAgent3 = _interopRequireDefault(_AbstractAgent2);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var MobileFirefoxAgent = function (_AbstractAgent) {
  _inherits(MobileFirefoxAgent, _AbstractAgent);

  function MobileFirefoxAgent() {
    _classCallCheck(this, MobileFirefoxAgent);

    return _possibleConstructorReturn(this, (MobileFirefoxAgent.__proto__ || Object.getPrototypeOf(MobileFirefoxAgent)).apply(this, arguments));
  }

  _createClass(MobileFirefoxAgent, [{
    key: "isSupported",
    value: function isSupported() {
      return void 0 !== this._win.navigator.mozNotification;
    }
  }, {
    key: "create",
    value: function create(t, i) {
      var o = this._win.navigator.mozNotification.createNotification(t, i.body, i.icon);return o.show(), o;
    }
  }]);

  return MobileFirefoxAgent;
}(_AbstractAgent3.default);

exports.default = MobileFirefoxAgent;
;

},{"./AbstractAgent":5}],10:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _AbstractAgent2 = require("./AbstractAgent");

var _AbstractAgent3 = _interopRequireDefault(_AbstractAgent2);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var WebKitAgent = function (_AbstractAgent) {
  _inherits(WebKitAgent, _AbstractAgent);

  function WebKitAgent() {
    _classCallCheck(this, WebKitAgent);

    return _possibleConstructorReturn(this, (WebKitAgent.__proto__ || Object.getPrototypeOf(WebKitAgent)).apply(this, arguments));
  }

  _createClass(WebKitAgent, [{
    key: "isSupported",
    value: function isSupported() {
      return void 0 !== this._win.webkitNotifications;
    }
  }, {
    key: "create",
    value: function create(t, e) {
      var i = this._win.webkitNotifications.createNotification(e.icon, t, e.body);return i.show(), i;
    }
  }, {
    key: "close",
    value: function close(t) {
      t.cancel();
    }
  }]);

  return WebKitAgent;
}(_AbstractAgent3.default);

exports.default = WebKitAgent;
;

},{"./AbstractAgent":5}],11:[function(require,module,exports){
"use strict";

var _Push = require("./classes/Push");

var _Push2 = _interopRequireDefault(_Push);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

module.exports = new _Push2.default("undefined" != typeof window ? window : undefined);

},{"./classes/Push":3}]},{},[11])(11)
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJzcmMvY2xhc3Nlcy9NZXNzYWdlcy5qcyIsInNyYy9jbGFzc2VzL1Blcm1pc3Npb24uanMiLCJzcmMvY2xhc3Nlcy9QdXNoLmpzIiwic3JjL2NsYXNzZXMvVXRpbC5qcyIsInNyYy9jbGFzc2VzL2FnZW50cy9BYnN0cmFjdEFnZW50LmpzIiwic3JjL2NsYXNzZXMvYWdlbnRzL0Rlc2t0b3BBZ2VudC5qcyIsInNyYy9jbGFzc2VzL2FnZW50cy9NU0FnZW50LmpzIiwic3JjL2NsYXNzZXMvYWdlbnRzL01vYmlsZUNocm9tZUFnZW50LmpzIiwic3JjL2NsYXNzZXMvYWdlbnRzL01vYmlsZUZpcmVmb3hBZ2VudC5qcyIsInNyYy9jbGFzc2VzL2FnZW50cy9XZWJLaXRBZ2VudC5qcyIsInNyYy9pbmRleC5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTs7Ozs7O0FDQUEsSUFBTSxjQUFjLGlDQUdsQixVQUNFLGtFQUNBLDBIQUNBLG9FQUNBLDZEQUNBLCtHQUNBLHdHQUNBOzs7Ozs7Ozs7Ozs7O0lDVmlCO0FBRW5CLHNCQUFZOzs7QUFDVixTQUFLLE9BQU8sR0FDWixLQUFLLFVBQVUsV0FDZixLQUFLLFVBQVUsV0FDZixLQUFLLFNBQVMsVUFDZCxLQUFLLGdCQUNILEtBQUssU0FDTCxLQUFLLFNBQ0wsS0FBSztBQVVUOzs7OzRCQUFRLEdBQVc7QUFDakIsYUFBUSxVQUFVLFNBQVMsSUFBSyxLQUFLLGlDQUF3QixhQUFhLEtBQUs7QUFVakY7Ozt5Q0FBcUIsR0FBVzs7O0FBQzlCLFVBQU0sSUFBVyxLQUFLLFVBRWpCLElBQVU7WUFBQyx3RUFBUyxNQUFLLEtBQUssYUFBYTthQUN6QixNQUFYLEtBQTBCLE1BQUssS0FBSyx3QkFDNUMsSUFBUyxNQUFLLEtBQUssb0JBQW9CLG9CQUNyQyxNQUFXLE1BQUssV0FBc0IsTUFBWCxJQUN6QixLQUFXLE1BQ04sS0FBVTtPQUx0QixDQVNHLE1B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TyxHQUFPO0FBQ1osVUFBSSxJQUFlLEtBQUssS0FBSyxvQkFBb0IsbUJBQy9DLEVBQVEsTUFDUixHQUNBLEVBQVEsTUFLVixPQUZBLEVBQWEsUUFFTjtBQU9UOzs7MEJBQU07QUFDSixRQUFhOzs7Ozs7Ozs7Ozs7O0FDdENWLEFBQVU7Ozs7OztBQUVqQixPQUFPLFVBQVUsQUFBSSxtQkFBdUIsc0JBQVgsU0FBeUIsQUFBUyIsImZpbGUiOiJnZW5lcmF0ZWQuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlc0NvbnRlbnQiOlsiKGZ1bmN0aW9uIGUodCxuLHIpe2Z1bmN0aW9uIHMobyx1KXtpZighbltvXSl7aWYoIXRbb10pe3ZhciBhPXR5cGVvZiByZXF1aXJlPT1cImZ1bmN0aW9uXCImJnJlcXVpcmU7aWYoIXUmJmEpcmV0dXJuIGEobywhMCk7aWYoaSlyZXR1cm4gaShvLCEwKTt2YXIgZj1uZXcgRXJyb3IoXCJDYW5ub3QgZmluZCBtb2R1bGUgJ1wiK28rXCInXCIpO3Rocm93IGYuY29kZT1cIk1PRFVMRV9OT1RfRk9VTkRcIixmfXZhciBsPW5bb109e2V4cG9ydHM6e319O3Rbb11bMF0uY2FsbChsLmV4cG9ydHMsZnVuY3Rpb24oZSl7dmFyIG49dFtvXVsxXVtlXTtyZXR1cm4gcyhuP246ZSl9LGwsbC5leHBvcnRzLGUsdCxuLHIpfXJldHVybiBuW29dLmV4cG9ydHN9dmFyIGk9dHlwZW9mIHJlcXVpcmU9PVwiZnVuY3Rpb25cIiYmcmVxdWlyZTtmb3IodmFyIG89MDtvPHIubGVuZ3RoO28rKylzKHJbb10pO3JldHVybiBzfSkiLCJjb25zdCBlcnJvclByZWZpeCA9ICdQdXNoRXJyb3I6JztcblxuZXhwb3J0IGRlZmF1bHQge1xuICBlcnJvcnM6IHtcbiAgICBpbmNvbXBhdGlibGU6IGAke2Vycm9yUHJlZml4fSBQdXNoLmpzIGlzIGluY29tcGF0aWJsZSB3aXRoIGJyb3dzZXIuYCxcbiAgICBpbnZhbGlkX3BsdWdpbjogYCR7ZXJyb3JQcmVmaXh9IHBsdWdpbiBjbGFzcyBtaXNzaW5nIGZyb20gcGx1Z2luIG1hbmlmZXN0IChpbnZhbGlkIHBsdWdpbikuIFBsZWFzZSBjaGVjayB0aGUgZG9jdW1lbnRhdGlvbi5gLFxuICAgIGludmFsaWRfdGl0bGU6IGAke2Vycm9yUHJlZml4fSB0aXRsZSBvZiBub3RpZmljYXRpb24gbXVzdCBiZSBhIHN0cmluZ2AsXG4gICAgcGVybWlzc2lvbl9kZW5pZWQ6IGAke2Vycm9yUHJlZml4fSBwZXJtaXNzaW9uIHJlcXVlc3QgZGVjbGluZWRgLFxuICAgIHN3X25vdGlmaWNhdGlvbl9lcnJvcjogYCR7ZXJyb3JQcmVmaXh9IGNvdWxkIG5vdCBzaG93IGEgU2VydmljZVdvcmtlciBub3RpZmljYXRpb24gZHVlIHRvIHRoZSBmb2xsb3dpbmcgcmVhc29uOiBgLFxuICAgIHN3X3JlZ2lzdHJhdGlvbl9lcnJvcjogYCR7ZXJyb3JQcmVmaXh9IGNvdWxkIG5vdCByZWdpc3RlciB0aGUgU2VydmljZVdvcmtlciBkdWUgdG8gdGhlIGZvbGxvd2luZyByZWFzb246IGAsXG4gICAgdW5rbm93bl9pbnRlcmZhY2U6IGAke2Vycm9yUHJlZml4fSB1bmFibGUgdG8gY3JlYXRlIG5vdGlmaWNhdGlvbjogdW5rbm93biBpbnRlcmZhY2VgLFxuICB9XG59XG4iLCJleHBvcnQgZGVmYXVsdCBjbGFzcyBQZXJtaXNzaW9uIHtcblxuICBjb25zdHJ1Y3Rvcih3aW4pIHtcbiAgICB0aGlzLl93aW4gPSB3aW47XG4gICAgdGhpcy5HUkFOVEVEID0gJ2dyYW50ZWQnO1xuICAgIHRoaXMuREVGQVVMVCA9ICdkZWZhdWx0JztcbiAgICB0aGlzLkRFTklFRCA9ICdkZW5pZWQnO1xuICAgIHRoaXMuX3Blcm1pc3Npb25zID0gW1xuICAgICAgdGhpcy5HUkFOVEVELFxuICAgICAgdGhpcy5ERUZBVUxULFxuICAgICAgdGhpcy5ERU5JRURcbiAgICBdO1xuICB9XG5cbiAgLyoqXG4gICAqIFJlcXVlc3RzIHBlcm1pc3Npb24gZm9yIGRlc2t0b3Agbm90aWZpY2F0aW9uc1xuICAgKiBAcGFyYW0ge0Z1bmN0aW9ufSBvbkdyYW50ZWQgLSBGdW5jdGlvbiB0byBleGVjdXRlIG9uY2UgcGVybWlzc2lvbiBpcyBncmFudGVkXG4gICAqIEBwYXJhbSB7RnVuY3Rpb259IG9uRGVuaWVkIC0gRnVuY3Rpb24gdG8gZXhlY3V0ZSBvbmNlIHBlcm1pc3Npb24gaXMgZGVuaWVkXG4gICAqIEByZXR1cm4ge3ZvaWQsIFByb21pc2V9XG4gICAqL1xuICByZXF1ZXN0KG9uR3JhbnRlZCwgb25EZW5pZWQpIHtcbiAgICByZXR1cm4gKGFyZ3VtZW50cy5sZW5ndGggPiAwKSA/IHRoaXMuX3JlcXVlc3RXaXRoQ2FsbGJhY2soLi4uYXJndW1lbnRzKSA6IHRoaXMuX3JlcXVlc3RBc1Byb21pc2UoKTtcbiAgfVxuXG4gIC8qKlxuICAgKiBPbGQgcGVybWlzc2lvbnMgaW1wbGVtZW50YXRpb24gZGVwcmVjYXRlZCBpbiBmYXZvciBvZiBhIHByb21pc2UgYmFzZWQgb25lXG4gICAqIEBkZXByZWNhdGVkIFNpbmNlIFYxLjAuNFxuICAgKiBAcGFyYW0ge0Z1bmN0aW9ufSBvbkdyYW50ZWQgLSBGdW5jdGlvbiB0byBleGVjdXRlIG9uY2UgcGVybWlzc2lvbiBpcyBncmFudGVkXG4gICAqIEBwYXJhbSB7RnVuY3Rpb259IG9uRGVuaWVkIC0gRnVuY3Rpb24gdG8gZXhlY3V0ZSBvbmNlIHBlcm1pc3Npb24gaXMgZGVuaWVkXG4gICAqIEByZXR1cm4ge3ZvaWR9XG4gICAqL1xuICBfcmVxdWVzdFdpdGhDYWxsYmFjayhvbkdyYW50ZWQsIG9uRGVuaWVkKSB7XG4gICAgY29uc3QgZXhpc3RpbmcgPSB0aGlzLmdldCgpO1xuXG4gICAgIHZhciByZXNvbHZlID0gKHJlc3VsdCA9IHRoaXMuX3dpbi5Ob3RpZmljYXRpb24ucGVybWlzc2lvbikgPT4ge1xuICAgICAgaWYgKHR5cGVvZihyZXN1bHQpPT09J3VuZGVmaW5lZCcgJiYgdGhpcy5fd2luLndlYmtpdE5vdGlmaWNhdGlvbnMpXG4gICAgICAgIHJlc3VsdCA9IHRoaXMuX3dpbi53ZWJraXROb3RpZmljYXRpb25zLmNoZWNrUGVybWlzc2lvbigpO1xuICAgICAgaWYgKHJlc3VsdCA9PT0gdGhpcy5HUkFOVEVEIHx8IHJlc3VsdCA9PT0gMCkge1xuICAgICAgICBpZiAob25HcmFudGVkKSBvbkdyYW50ZWQoKTtcbiAgICAgIH0gZWxzZSBpZiAob25EZW5pZWQpIG9uRGVuaWVkKCk7XG4gICAgfVxuXG4gICAgLyogUGVybWlzc2lvbnMgYWxyZWFkeSBzZXQgKi9cbiAgICBpZiAoZXhpc3RpbmcgIT09IHRoaXMuREVGQVVMVCkge1xuICAgICAgcmVzb2x2ZShleGlzdGluZyk7XG4gICAgfVxuICAgIC8qIFNhZmFyaSA2KywgTGVnYWN5IHdlYmtpdCBicm93c2VycyAqL1xuICAgIGVsc2UgaWYgKHRoaXMuX3dpbi53ZWJraXROb3RpZmljYXRpb25zICYmIHRoaXMuX3dpbi53ZWJraXROb3RpZmljYXRpb25zLmNoZWNrUGVybWlzc2lvbikge1xuICAgICAgdGhpcy5fd2luLndlYmtpdE5vdGlmaWNhdGlvbnMucmVxdWVzdFBlcm1pc3Npb24ocmVzb2x2ZSk7XG4gICAgfVxuICAgIC8qIENocm9tZSAyMysgKi9cbiAgICBlbHNlIGlmICh0aGlzLl93aW4uTm90aWZpY2F0aW9uICYmIHRoaXMuX3dpbi5Ob3RpZmljYXRpb24ucmVxdWVzdFBlcm1pc3Npb24pIHtcbiAgICAgIHRoaXMuX3dpbi5Ob3RpZmljYXRpb24ucmVxdWVzdFBlcm1pc3Npb24oKS50aGVuKHJlc29sdmUpLmNhdGNoKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKG9uRGVuaWVkKSBvbkRlbmllZCgpO1xuICAgICAgfSk7XG4gICAgfVxuICAgIC8qIExldCB0aGUgdXNlciBjb250aW51ZSBieSBkZWZhdWx0ICovXG4gICAgZWxzZSBpZiAob25HcmFudGVkKSB7XG4gICAgICBvbkdyYW50ZWQoKTtcbiAgICB9XG4gIH1cblxuICAvKipcbiAgICogUmVxdWVzdHMgcGVybWlzc2lvbiBmb3IgZGVza3RvcCBub3RpZmljYXRpb25zIGluIGEgcHJvbWlzZSBiYXNlZCB3YXlcbiAgICogQHJldHVybiB7UHJvbWlzZX1cbiAgICovXG4gIF9yZXF1ZXN0QXNQcm9taXNlKCkge1xuICAgIGNvbnN0IGV4aXN0aW5nID0gdGhpcy5nZXQoKTtcblxuICAgIGxldCBpc0dyYW50ZWQgPSByZXN1bHQgPT4gKHJlc3VsdCA9PT0gdGhpcy5HUkFOVEVEIHx8IHJlc3VsdCA9PT0gMCk7XG5cbiAgICAvKiBQZXJtaXNzaW9ucyBhbHJlYWR5IHNldCAqL1xuICAgIHZhciBoYXNQZXJtaXNzaW9ucyA9IChleGlzdGluZyAhPT0gdGhpcy5ERUZBVUxUKTtcblxuICAgIC8qIFNhZmFyaSA2KywgQ2hyb21lIDIzKyAqL1xuICAgIHZhciBpc01vZGVybkFQSSA9ICh0aGlzLl93aW4uTm90aWZpY2F0aW9uICYmIHRoaXMuX3dpbi5Ob3RpZmljYXRpb24ucmVxdWVzdFBlcm1pc3Npb24pO1xuXG4gICAgLyogTGVnYWN5IHdlYmtpdCBicm93c2VycyAqL1xuICAgIHZhciBpc1dlYmtpdEFQSSA9ICh0aGlzLl93aW4ud2Via2l0Tm90aWZpY2F0aW9ucyAmJiB0aGlzLl93aW4ud2Via2l0Tm90aWZpY2F0aW9ucy5jaGVja1Blcm1pc3Npb24pO1xuXG4gICAgcmV0dXJuIG5ldyBQcm9taXNlKChyZXNvbHZlUHJvbWlzZSwgcmVqZWN0UHJvbWlzZSkgPT4ge1xuXG4gICAgICB2YXIgcmVzb2x2ZXIgPSByZXN1bHQgPT4gKGlzR3JhbnRlZChyZXN1bHQpKSA/IHJlc29sdmVQcm9taXNlKCkgOiByZWplY3RQcm9taXNlKCk7XG5cbiAgICAgIGlmIChoYXNQZXJtaXNzaW9ucykge1xuICAgICAgIHJlc29sdmVyKGV4aXN0aW5nKVxuICAgICAgfVxuICAgICAgZWxzZSBpZiAoaXNXZWJraXRBUEkpIHtcbiAgICAgICAgdGhpcy5fd2luLndlYmtpdE5vdGlmaWNhdGlvbnMucmVxdWVzdFBlcm1pc3Npb24ocmVzdWx0ID0+IHsgcmVzb2x2ZXIocmVzdWx0KSB9KTtcbiAgICAgIH1cbiAgICAgIGVsc2UgaWYgKGlzTW9kZXJuQVBJKSB7XG4gICAgICAgIHRoaXMuX3dpbi5Ob3RpZmljYXRpb24ucmVxdWVzdFBlcm1pc3Npb24oKS50aGVuKHJlc3VsdCA9PiB7IHJlc29sdmVyKHJlc3VsdCkgfSkuY2F0Y2gocmVqZWN0UHJvbWlzZSlcbiAgICAgIH1cbiAgICAgIGVsc2UgcmVzb2x2ZVByb21pc2UoKVxuICAgIH0pXG4gIH1cblxuICAvKipcbiAgICogUmV0dXJucyB3aGV0aGVyIFB1c2ggaGFzIGJlZW4gZ3JhbnRlZCBwZXJtaXNzaW9uIHRvIHJ1blxuICAgKiBAcmV0dXJuIHtCb29sZWFufVxuICAgKi9cbiAgaGFzKCkge1xuICAgIHJldHVybiB0aGlzLmdldCgpID09PSB0aGlzLkdSQU5URUQ7XG4gIH1cblxuICAvKipcbiAgICogR2V0cyB0aGUgcGVybWlzc2lvbiBsZXZlbFxuICAgKiBAcmV0dXJuIHtQZXJtaXNzaW9ufSBUaGUgcGVybWlzc2lvbiBsZXZlbFxuICAgKi9cbiAgZ2V0KCkge1xuICAgIGxldCBwZXJtaXNzaW9uO1xuXG4gICAgLyogU2FmYXJpIDYrLCBDaHJvbWUgMjMrICovXG4gICAgaWYgKHRoaXMuX3dpbi5Ob3RpZmljYXRpb24gJiYgdGhpcy5fd2luLk5vdGlmaWNhdGlvbi5wZXJtaXNzaW9uKVxuICAgICAgcGVybWlzc2lvbiA9IHRoaXMuX3dpbi5Ob3RpZmljYXRpb24ucGVybWlzc2lvbjtcblxuICAgIC8qIExlZ2FjeSB3ZWJraXQgYnJvd3NlcnMgKi9cbiAgICBlbHNlIGlmICh0aGlzLl93aW4ud2Via2l0Tm90aWZpY2F0aW9ucyAmJiB0aGlzLl93aW4ud2Via2l0Tm90aWZpY2F0aW9ucy5jaGVja1Blcm1pc3Npb24pXG4gICAgICBwZXJtaXNzaW9uID0gdGhpcy5fcGVybWlzc2lvbnNbdGhpcy5fd2luLndlYmtpdE5vdGlmaWNhdGlvbnMuY2hlY2tQZXJtaXNzaW9uKCldO1xuXG4gICAgLyogRmlyZWZveCBNb2JpbGUgKi9cbiAgICBlbHNlIGlmIChuYXZpZ2F0b3IubW96Tm90aWZpY2F0aW9uKVxuICAgICAgcGVybWlzc2lvbiA9IHRoaXMuR1JBTlRFRDtcblxuICAgIC8qIElFOSsgKi9cbiAgICBlbHNlIGlmICh0aGlzLl93aW4uZXh0ZXJuYWwgJiYgdGhpcy5fd2luLmV4dGVybmFsLm1zSXNTaXRlTW9kZSlcbiAgICAgIHBlcm1pc3Npb24gPSB0aGlzLl93aW4uZXh0ZXJuYWwubXNJc1NpdGVNb2RlKCkgPyB0aGlzLkdSQU5URUQgOiB0aGlzLkRFRkFVTFQ7XG5cbiAgICBlbHNlXG4gICAgICBwZXJtaXNzaW9uID0gdGhpcy5HUkFOVEVEO1xuXG4gICAgcmV0dXJuIHBlcm1pc3Npb247XG4gIH1cbn1cbiIsImltcG9ydCBNZXNzYWdlcyBmcm9tIFwiLi9NZXNzYWdlc1wiO1xuaW1wb3J0IFBlcm1pc3Npb24gZnJvbSBcIi4vUGVybWlzc2lvblwiO1xuaW1wb3J0IFV0aWwgZnJvbSBcIi4vVXRpbFwiO1xuLyogSW1wb3J0IG5vdGlmaWNhdGlvbiBhZ2VudHMgKi9cbmltcG9ydCBEZXNrdG9wQWdlbnQgZnJvbSBcIi4vYWdlbnRzL0Rlc2t0b3BBZ2VudFwiO1xuaW1wb3J0IE1vYmlsZUNocm9tZUFnZW50IGZyb20gXCIuL2FnZW50cy9Nb2JpbGVDaHJvbWVBZ2VudFwiO1xuaW1wb3J0IE1vYmlsZUZpcmVmb3hBZ2VudCBmcm9tIFwiLi9hZ2VudHMvTW9iaWxlRmlyZWZveEFnZW50XCI7XG5pbXBvcnQgTVNBZ2VudCBmcm9tIFwiLi9hZ2VudHMvTVNBZ2VudFwiO1xuaW1wb3J0IFdlYktpdEFnZW50IGZyb20gXCIuL2FnZW50cy9XZWJLaXRBZ2VudFwiO1xuXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBQdXNoIHtcblxuICBjb25zdHJ1Y3Rvcih3aW4pIHtcbiAgICAvKiBQcml2YXRlIHZhcmlhYmxlcyAqL1xuXG4gICAgLyogSUQgdG8gdXNlIGZvciBuZXcgbm90aWZpY2F0aW9ucyAqL1xuICAgIHRoaXMuX2N1cnJlbnRJZCA9IDA7XG5cbiAgICAvKiBNYXAgb2Ygb3BlbiBub3RpZmljYXRpb25zICovXG4gICAgdGhpcy5fbm90aWZpY2F0aW9ucyA9IHt9O1xuXG4gICAgLyogV2luZG93IG9iamVjdCAqL1xuICAgIHRoaXMuX3dpbiA9IHdpbjtcblxuICAgIC8qIFB1YmxpYyB2YXJpYWJsZXMgKi9cbiAgICB0aGlzLlBlcm1pc3Npb24gPSBuZXcgUGVybWlzc2lvbih3aW4pO1xuXG4gICAgLyogQWdlbnRzICovXG4gICAgdGhpcy5fYWdlbnRzID0ge1xuICAgICAgZGVza3RvcDogbmV3IERlc2t0b3BBZ2VudCh3aW4pLFxuICAgICAgY2hyb21lOiBuZXcgTW9iaWxlQ2hyb21lQWdlbnQod2luKSxcbiAgICAgIGZpcmVmb3g6IG5ldyBNb2JpbGVGaXJlZm94QWdlbnQod2luKSxcbiAgICAgIG1zOiBuZXcgTVNBZ2VudCh3aW4pLFxuICAgICAgd2Via2l0OiBuZXcgV2ViS2l0QWdlbnQod2luKVxuICAgIH07XG5cbiAgICB0aGlzLl9jb25maWd1cmF0aW9uID0ge1xuICAgICAgc2VydmljZVdvcmtlcjogJy9zZXJ2aWNlV29ya2VyLm1pbi5qcycsXG4gICAgICBmYWxsYmFjazogZnVuY3Rpb24ocGF5bG9hZCkge31cbiAgICB9XG4gIH1cblxuICAvKipcbiAgICogQ2xvc2VzIGEgbm90aWZpY2F0aW9uXG4gICAqIEBwYXJhbSB7Tm90aWZpY2F0aW9ufSBub3RpZmljYXRpb25cbiAgICogQHJldHVybiB7Qm9vbGVhbn0gYm9vbGVhbiBkZW5vdGluZyB3aGV0aGVyIHRoZSBvcGVyYXRpb24gd2FzIHN1Y2Nlc3NmdWxcbiAgICogQHByaXZhdGVcbiAgICovXG4gIF9jbG9zZU5vdGlmaWNhdGlvbihpZCkge1xuICAgIGxldCBzdWNjZXNzID0gdHJ1ZTtcbiAgICBjb25zdCBub3RpZmljYXRpb24gPSB0aGlzLl9ub3RpZmljYXRpb25zW2lkXTtcblxuICAgIGlmIChub3RpZmljYXRpb24gIT09IHVuZGVmaW5lZCkge1xuICAgICAgc3VjY2VzcyA9IHRoaXMuX3JlbW92ZU5vdGlmaWNhdGlvbihpZCk7XG5cbiAgICAgIC8qIFNhZmFyaSA2KywgRmlyZWZveCAyMissIENocm9tZSAyMissIE9wZXJhIDI1KyAqL1xuICAgICAgaWYgKHRoaXMuX2FnZW50cy5kZXNrdG9wLmlzU3VwcG9ydGVkKCkpXG4gICAgICAgIHRoaXMuX2FnZW50cy5kZXNrdG9wLmNsb3NlKG5vdGlmaWNhdGlvbik7XG5cbiAgICAgIC8qIExlZ2FjeSBXZWJLaXQgYnJvd3NlcnMgKi9cbiAgICAgIGVsc2UgaWYgKHRoaXMuX2FnZW50cy53ZWJraXQuaXNTdXBwb3J0ZWQoKSlcbiAgICAgICAgdGhpcy5fYWdlbnRzLndlYmtpdC5jbG9zZShub3RpZmljYXRpb24pO1xuXG4gICAgICAvKiBJRTkgKi9cbiAgICAgIGVsc2UgaWYgKHRoaXMuX2FnZW50cy5tcy5pc1N1cHBvcnRlZCgpKVxuICAgICAgICB0aGlzLl9hZ2VudHMubXMuY2xvc2UoKTtcblxuICAgICAgZWxzZSB7XG4gICAgICAgIHN1Y2Nlc3MgPSBmYWxzZTtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKE1lc3NhZ2VzLmVycm9ycy51bmtub3duX2ludGVyZmFjZSk7XG4gICAgICB9XG5cbiAgICAgIHJldHVybiBzdWNjZXNzO1xuICAgIH1cblxuICAgIHJldHVybiBmYWxzZTtcbiAgfVxuXG4gIC8qKlxuICAgKiBBZGRzIGEgbm90aWZpY2F0aW9uIHRvIHRoZSBnbG9iYWwgZGljdGlvbmFyeSBvZiBub3RpZmljYXRpb25zXG4gICAqIEBwYXJhbSB7Tm90aWZpY2F0aW9ufSBub3RpZmljYXRpb25cbiAgICogQHJldHVybiB7SW50ZWdlcn0gRGljdGlvbmFyeSBrZXkgb2YgdGhlIG5vdGlmaWNhdGlvblxuICAgKiBAcHJpdmF0ZVxuICAgKi9cbiAgX2FkZE5vdGlmaWNhdGlvbihub3RpZmljYXRpb24pIHtcbiAgICBjb25zdCBpZCA9IHRoaXMuX2N1cnJlbnRJZDtcbiAgICB0aGlzLl9ub3RpZmljYXRpb25zW2lkXSA9IG5vdGlmaWNhdGlvbjtcbiAgICB0aGlzLl9jdXJyZW50SWQrKztcbiAgICByZXR1cm4gaWQ7XG4gIH1cblxuICAvKipcbiAgICogUmVtb3ZlcyBhIG5vdGlmaWNhdGlvbiB3aXRoIHRoZSBnaXZlbiBJRFxuICAgKiBAcGFyYW0gIHtJbnRlZ2VyfSBpZCAtIERpY3Rpb25hcnkga2V5L0lEIG9mIHRoZSBub3RpZmljYXRpb24gdG8gcmVtb3ZlXG4gICAqIEByZXR1cm4ge0Jvb2xlYW59IGJvb2xlYW4gZGVub3Rpbmcgc3VjY2Vzc1xuICAgKiBAcHJpdmF0ZVxuICAgKi9cbiAgX3JlbW92ZU5vdGlmaWNhdGlvbihpZCkge1xuICAgIGxldCBzdWNjZXNzID0gZmFsc2U7XG5cbiAgICBpZiAodGhpcy5fbm90aWZpY2F0aW9ucy5oYXNPd25Qcm9wZXJ0eShpZCkpIHtcbiAgICAgIC8qIFdlJ3JlIHN1Y2Nlc3NmdWwgaWYgd2Ugb21pdCB0aGUgZ2l2ZW4gSUQgZnJvbSB0aGUgbmV3IGFycmF5ICovXG4gICAgICBkZWxldGUgdGhpcy5fbm90aWZpY2F0aW9uc1tpZF07XG4gICAgICBzdWNjZXNzID0gdHJ1ZTtcbiAgICB9XG5cbiAgICByZXR1cm4gc3VjY2VzcztcbiAgfVxuXG4gIC8qKlxuICAgKiBDcmVhdGVzIHRoZSB3cmFwcGVyIGZvciBhIGdpdmVuIG5vdGlmaWNhdGlvblxuICAgKlxuICAgKiBAcGFyYW0ge0ludGVnZXJ9IGlkIC0gRGljdGlvbmFyeSBrZXkvSUQgb2YgdGhlIG5vdGlmaWNhdGlvblxuICAgKiBAcGFyYW0ge01hcH0gb3B0aW9ucyAtIE9wdGlvbnMgdXNlZCB0byBjcmVhdGUgdGhlIG5vdGlmaWNhdGlvblxuICAgKiBAcmV0dXJucyB7TWFwfSB3cmFwcGVyIGhhc2htYXAgb2JqZWN0XG4gICAqIEBwcml2YXRlXG4gICAqL1xuICBfcHJlcGFyZU5vdGlmaWNhdGlvbihpZCwgb3B0aW9ucykge1xuICAgIGxldCB3cmFwcGVyO1xuXG4gICAgLyogV3JhcHBlciB1c2VkIHRvIGdldC9jbG9zZSBub3RpZmljYXRpb24gbGF0ZXIgb24gKi9cbiAgICB3cmFwcGVyID0ge1xuICAgICAgZ2V0OiAoKSA9PiB7XG4gICAgICAgIHJldHVybiB0aGlzLl9ub3RpZmljYXRpb25zW2lkXTtcbiAgICAgIH0sXG5cbiAgICAgIGNsb3NlOiAoKSA9PiB7XG4gICAgICAgIHRoaXMuX2Nsb3NlTm90aWZpY2F0aW9uKGlkKTtcbiAgICAgIH1cbiAgICB9O1xuXG4gICAgLyogQXV0b2Nsb3NlIHRpbWVvdXQgKi9cbiAgICBpZiAob3B0aW9ucy50aW1lb3V0KSB7XG4gICAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgd3JhcHBlci5jbG9zZSgpO1xuICAgICAgfSwgb3B0aW9ucy50aW1lb3V0KTtcbiAgICB9XG5cbiAgICByZXR1cm4gd3JhcHBlcjtcbiAgfVxuXG4gIC8qKlxuICAgKiBGaW5kIHRoZSBtb3N0IHJlY2VudCBub3RpZmljYXRpb24gZnJvbSBhIFNlcnZpY2VXb3JrZXIgYW5kIGFkZCBpdCB0byB0aGUgZ2xvYmFsIGFycmF5XG4gICAqIEBwYXJhbSBub3RpZmljYXRpb25zXG4gICAqIEBwcml2YXRlXG4gICAqL1xuICBfc2VydmljZVdvcmtlckNhbGxiYWNrKG5vdGlmaWNhdGlvbnMsIG9wdGlvbnMsIHJlc29sdmUpIHtcbiAgICBsZXQgaWQgPSB0aGlzLl9hZGROb3RpZmljYXRpb24obm90aWZpY2F0aW9uc1tub3RpZmljYXRpb25zLmxlbmd0aCAtIDFdKTtcblxuICAgIC8qIExpc3RlbiBmb3IgY2xvc2UgcmVxdWVzdHMgZnJvbSB0aGUgU2VydmljZVdvcmtlciAqL1xuICAgIG5hdmlnYXRvci5zZXJ2aWNlV29ya2VyLmFkZEV2ZW50TGlzdGVuZXIoJ21lc3NhZ2UnLCBldmVudCA9PiB7XG4gICAgICBjb25zdCBkYXRhID0gSlNPTi5wYXJzZShldmVudC5kYXRhKTtcblxuICAgICAgaWYgKGRhdGEuYWN0aW9uID09PSAnY2xvc2UnICYmIE51bWJlci5pc0ludGVnZXIoZGF0YS5pZCkpXG4gICAgICAgIHRoaXMuX3JlbW92ZU5vdGlmaWNhdGlvbihkYXRhLmlkKTtcbiAgICB9KTtcblxuICAgIHJlc29sdmUodGhpcy5fcHJlcGFyZU5vdGlmaWNhdGlvbihpZCwgb3B0aW9ucykpO1xuICB9XG5cbiAgLyoqXG4gICAqIENhbGxiYWNrIGZ1bmN0aW9uIGZvciB0aGUgJ2NyZWF0ZScgbWV0aG9kXG4gICAqIEByZXR1cm4ge3ZvaWR9XG4gICAqIEBwcml2YXRlXG4gICAqL1xuICBfY3JlYXRlQ2FsbGJhY2sodGl0bGUsIG9wdGlvbnMsIHJlc29sdmUpIHtcbiAgICBsZXQgbm90aWZpY2F0aW9uID0gbnVsbDtcbiAgICBsZXQgb25DbG9zZTtcblxuICAgIC8qIFNldCBlbXB0eSBzZXR0aW5ncyBpZiBub25lIGFyZSBzcGVjaWZpZWQgKi9cbiAgICBvcHRpb25zID0gb3B0aW9ucyB8fCB7fTtcblxuICAgIC8qIG9uQ2xvc2UgZXZlbnQgaGFuZGxlciAqL1xuICAgIG9uQ2xvc2UgPSAoaWQpID0+IHtcbiAgICAgIC8qIEEgYml0IHJlZHVuZGFudCwgYnV0IGNvdmVycyB0aGUgY2FzZXMgd2hlbiBjbG9zZSgpIGlzbid0IGV4cGxpY2l0bHkgY2FsbGVkICovXG4gICAgICB0aGlzLl9yZW1vdmVOb3RpZmljYXRpb24oaWQpO1xuICAgICAgaWYgKFV0aWwuaXNGdW5jdGlvbihvcHRpb25zLm9uQ2xvc2UpKSB7XG4gICAgICAgIG9wdGlvbnMub25DbG9zZS5jYWxsKHRoaXMsIG5vdGlmaWNhdGlvbik7XG4gICAgICB9XG4gICAgfTtcblxuICAgIC8qIFNhZmFyaSA2KywgRmlyZWZveCAyMissIENocm9tZSAyMissIE9wZXJhIDI1KyAqL1xuICAgIGlmICh0aGlzLl9hZ2VudHMuZGVza3RvcC5pc1N1cHBvcnRlZCgpKSB7XG4gICAgICB0cnkge1xuICAgICAgICAvKiBDcmVhdGUgYSBub3RpZmljYXRpb24gdXNpbmcgdGhlIEFQSSBpZiBwb3NzaWJsZSAqL1xuICAgICAgICBub3RpZmljYXRpb24gPSB0aGlzLl9hZ2VudHMuZGVza3RvcC5jcmVhdGUodGl0bGUsIG9wdGlvbnMpO1xuICAgICAgfSBjYXRjaCAoZSkge1xuICAgICAgICBjb25zdCBpZCA9IHRoaXMuX2N1cnJlbnRJZDtcbiAgICAgICAgY29uc3Qgc3cgPSB0aGlzLmNvbmZpZygpLnNlcnZpY2VXb3JrZXI7XG4gICAgICAgIGNvbnN0IGNiID0gKG5vdGlmaWNhdGlvbnMpID0+IHRoaXMuX3NlcnZpY2VXb3JrZXJDYWxsYmFjayhub3RpZmljYXRpb25zLCBvcHRpb25zLCByZXNvbHZlKTtcbiAgICAgICAgLyogQ3JlYXRlIGEgQ2hyb21lIFNlcnZpY2VXb3JrZXIgbm90aWZpY2F0aW9uIGlmIGl0IGlzbid0IHN1cHBvcnRlZCAqL1xuICAgICAgICBpZiAodGhpcy5fYWdlbnRzLmNocm9tZS5pc1N1cHBvcnRlZCgpKSB7XG4gICAgICAgICAgdGhpcy5fYWdlbnRzLmNocm9tZS5jcmVhdGUoaWQsIHRpdGxlLCBvcHRpb25zLCBzdywgY2IpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgICAvKiBMZWdhY3kgV2ViS2l0IGJyb3dzZXJzICovXG4gICAgfSBlbHNlIGlmICh0aGlzLl9hZ2VudHMud2Via2l0LmlzU3VwcG9ydGVkKCkpXG4gICAgICBub3RpZmljYXRpb24gPSB0aGlzLl9hZ2VudHMud2Via2l0LmNyZWF0ZSh0aXRsZSwgb3B0aW9ucyk7XG5cbiAgICAvKiBGaXJlZm94IE1vYmlsZSAqL1xuICAgIGVsc2UgaWYgKHRoaXMuX2FnZW50cy5maXJlZm94LmlzU3VwcG9ydGVkKCkpXG4gICAgICB0aGlzLl9hZ2VudHMuZmlyZWZveC5jcmVhdGUodGl0bGUsIG9wdGlvbnMpO1xuXG4gICAgLyogSUU5ICovXG4gICAgZWxzZSBpZiAodGhpcy5fYWdlbnRzLm1zLmlzU3VwcG9ydGVkKCkpXG4gICAgICBub3RpZmljYXRpb24gPSB0aGlzLl9hZ2VudHMubXMuY3JlYXRlKHRpdGxlLCBvcHRpb25zKTtcblxuICAgIC8qIERlZmF1bHQgZmFsbGJhY2sgKi9cbiAgICBlbHNlIHtcbiAgICAgIG9wdGlvbnMudGl0bGUgPSB0aXRsZTtcbiAgICAgIHRoaXMuY29uZmlnKCkuZmFsbGJhY2sob3B0aW9ucyk7XG4gICAgfVxuXG4gICAgaWYgKG5vdGlmaWNhdGlvbiAhPT0gbnVsbCkge1xuICAgICAgY29uc3QgaWQgPSB0aGlzLl9hZGROb3RpZmljYXRpb24obm90aWZpY2F0aW9uKTtcbiAgICAgIGNvbnN0IHdyYXBwZXIgPSB0aGlzLl9wcmVwYXJlTm90aWZpY2F0aW9uKGlkLCBvcHRpb25zKTtcblxuICAgICAgLyogTm90aWZpY2F0aW9uIGNhbGxiYWNrcyAqL1xuICAgICAgaWYgKFV0aWwuaXNGdW5jdGlvbihvcHRpb25zLm9uU2hvdykpXG4gICAgICAgIG5vdGlmaWNhdGlvbi5hZGRFdmVudExpc3RlbmVyKCdzaG93Jywgb3B0aW9ucy5vblNob3cpO1xuXG4gICAgICBpZiAoVXRpbC5pc0Z1bmN0aW9uKG9wdGlvbnMub25FcnJvcikpXG4gICAgICAgIG5vdGlmaWNhdGlvbi5hZGRFdmVudExpc3RlbmVyKCdlcnJvcicsIG9wdGlvbnMub25FcnJvcik7XG5cbiAgICAgIGlmIChVdGlsLmlzRnVuY3Rpb24ob3B0aW9ucy5vbkNsaWNrKSlcbiAgICAgICAgbm90aWZpY2F0aW9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgb3B0aW9ucy5vbkNsaWNrKTtcblxuICAgICAgbm90aWZpY2F0aW9uLmFkZEV2ZW50TGlzdGVuZXIoJ2Nsb3NlJywgKCkgPT4ge1xuICAgICAgICBvbkNsb3NlKGlkKTtcbiAgICAgIH0pO1xuXG4gICAgICBub3RpZmljYXRpb24uYWRkRXZlbnRMaXN0ZW5lcignY2FuY2VsJywgKCkgPT4ge1xuICAgICAgICBvbkNsb3NlKGlkKTtcbiAgICAgIH0pO1xuXG4gICAgICAvKiBSZXR1cm4gdGhlIHdyYXBwZXIgc28gdGhlIHVzZXIgY2FuIGNhbGwgY2xvc2UoKSAqL1xuICAgICAgcmVzb2x2ZSh3cmFwcGVyKTtcbiAgICB9XG5cbiAgICAvKiBCeSBkZWZhdWx0LCBwYXNzIGFuIGVtcHR5IHdyYXBwZXIgKi9cbiAgICByZXNvbHZlKG51bGwpO1xuICB9XG5cbiAgLyoqXG4gICAqIENyZWF0ZXMgYW5kIGRpc3BsYXlzIGEgbmV3IG5vdGlmaWNhdGlvblxuICAgKiBAcGFyYW0ge0FycmF5fSBvcHRpb25zXG4gICAqIEByZXR1cm4ge1Byb21pc2V9XG4gICAqL1xuICBjcmVhdGUodGl0bGUsIG9wdGlvbnMpIHtcbiAgICBsZXQgcHJvbWlzZUNhbGxiYWNrO1xuXG4gICAgLyogRmFpbCBpZiBubyBvciBhbiBpbnZhbGlkIHRpdGxlIGlzIHByb3ZpZGVkICovXG4gICAgaWYgKCFVdGlsLmlzU3RyaW5nKHRpdGxlKSkge1xuICAgICAgdGhyb3cgbmV3IEVycm9yKE1lc3NhZ2VzLmVycm9ycy5pbnZhbGlkX3RpdGxlKTtcbiAgICB9XG5cbiAgICAvKiBSZXF1ZXN0IHBlcm1pc3Npb24gaWYgaXQgaXNuJ3QgZ3JhbnRlZCAqL1xuICAgIGlmICghdGhpcy5QZXJtaXNzaW9uLmhhcygpKSB7XG4gICAgICBwcm9taXNlQ2FsbGJhY2sgPSAocmVzb2x2ZSwgcmVqZWN0KSA9PiB7XG4gICAgICAgIHRoaXMuUGVybWlzc2lvbi5yZXF1ZXN0KCkudGhlbigoKSA9PiB7XG4gICAgICAgICAgdGhpcy5fY3JlYXRlQ2FsbGJhY2sodGl0bGUsIG9wdGlvbnMsIHJlc29sdmUpO1xuICAgICAgICB9KS5jYXRjaCgoKSA9PiB7XG4gICAgICAgICAgcmVqZWN0KE1lc3NhZ2VzLmVycm9ycy5wZXJtaXNzaW9uX2RlbmllZCk7XG4gICAgICAgIH0pXG4gICAgICB9O1xuICAgIH0gZWxzZSB7XG4gICAgICBwcm9taXNlQ2FsbGJhY2sgPSAocmVzb2x2ZSwgcmVqZWN0KSA9PiB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgdGhpcy5fY3JlYXRlQ2FsbGJhY2sodGl0bGUsIG9wdGlvbnMsIHJlc29sdmUpO1xuICAgICAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgICAgcmVqZWN0KGUpO1xuICAgICAgICB9XG4gICAgICB9O1xuICAgIH1cblxuICAgIHJldHVybiBuZXcgUHJvbWlzZShwcm9taXNlQ2FsbGJhY2spO1xuICB9XG5cbiAgLyoqXG4gICAqIFJldHVybnMgdGhlIG5vdGlmaWNhdGlvbiBjb3VudFxuICAgKiBAcmV0dXJuIHtJbnRlZ2VyfSBUaGUgbm90aWZpY2F0aW9uIGNvdW50XG4gICAqL1xuICBjb3VudCgpIHtcbiAgICBsZXQgY291bnQgPSAwO1xuICAgIGxldCBrZXk7XG5cbiAgICBmb3IgKGtleSBpbiB0aGlzLl9ub3RpZmljYXRpb25zKVxuICAgICAgaWYgKHRoaXMuX25vdGlmaWNhdGlvbnMuaGFzT3duUHJvcGVydHkoa2V5KSkgY291bnQrKztcblxuICAgIHJldHVybiBjb3VudDtcbiAgfVxuXG4gIC8qKlxuICAgKiBDbG9zZXMgYSBub3RpZmljYXRpb24gd2l0aCB0aGUgZ2l2ZW4gdGFnXG4gICAqIEBwYXJhbSB7U3RyaW5nfSB0YWcgLSBUYWcgb2YgdGhlIG5vdGlmaWNhdGlvbiB0byBjbG9zZVxuICAgKiBAcmV0dXJuIHtCb29sZWFufSBib29sZWFuIGRlbm90aW5nIHN1Y2Nlc3NcbiAgICovXG4gIGNsb3NlKHRhZykge1xuICAgIGxldCBrZXksIG5vdGlmaWNhdGlvbjtcblxuICAgIGZvciAoa2V5IGluIHRoaXMuX25vdGlmaWNhdGlvbnMpIHtcbiAgICAgIGlmICh0aGlzLl9ub3RpZmljYXRpb25zLmhhc093blByb3BlcnR5KGtleSkpIHtcbiAgICAgICAgbm90aWZpY2F0aW9uID0gdGhpcy5fbm90aWZpY2F0aW9uc1trZXldO1xuXG4gICAgICAgIC8qIFJ1biBvbmx5IGlmIHRoZSB0YWdzIG1hdGNoICovXG4gICAgICAgIGlmIChub3RpZmljYXRpb24udGFnID09PSB0YWcpIHtcblxuICAgICAgICAgIC8qIENhbGwgdGhlIG5vdGlmaWNhdGlvbidzIGNsb3NlKCkgbWV0aG9kICovXG4gICAgICAgICAgcmV0dXJuIHRoaXMuX2Nsb3NlTm90aWZpY2F0aW9uKGtleSk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9XG4gIH1cblxuICAvKipcbiAgICogQ2xlYXJzIGFsbCBub3RpZmljYXRpb25zXG4gICAqIEByZXR1cm4ge0Jvb2xlYW59IGJvb2xlYW4gZGVub3Rpbmcgd2hldGhlciB0aGUgY2xlYXIgd2FzIHN1Y2Nlc3NmdWwgaW4gY2xvc2luZyBhbGwgbm90aWZpY2F0aW9uc1xuICAgKi9cbiAgY2xlYXIoKSB7XG4gICAgbGV0IGtleSwgc3VjY2VzcyA9IHRydWU7XG5cbiAgICBmb3IgKGtleSBpbiB0aGlzLl9ub3RpZmljYXRpb25zKVxuICAgICAgaWYgKHRoaXMuX25vdGlmaWNhdGlvbnMuaGFzT3duUHJvcGVydHkoa2V5KSlcbiAgICAgICAgc3VjY2VzcyA9IHN1Y2Nlc3MgJiYgdGhpcy5fY2xvc2VOb3RpZmljYXRpb24oa2V5KTtcblxuICAgIHJldHVybiBzdWNjZXNzO1xuICB9XG5cbiAgLyoqXG4gICAqIERlbm90ZXMgd2hldGhlciBQdXNoIGlzIHN1cHBvcnRlZCBpbiB0aGUgY3VycmVudCBicm93c2VyXG4gICAqIEByZXR1cm5zIHtib29sZWFufVxuICAgKi9cbiAgc3VwcG9ydGVkKCkge1xuICAgIGxldCBzdXBwb3J0ZWQgPSBmYWxzZTtcblxuICAgIGZvciAodmFyIGFnZW50IGluIHRoaXMuX2FnZW50cylcbiAgICAgIGlmICh0aGlzLl9hZ2VudHMuaGFzT3duUHJvcGVydHkoYWdlbnQpKVxuICAgICAgICBzdXBwb3J0ZWQgPSBzdXBwb3J0ZWQgfHwgdGhpcy5fYWdlbnRzW2FnZW50XS5pc1N1cHBvcnRlZCgpXG5cbiAgICByZXR1cm4gc3VwcG9ydGVkO1xuICB9XG5cbiAgLyoqXG4gICAqIE1vZGlmaWVzIHNldHRpbmdzIG9yIHJldHVybnMgYWxsIHNldHRpbmdzIGlmIG5vIHBhcmFtZXRlciBwYXNzZWRcbiAgICogQHBhcmFtIHNldHRpbmdzXG4gICAqL1xuICBjb25maWcoc2V0dGluZ3MpIHtcbiAgICBpZiAodHlwZW9mIHNldHRpbmdzICE9PSAndW5kZWZpbmVkJyB8fCBzZXR0aW5ncyAhPT0gbnVsbCAmJiBVdGlsLmlzT2JqZWN0KHNldHRpbmdzKSlcbiAgICAgIFV0aWwub2JqZWN0TWVyZ2UodGhpcy5fY29uZmlndXJhdGlvbiwgc2V0dGluZ3MpO1xuICAgIHJldHVybiB0aGlzLl9jb25maWd1cmF0aW9uO1xuICB9XG5cbiAgLyoqXG4gICAqIENvcGllcyB0aGUgZnVuY3Rpb25zIGZyb20gYSBwbHVnaW4gdG8gdGhlIG1haW4gbGlicmFyeVxuICAgKiBAcGFyYW0gcGx1Z2luXG4gICAqL1xuICBleHRlbmQobWFuaWZlc3QpIHtcbiAgICB2YXIgcGx1Z2luLCBQbHVnaW4sXG4gICAgICBoYXNQcm9wID0ge30uaGFzT3duUHJvcGVydHk7XG5cbiAgICBpZiAoIWhhc1Byb3AuY2FsbChtYW5pZmVzdCwgJ3BsdWdpbicpKSB7XG4gICAgICB0aHJvdyBuZXcgRXJyb3IoTWVzc2FnZXMuZXJyb3JzLmludmFsaWRfcGx1Z2luKTtcbiAgICB9IGVsc2Uge1xuICAgICAgaWYgKGhhc1Byb3AuY2FsbChtYW5pZmVzdCwgJ2NvbmZpZycpICYmIFV0aWwuaXNPYmplY3QobWFuaWZlc3QuY29uZmlnKSAmJiBtYW5pZmVzdC5jb25maWcgIT09IG51bGwpIHtcbiAgICAgICAgdGhpcy5jb25maWcobWFuaWZlc3QuY29uZmlnKTtcbiAgICAgIH1cblxuICAgICAgUGx1Z2luID0gbWFuaWZlc3QucGx1Z2luO1xuICAgICAgcGx1Z2luID0gbmV3IFBsdWdpbih0aGlzLmNvbmZpZygpKVxuXG4gICAgICBmb3IgKHZhciBtZW1iZXIgaW4gcGx1Z2luKSB7XG4gICAgICAgIGlmIChoYXNQcm9wLmNhbGwocGx1Z2luLCBtZW1iZXIpICYmIFV0aWwuaXNGdW5jdGlvbihwbHVnaW5bbWVtYmVyXSkpXG4gICAgICAgICAgdGhpc1ttZW1iZXJdID0gcGx1Z2luW21lbWJlcl07XG4gICAgICB9XG4gICAgfVxuICB9XG59XG4iLCJleHBvcnQgZGVmYXVsdCBjbGFzcyBVdGlsIHtcbiAgc3RhdGljIGlzVW5kZWZpbmVkKG9iaikge1xuICAgIHJldHVybiBvYmogPT09IHVuZGVmaW5lZDtcbiAgfVxuXG4gIHN0YXRpYyBpc1N0cmluZyhvYmopIHtcbiAgICByZXR1cm4gdHlwZW9mIG9iaiA9PT0gJ3N0cmluZyc7XG4gIH1cblxuICBzdGF0aWMgaXNGdW5jdGlvbihvYmopIHtcbiAgICByZXR1cm4gb2JqICYmIHt9LnRvU3RyaW5nLmNhbGwob2JqKSA9PT0gJ1tvYmplY3QgRnVuY3Rpb25dJztcbiAgfVxuXG4gIHN0YXRpYyBpc09iamVjdChvYmopIHtcbiAgICByZXR1cm4gdHlwZW9mIG9iaiA9PT0gJ29iamVjdCdcbiAgfVxuXG4gIHN0YXRpYyBvYmplY3RNZXJnZSh0YXJnZXQsIHNvdXJjZSkge1xuICAgIGZvciAodmFyIGtleSBpbiBzb3VyY2UpIHtcbiAgICAgIGlmICh0YXJnZXQuaGFzT3duUHJvcGVydHkoa2V5KSAmJiB0aGlzLmlzT2JqZWN0KHRhcmdldFtrZXldKSAmJiB0aGlzLmlzT2JqZWN0KHNvdXJjZVtrZXldKSkge1xuICAgICAgICB0aGlzLm9iamVjdE1lcmdlKHRhcmdldFtrZXldLCBzb3VyY2Vba2V5XSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB0YXJnZXRba2V5XSA9IHNvdXJjZVtrZXldXG4gICAgICB9XG4gICAgfVxuICB9XG59XG4iLCJleHBvcnQgZGVmYXVsdCBjbGFzcyBBYnN0cmFjdEFnZW50IHtcbiAgY29uc3RydWN0b3Iod2luKSB7XG4gICAgdGhpcy5fd2luID0gd2luO1xuICB9XG59XG4iLCJpbXBvcnQgQWJzdHJhY3RBZ2VudCBmcm9tICcuL0Fic3RyYWN0QWdlbnQnO1xuaW1wb3J0IFV0aWwgZnJvbSAnLi4vVXRpbCc7XG5cbi8qKlxuICogTm90aWZpY2F0aW9uIGFnZW50IGZvciBtb2Rlcm4gZGVza3RvcCBicm93c2VyczpcbiAqIFNhZmFyaSA2KywgRmlyZWZveCAyMissIENocm9tZSAyMissIE9wZXJhIDI1K1xuICovXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBEZXNrdG9wQWdlbnQgZXh0ZW5kcyBBYnN0cmFjdEFnZW50IHtcblxuICAvKipcbiAgICogUmV0dXJucyBhIGJvb2xlYW4gZGVub3Rpbmcgc3VwcG9ydFxuICAgKiBAcmV0dXJucyB7Qm9vbGVhbn0gYm9vbGVhbiBkZW5vdGluZyB3aGV0aGVyIHdlYmtpdCBub3RpZmljYXRpb25zIGFyZSBzdXBwb3J0ZWRcbiAgICovXG4gIGlzU3VwcG9ydGVkKCkge1xuICAgIHJldHVybiB0aGlzLl93aW4uTm90aWZpY2F0aW9uICE9PSB1bmRlZmluZWQ7XG4gIH1cblxuICAvKipcbiAgICogQ3JlYXRlcyBhIG5ldyBub3RpZmljYXRpb25cbiAgICogQHBhcmFtIHRpdGxlIC0gbm90aWZpY2F0aW9uIHRpdGxlXG4gICAqIEBwYXJhbSBvcHRpb25zIC0gbm90aWZpY2F0aW9uIG9wdGlvbnMgYXJyYXlcbiAgICogQHJldHVybnMge05vdGlmaWNhdGlvbn1cbiAgICovXG4gIGNyZWF0ZSh0aXRsZSwgb3B0aW9ucykge1xuICAgIHJldHVybiBuZXcgdGhpcy5fd2luLk5vdGlmaWNhdGlvbihcbiAgICAgIHRpdGxlLFxuICAgICAge1xuICAgICAgICBpY29uOiAoVXRpbC5pc1N0cmluZyhvcHRpb25zLmljb24pIHx8IFV0aWwuaXNVbmRlZmluZWQob3B0aW9ucy5pY29uKSkgPyBvcHRpb25zLmljb24gOiBvcHRpb25zLmljb24ueDMyLFxuICAgICAgICBib2R5OiBvcHRpb25zLmJvZHksXG4gICAgICAgIHRhZzogb3B0aW9ucy50YWcsXG4gICAgICAgIHJlcXVpcmVJbnRlcmFjdGlvbjogb3B0aW9ucy5yZXF1aXJlSW50ZXJhY3Rpb25cbiAgICAgIH1cbiAgICApO1xuICB9XG5cbiAgLyoqXG4gICAqIENsb3NlIGEgZ2l2ZW4gbm90aWZpY2F0aW9uXG4gICAqIEBwYXJhbSBub3RpZmljYXRpb24gLSBub3RpZmljYXRpb24gdG8gY2xvc2VcbiAgICovXG4gIGNsb3NlKG5vdGlmaWNhdGlvbikge1xuICAgIG5vdGlmaWNhdGlvbi5jbG9zZSgpO1xuICB9XG59XG4iLCJpbXBvcnQgQWJzdHJhY3RBZ2VudCBmcm9tICcuL0Fic3RyYWN0QWdlbnQnO1xuaW1wb3J0IFV0aWwgZnJvbSAnLi4vVXRpbCc7XG5cbi8qKlxuICogTm90aWZpY2F0aW9uIGFnZW50IGZvciBJRTlcbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgTVNBZ2VudCBleHRlbmRzIEFic3RyYWN0QWdlbnQge1xuXG4gIC8qKlxuICAgKiBSZXR1cm5zIGEgYm9vbGVhbiBkZW5vdGluZyBzdXBwb3J0XG4gICAqIEByZXR1cm5zIHtCb29sZWFufSBib29sZWFuIGRlbm90aW5nIHdoZXRoZXIgd2Via2l0IG5vdGlmaWNhdGlvbnMgYXJlIHN1cHBvcnRlZFxuICAgKi9cbiAgaXNTdXBwb3J0ZWQoKSB7XG4gICAgcmV0dXJuICh0aGlzLl93aW4uZXh0ZXJuYWwgIT09IHVuZGVmaW5lZCkgJiYgKHRoaXMuX3dpbi5leHRlcm5hbC5tc0lzU2l0ZU1vZGUgIT09IHVuZGVmaW5lZCk7XG4gIH1cblxuICAvKipcbiAgICogQ3JlYXRlcyBhIG5ldyBub3RpZmljYXRpb25cbiAgICogQHBhcmFtIHRpdGxlIC0gbm90aWZpY2F0aW9uIHRpdGxlXG4gICAqIEBwYXJhbSBvcHRpb25zIC0gbm90aWZpY2F0aW9uIG9wdGlvbnMgYXJyYXlcbiAgICogQHJldHVybnMge05vdGlmaWNhdGlvbn1cbiAgICovXG4gIGNyZWF0ZSh0aXRsZSwgb3B0aW9ucykge1xuICAgIC8qIENsZWFyIGFueSBwcmV2aW91cyBub3RpZmljYXRpb25zICovXG4gICAgdGhpcy5fd2luLmV4dGVybmFsLm1zU2l0ZU1vZGVDbGVhckljb25PdmVybGF5KCk7XG5cbiAgICB0aGlzLl93aW4uZXh0ZXJuYWwubXNTaXRlTW9kZVNldEljb25PdmVybGF5KFxuICAgICAgKChVdGlsLmlzU3RyaW5nKG9wdGlvbnMuaWNvbikgfHwgVXRpbC5pc1VuZGVmaW5lZChvcHRpb25zLmljb24pKVxuICAgICAgICA/IG9wdGlvbnMuaWNvblxuICAgICAgICA6IG9wdGlvbnMuaWNvbi54MTYpLCB0aXRsZVxuICAgICk7XG5cbiAgICB0aGlzLl93aW4uZXh0ZXJuYWwubXNTaXRlTW9kZUFjdGl2YXRlKCk7XG5cbiAgICByZXR1cm4gbnVsbDtcbiAgfVxuXG4gIC8qKlxuICAgKiBDbG9zZSBhIGdpdmVuIG5vdGlmaWNhdGlvblxuICAgKiBAcGFyYW0gbm90aWZpY2F0aW9uIC0gbm90aWZpY2F0aW9uIHRvIGNsb3NlXG4gICAqL1xuICBjbG9zZSgpIHtcbiAgICB0aGlzLl93aW4uZXh0ZXJuYWwubXNTaXRlTW9kZUNsZWFySWNvbk92ZXJsYXkoKVxuICB9XG59XG4iLCJpbXBvcnQgQWJzdHJhY3RBZ2VudCBmcm9tICcuL0Fic3RyYWN0QWdlbnQnO1xuaW1wb3J0IFV0aWwgZnJvbSAnLi4vVXRpbCc7XG5pbXBvcnQgTWVzc2FnZXMgZnJvbSAnLi4vTWVzc2FnZXMnO1xuXG4vKipcbiAqIE5vdGlmaWNhdGlvbiBhZ2VudCBmb3IgbW9kZXJuIGRlc2t0b3AgYnJvd3NlcnM6XG4gKiBTYWZhcmkgNissIEZpcmVmb3ggMjIrLCBDaHJvbWUgMjIrLCBPcGVyYSAyNStcbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgTW9iaWxlQ2hyb21lQWdlbnQgZXh0ZW5kcyBBYnN0cmFjdEFnZW50IHtcblxuICAvKipcbiAgICogUmV0dXJucyBhIGJvb2xlYW4gZGVub3Rpbmcgc3VwcG9ydFxuICAgKiBAcmV0dXJucyB7Qm9vbGVhbn0gYm9vbGVhbiBkZW5vdGluZyB3aGV0aGVyIHdlYmtpdCBub3RpZmljYXRpb25zIGFyZSBzdXBwb3J0ZWRcbiAgICovXG4gIGlzU3VwcG9ydGVkKCkge1xuICAgIHJldHVybiB0aGlzLl93aW4ubmF2aWdhdG9yICE9PSB1bmRlZmluZWQgJiZcbiAgICAgIHRoaXMuX3dpbi5uYXZpZ2F0b3Iuc2VydmljZVdvcmtlciAhPT0gdW5kZWZpbmVkO1xuICB9XG5cbiAgLyoqXG4gICAqIFJldHVybnMgdGhlIGZ1bmN0aW9uIGJvZHkgYXMgYSBzdHJpbmdcbiAgICogQHBhcmFtIGZ1bmNcbiAgICovXG4gIGdldEZ1bmN0aW9uQm9keShmdW5jKSB7XG4gICAgcmV0dXJuIGZ1bmMudG9TdHJpbmcoKS5tYXRjaCgvZnVuY3Rpb25bXntdK3soW1xcc1xcU10qKX0kLylbMV07XG4gIH1cblxuICAvKipcbiAgICogQ3JlYXRlcyBhIG5ldyBub3RpZmljYXRpb25cbiAgICogQHBhcmFtIHRpdGxlIC0gbm90aWZpY2F0aW9uIHRpdGxlXG4gICAqIEBwYXJhbSBvcHRpb25zIC0gbm90aWZpY2F0aW9uIG9wdGlvbnMgYXJyYXlcbiAgICogQHJldHVybnMge05vdGlmaWNhdGlvbn1cbiAgICovXG4gIGNyZWF0ZShpZCwgdGl0bGUsIG9wdGlvbnMsIHNlcnZpY2VXb3JrZXIsIGNhbGxiYWNrKSB7XG4gICAgLyogUmVnaXN0ZXIgU2VydmljZVdvcmtlciAqL1xuICAgIHRoaXMuX3dpbi5uYXZpZ2F0b3Iuc2VydmljZVdvcmtlci5yZWdpc3RlcihzZXJ2aWNlV29ya2VyKTtcblxuICAgIHRoaXMuX3dpbi5uYXZpZ2F0b3Iuc2VydmljZVdvcmtlci5yZWFkeS50aGVuKHJlZ2lzdHJhdGlvbiA9PiB7XG4gICAgICAvKiBMb2NhbCBkYXRhIHRoZSBzZXJ2aWNlIHdvcmtlciB3aWxsIHVzZSAqL1xuICAgICAgbGV0IGxvY2FsRGF0YSA9IHtcbiAgICAgICAgaWQ6IGlkLFxuICAgICAgICBsaW5rOiBvcHRpb25zLmxpbmssXG4gICAgICAgIG9yaWdpbjogZG9jdW1lbnQubG9jYXRpb24uaHJlZixcbiAgICAgICAgb25DbGljazogKFV0aWwuaXNGdW5jdGlvbihvcHRpb25zLm9uQ2xpY2spKSA/IHRoaXMuZ2V0RnVuY3Rpb25Cb2R5KG9wdGlvbnMub25DbGljaykgOiAnJyxcbiAgICAgICAgb25DbG9zZTogKFV0aWwuaXNGdW5jdGlvbihvcHRpb25zLm9uQ2xvc2UpKSA/IHRoaXMuZ2V0RnVuY3Rpb25Cb2R5KG9wdGlvbnMub25DbG9zZSkgOiAnJ1xuICAgICAgfTtcblxuICAgICAgLyogTWVyZ2UgdGhlIGxvY2FsIGRhdGEgd2l0aCB1c2VyLXByb3ZpZGVkIGRhdGEgKi9cbiAgICAgIGlmIChvcHRpb25zLmRhdGEgIT09IHVuZGVmaW5lZCAmJiBvcHRpb25zLmRhdGEgIT09IG51bGwpXG4gICAgICAgIGxvY2FsRGF0YSA9IE9iamVjdC5hc3NpZ24obG9jYWxEYXRhLCBvcHRpb25zLmRhdGEpO1xuXG4gICAgICAvKiBTaG93IHRoZSBub3RpZmljYXRpb24gKi9cbiAgICAgIHJlZ2lzdHJhdGlvbi5zaG93Tm90aWZpY2F0aW9uKFxuICAgICAgICB0aXRsZSxcbiAgICAgICAge1xuICAgICAgICAgIGljb246IG9wdGlvbnMuaWNvbixcbiAgICAgICAgICBib2R5OiBvcHRpb25zLmJvZHksXG4gICAgICAgICAgdmlicmF0ZTogb3B0aW9ucy52aWJyYXRlLFxuICAgICAgICAgIHRhZzogb3B0aW9ucy50YWcsXG4gICAgICAgICAgZGF0YTogbG9jYWxEYXRhLFxuICAgICAgICAgIHJlcXVpcmVJbnRlcmFjdGlvbjogb3B0aW9ucy5yZXF1aXJlSW50ZXJhY3Rpb24sXG4gICAgICAgICAgc2lsZW50OiBvcHRpb25zLnNpbGVudFxuICAgICAgICB9XG4gICAgICApLnRoZW4oKCkgPT4ge1xuXG4gICAgICAgIHJlZ2lzdHJhdGlvbi5nZXROb3RpZmljYXRpb25zKCkudGhlbihub3RpZmljYXRpb25zID0+IHtcbiAgICAgICAgICAvKiBTZW5kIGFuIGVtcHR5IG1lc3NhZ2Ugc28gdGhlIFNlcnZpY2VXb3JrZXIga25vd3Mgd2hvIHRoZSBjbGllbnQgaXMgKi9cbiAgICAgICAgICByZWdpc3RyYXRpb24uYWN0aXZlLnBvc3RNZXNzYWdlKCcnKTtcblxuICAgICAgICAgIC8qIFRyaWdnZXIgY2FsbGJhY2sgKi9cbiAgICAgICAgICBjYWxsYmFjayhub3RpZmljYXRpb25zKTtcbiAgICAgICAgfSk7XG4gICAgICB9KS5jYXRjaChmdW5jdGlvbihlcnJvcikge1xuICAgICAgICB0aHJvdyBuZXcgRXJyb3IoTWVzc2FnZXMuZXJyb3JzLnN3X25vdGlmaWNhdGlvbl9lcnJvciArIGVycm9yLm1lc3NhZ2UpO1xuICAgICAgfSk7XG4gICAgfSkuY2F0Y2goZnVuY3Rpb24oZXJyb3IpIHtcbiAgICAgIHRocm93IG5ldyBFcnJvcihNZXNzYWdlcy5lcnJvcnMuc3dfcmVnaXN0cmF0aW9uX2Vycm9yICsgZXJyb3IubWVzc2FnZSk7XG4gICAgfSk7XG4gIH1cblxuICAvKipcbiAgICogQ2xvc2UgYWxsIG5vdGlmaWNhdGlvblxuICAgKi9cbiAgY2xvc2UoKSB7XG4gICAgLy8gQ2FuJ3QgZG8gdGhpcyB3aXRoIHNlcnZpY2Ugd29ya2Vyc1xuICB9XG59XG4iLCJpbXBvcnQgQWJzdHJhY3RBZ2VudCBmcm9tICcuL0Fic3RyYWN0QWdlbnQnO1xuXG4vKipcbiAqIE5vdGlmaWNhdGlvbiBhZ2VudCBmb3IgbW9kZXJuIGRlc2t0b3AgYnJvd3NlcnM6XG4gKiBTYWZhcmkgNissIEZpcmVmb3ggMjIrLCBDaHJvbWUgMjIrLCBPcGVyYSAyNStcbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgTW9iaWxlRmlyZWZveEFnZW50IGV4dGVuZHMgQWJzdHJhY3RBZ2VudCB7XG5cbiAgLyoqXG4gICAqIFJldHVybnMgYSBib29sZWFuIGRlbm90aW5nIHN1cHBvcnRcbiAgICogQHJldHVybnMge0Jvb2xlYW59IGJvb2xlYW4gZGVub3Rpbmcgd2hldGhlciB3ZWJraXQgbm90aWZpY2F0aW9ucyBhcmUgc3VwcG9ydGVkXG4gICAqL1xuICBpc1N1cHBvcnRlZCgpIHtcbiAgICByZXR1cm4gdGhpcy5fd2luLm5hdmlnYXRvci5tb3pOb3RpZmljYXRpb24gIT09IHVuZGVmaW5lZDtcbiAgfVxuXG4gIC8qKlxuICAgKiBDcmVhdGVzIGEgbmV3IG5vdGlmaWNhdGlvblxuICAgKiBAcGFyYW0gdGl0bGUgLSBub3RpZmljYXRpb24gdGl0bGVcbiAgICogQHBhcmFtIG9wdGlvbnMgLSBub3RpZmljYXRpb24gb3B0aW9ucyBhcnJheVxuICAgKiBAcmV0dXJucyB7Tm90aWZpY2F0aW9ufVxuICAgKi9cbiAgY3JlYXRlKHRpdGxlLCBvcHRpb25zKSB7XG4gICAgbGV0IG5vdGlmaWNhdGlvbiA9IHRoaXMuX3dpbi5uYXZpZ2F0b3IubW96Tm90aWZpY2F0aW9uLmNyZWF0ZU5vdGlmaWNhdGlvbihcbiAgICAgIHRpdGxlLFxuICAgICAgb3B0aW9ucy5ib2R5LFxuICAgICAgb3B0aW9ucy5pY29uXG4gICAgKTtcblxuICAgIG5vdGlmaWNhdGlvbi5zaG93KCk7XG5cbiAgICByZXR1cm4gbm90aWZpY2F0aW9uO1xuICB9XG59XG4iLCJpbXBvcnQgQWJzdHJhY3RBZ2VudCBmcm9tICcuL0Fic3RyYWN0QWdlbnQnO1xuXG4vKipcbiAqIE5vdGlmaWNhdGlvbiBhZ2VudCBmb3Igb2xkIENocm9tZSB2ZXJzaW9ucyAoYW5kIHNvbWUpIEZpcmVmb3hcbiAqL1xuZXhwb3J0IGRlZmF1bHQgY2xhc3MgV2ViS2l0QWdlbnQgZXh0ZW5kcyBBYnN0cmFjdEFnZW50IHtcblxuICAvKipcbiAgICogUmV0dXJucyBhIGJvb2xlYW4gZGVub3Rpbmcgc3VwcG9ydFxuICAgKiBAcmV0dXJucyB7Qm9vbGVhbn0gYm9vbGVhbiBkZW5vdGluZyB3aGV0aGVyIHdlYmtpdCBub3RpZmljYXRpb25zIGFyZSBzdXBwb3J0ZWRcbiAgICovXG4gIGlzU3VwcG9ydGVkKCkge1xuICAgIHJldHVybiB0aGlzLl93aW4ud2Via2l0Tm90aWZpY2F0aW9ucyAhPT0gdW5kZWZpbmVkO1xuICB9XG5cbiAgLyoqXG4gICAqIENyZWF0ZXMgYSBuZXcgbm90aWZpY2F0aW9uXG4gICAqIEBwYXJhbSB0aXRsZSAtIG5vdGlmaWNhdGlvbiB0aXRsZVxuICAgKiBAcGFyYW0gb3B0aW9ucyAtIG5vdGlmaWNhdGlvbiBvcHRpb25zIGFycmF5XG4gICAqIEByZXR1cm5zIHtOb3RpZmljYXRpb259XG4gICAqL1xuICBjcmVhdGUodGl0bGUsIG9wdGlvbnMpIHtcbiAgICBsZXQgbm90aWZpY2F0aW9uID0gdGhpcy5fd2luLndlYmtpdE5vdGlmaWNhdGlvbnMuY3JlYXRlTm90aWZpY2F0aW9uKFxuICAgICAgb3B0aW9ucy5pY29uLFxuICAgICAgdGl0bGUsXG4gICAgICBvcHRpb25zLmJvZHlcbiAgICApO1xuXG4gICAgbm90aWZpY2F0aW9uLnNob3coKTtcblxuICAgIHJldHVybiBub3RpZmljYXRpb247XG4gIH1cblxuICAvKipcbiAgICogQ2xvc2UgYSBnaXZlbiBub3RpZmljYXRpb25cbiAgICogQHBhcmFtIG5vdGlmaWNhdGlvbiAtIG5vdGlmaWNhdGlvbiB0byBjbG9zZVxuICAgKi9cbiAgY2xvc2Uobm90aWZpY2F0aW9uKSB7XG4gICAgbm90aWZpY2F0aW9uLmNhbmNlbCgpO1xuICB9XG59XG4iLCJpbXBvcnQgUHVzaCBmcm9tICcuL2NsYXNzZXMvUHVzaCc7XG5cbm1vZHVsZS5leHBvcnRzID0gbmV3IFB1c2godHlwZW9mIHdpbmRvdyAhPT0gJ3VuZGVmaW5lZCcgPyB3aW5kb3cgOiB0aGlzKTtcbiJdfQ==
