'use strict';



// =====================
// App
// =====================
//
+function($, window){
  var app = {
    name:       'TheAdmin',
    version:    '1.1.0',
    corejs:     $('script[src*="core.min.js"]').attr('src'),
  };


  app.dir = {
    home:   app.corejs.replace('assets/js/core.min.js', ''),
    assets: app.corejs.replace('js/core.min.js', ''),
    vendor: app.corejs.replace('js/core.min.js', 'vendor/')
  }


  // Change app.dir values if user assigned another url
  //
  var assets_dir_el = $('[data-assets-url]');
  if ( assets_dir_el.length ) {
    var assets_dir = assets_dir_el.data('assets-url');
    if ( '/' !== assets_dir.slice(-1) ) {
      assets_dir += '/';
    }

    app.dir.assets = assets_dir;
    app.dir.vendor = assets_dir + 'vendor/';
  }



  app.defaults = {

    provide: null,
    googleApiKey: null,
    googleAnalyticsKey: null,
    smoothScroll: false,
    saveState: false,

    // Toast
    //
    toast: {
      duration:    4000,
      actionTitle: '',
      actionUrl:   '',
      actionColor: 'warning',
    },




    // Modaler
    //
    modaler: {
      url: '',
      isModal: false,
      html: '',
      target: '',
      type: '',
      size: '',
      title: '',
      backdrop: true,
      headerVisible: true,
      footerVisible: true,
      confirmVisible: true,
      confirmText: 'Ok',
      confirmClass: 'btn btn-w-sm btn-flat btn-primary',
      cancelVisible: false,
      cancelText: 'Cancel',
      cancelClass: 'btn btn-w-sm btn-flat btn-secondary',
      bodyExtraClass: '',
      spinner: '<div class="h-200 center-vh"><svg class="spinner-circle-material-svg" viewBox="0 0 50 50"><circle class="circle" cx="25" cy="25" r="20"></svg></div>',

      autoDestroy: true,

      // Events
      onShow: null,
      onShown: null,
      onHide: null,
      onHidden: null,
      onConfirm: null,
      onCancel: null,

      // Private options
      modalId: null,
    },




    // Google map
    //
    googleMap: {
      lat: '',
      lng: '',
      zoom: 13,
      markerLat: '',
      markerLng: '',
      markerIcon: '',
      style: ''
    }



  };




  // Breakpoint values
  //
  app.breakpoint = {
    xs: 576,
    sm: 768,
    md: 992,
    lg: 1200
  };




  // Application colors
  //
  app.colors = {
    primary:       "#33cabb",
    secondary:     "#e4eaec",
    success:       "#46be8a",
    info:          "#48b0f7",
    warning:       "#f2a654",
    danger:        "#f96868",
    bg:            "#f3f5f6",
    text:          "#616a78",
    textSecondary: "#929daf",
  }

  // Fonts
  //
  app.font = {
    body:  'Roboto, sans-serif',
    title: 'Roboto, sans-serif',
  }

  // Local variables
  //
  var readyCallbacks = [];


  app.getReadyCallbacksString = function() {
    return readyCallbacks.toString();
  }


  app.ready = function(callback) {
    readyCallbacks.push(callback);
  }

  var count = 0;

  app.isReady = function() {
    count++;
    if (count != 2) {
      return;
    }

    $(function(){

      // Init plugins
      provider.callCallbacks();

      // Run ready callbacks
      for (var i = 0; i < readyCallbacks.length; i++) {

        try {
          readyCallbacks[i]();
        }
        catch(e){
          console.error(e);
        }
      }
      readyCallbacks = [];


      // Preloader
      var preloader = $('.preloader');
      if ( preloader.length ) {
        var speed = preloader.dataAttr('hide-spped', 600);
        preloader.fadeOut(speed);
      }

    });
  };



  app.provide = function(vendors) {
    if ( Array.isArray(vendors) ) {
      var len = vendors.length;
      for (var i = 0; i < len; i++) {
        provider.inject(vendors[i]);
      }
    }
    else {
      provider.inject(vendors);
    }
  };




  app.init = function() {

    provider.init();

    app.initCorePlugins();
    app.initThePlugins();

  };




  // Call a function
  //
  app.call = function(functionName /*, args */) {
    if ( functionName == '' || functionName == 'provider.undefined' ) {
      console.log('UNDEFINED FUNC');
      return;
    }

    var args = Array.prototype.slice.call(arguments, 1);
    var context = window;
    var namespaces = functionName.split(".");
    var func = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
      context = context[namespaces[i]];
    }

    try {
      return context[func].apply(context, args);
    }
    catch (e) {
      console.error(e);
    }


  };




  // Load a JS file
  //
  app.loadScript = function (url, callback) {
    $.getScript(url, callback);
  };




  // Load a CSS file and insert ot after core.css.min
  //
  app.loadStyle = function(url, base) {
    if ( url == '' ) {
      return;
    }

    if ( base === undefined ) {
      base = '';
    }

    if ( Array.isArray(url) ) {
      for (var i = 0; i < url.length; i++) {
        $('head link:first').after( $('<link href="'+ base + url[i] +'" rel="stylesheet">') );
      }
    }
    else {
      $('head link:first').after( $('<link href="'+ base + url +'" rel="stylesheet">') );
    }
  };




  app.key = function(key, fn) {
    app.unkey(key);
    $(document).on('keydown.'+ app._normalizeKey(key), null, key, fn);
  }


  app.unkey = function(key) {
    $(document).off('keydown.'+ app._normalizeKey(key));
  }


  app._normalizeKey = function(key) {
    return key.replace('+', '_');
  }



  // Get target of an action from element.
  //
  // It can be 'data-target' or 'href' attribute.
  // We support 'next' and 'prev' values to target next or previous element. In this case, we return jQuery element.
  //
  app.getTarget = function(e) {
    var target;
    if ( e.hasDataAttr('target') ) {
      target = e.data('target');
    }
    else {
      target = e.attr('href');
    }

    if ( target == 'next' ) {
      target = $(e).next();
    }
    else if ( target == 'prev' ) {
      target = $(e).prev();
    }

    if ( target == undefined ) {
      return false;
    }

    return target;
  };





  // Get URL of an action from element.
  //
  // It can be 'data-url' or 'href' attribute.
  //
  app.getURL = function(e) {
    var url;
    if ( e.hasDataAttr('url') ) {
      url = e.data('url');
    }
    else {
      url = e.attr('href');
    }

    return url;
  };



  // Config application
  //
  app.config = function(options) {

    // Return config value
    if ( typeof options === 'string' ) {
      return app.defaults[options];
    }


    // Save configs
    $.extend(true, app.defaults, options);


    // Provide required plugins
    //
    if ( app.defaults.provide ) {
      app.provide(app.defaults.provide);
    }

    // Make necessary changes
    //
    if ( app.defaults.smoothscroll ) {
      app.provide('smoothscroll');
    }



    // Google map
    //
    if ( $('[data-provide~="map"]').length && window["google.maps.Map"] === undefined ) {
      $.getScript("https://maps.googleapis.com/maps/api/js?key="+ app.defaults.googleApiKey +"&callback=app.map");
    }


    // Google Analytics
    //
    if ( app.defaults.googleAnalyticsId ) {
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

      ga('create', app.defaults.googleAnalyticsId, 'auto');
      ga('send', 'pageview');
    }


    // Recover saved states
    //
    if ( app.defaults.saveState ) {
      var states = app.state();
      if ( states['sidebar.folded'] ) {
        sidebar.fold();
      }

      if ( states['topbar.fixed'] ) {
        topbar.fix();
      }
    }
  }


  // Register shortcuts
  //
  app.shortcut = function(keys) {
    $.each( keys, function(key, fn) {
      app.key(key, fn);
    })
  }



  // Convert data-attributes options to Javascript object
  //
  app.getDataOptions = function(el, castList) {
    var options = {};

    $.each( $(el).data(), function(key, value){

      key = app.dataToOption(key);

      // Escape data-provide
      if ( key == 'provide' ) {
        return;
      }

      if ( castList != undefined ) {
        var type = castList[key];
        switch (type) {
          case 'bool':
            value = Boolean(value);
            break;

          case 'num':
            value = Number(value);
            break;

          case 'array':
            value = value.split(',');
            break;

          default:

        }
      }

      options[key] = value;
    });

    return options;
  }



  // Save app state
  //
  app.state = function(key, value) {
    if ( localStorage.theadmin === undefined ) {
      localStorage.theadmin = '{}';
    }

    var states = JSON.parse(localStorage.theadmin);
    if (arguments.length == 0) {
      return states;
    }
    else if (arguments.length == 1) {
      return states[key];
    }
    else if (arguments.length == 2 && app.defaults.saveState) {
      states[key] = value;
      localStorage.theadmin = JSON.stringify(states);
    }
  }

  app.toggleState = function(key) {
    if ( app.defaults.saveState ) {
      var states = app.state();
      states[key] = !states[key];
      localStorage.theadmin = JSON.stringify(states);
    }
  }

  app.state.remove = function(key) {
    localStorage.removeItem(key);
  }

  app.state.clear = function() {
    localStorage.clear();
  }


  // Generate an almost unique ID
  //
  app.guid = function(len) {
    if ( len == undefined) {
      len = 5;
    }
    return Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, len);
  }



  // Convert fooBarBaz to foo-bar-baz
  //
  app.optionToData = function(name) {
    return name.replace(/([A-Z])/g, "-$1").toLowerCase();
  }


  // Convert foo-bar-baz to fooBarBaz
  //
  app.dataToOption = function(name) {
    return name.replace(/-([a-z])/g, function(x){return x[1].toUpperCase();});
  }


  // Escape HTML strings
  //
  app.htmlEscape = function(html) {
    var escapeMap = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#x27;',
      '`': '&#x60;'
    };
    var source = '(?:' + Object.keys(escapeMap).join('|') + ')',
        testRegexp = new RegExp(source),
        replaceRegexp = new RegExp(source, 'g'),
        string = html == null ? '' : '' + html;
    return testRegexp.test(string) ? string.replace(replaceRegexp, function (match) {
      return escapeMap[match];
    }) : string;
  }



  window.app = app;
}(jQuery, window);
