
// Check if an element has a specific data attribute
//
jQuery.fn.hasDataAttr = function(name) {
  return $(this)[0].hasAttribute('data-'+ name);
};



// Get data attribute. If element doesn't have the attribute, return default value
//
jQuery.fn.dataAttr = function(name, def) {
  return $(this)[0].getAttribute('data-'+ name) || def;
};



// Return outerHTML (inclusing the element) code
//
jQuery.fn.outerHTML = function() {
  var html = '';
  this.each(function(){
    html += $(this).prop("outerHTML");
  })
  return html;
};


// Return HTML code of all the selected elements
//
jQuery.fn.fullHTML = function() {
  var html = '';
  $(this).each(function(){
    html += $(this).outerHTML();
  });
  return html;
};

// Instance search
//
// $.expr[':'] -> $.expr.pseudos
jQuery.expr[':'].search = function(a, i, m) {
  return $(a).html().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
};


// Scroll to end
//
jQuery.fn.scrollToEnd = function() {
  $(this).scrollTop( $(this).prop("scrollHeight") );
  return this;
};

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



// =====================
// provider
// =====================
//
+function($, window){

  var provider = {};
  provider.callbacks = [];

  var msobservers = [];
  var loaded = [];
  var firstLoad = true;
  var observer;

  var MsObserver = function(selector, callback) {
    this.selector = selector;
    this.callback = callback;
  }



  provider.init = function() {

    $LAB.setGlobalDefaults({
      BasePath: app.dir.vendor,
      AlwaysPreserveOrder: true,
      AllowDuplicates: false,
      //Debug: true
    });

    provider.inject();
    provider.observeDOM();
  };




  provider.observeDOM = function() {
    app.ready(function(){
      observer = new MutationObserver(function(mutations) {
        provider.inject();
        for (var i = 0; i < msobservers.length; i++) {
          $(msobservers[i].selector).each(msobservers[i].callback);
        }

      });

      observer.observe(document.body, {childList: true, subtree: true, attributes: false});
    });
  }



  // All of the plugins should initialize using this function
  //
  provider.provide = function(selector, init_callback, isRawSelector) {

    if ( ! isRawSelector === true ) {
      selector = provider.getSelector(provider.list[selector].selector);
    }

    // Call once per element
    var seen = [];
    var callbackOnce = function() {
      // Do not run script if it's provided from a <script> or has data-init="false"
      if ( $(this).is('script') || $(this).data('init') == false ) {
        return;
      }

      if (seen.indexOf(this) == -1) {
        seen.push(this);
        $(this).each(init_callback);
      }
    }

    $(selector).each(callbackOnce);
    msobservers.push(new MsObserver(selector, callbackOnce));
  };




  provider.inject = function(pluginName) {

    if ( pluginName !== undefined ) {
      var vendor = provider.list[pluginName];


      if ( vendor === undefined ) {
        return;
      }

      // Check if it's already loaded
      if ( loaded.indexOf(pluginName) > -1 ) {
        return;
      }

      // Load css files
      if ( 'css' in vendor ) {
        app.loadStyle(vendor.css, app.dir.vendor);
      }


      // Load js files
      if ( 'js' in vendor ) {
        var js = vendor.js;

        if ( Array.isArray(js) ) {
          for (var i = 0; i < js.length; i++) {
            $LAB.queueScript(js[i]);
          }
        }
        else {
          $LAB.queueScript(js);
        }
      }


      // Queue callbacks
      if ( 'callback' in vendor ) {
        //console.log(vendor.callback);
        $LAB.queueWait(function() {
          app.call('provider.'+ vendor.callback);
        });

      }


      // Add to loaded list
      loaded.push(pluginName);

      $LAB.runQueue();

      return;
    }





    var localCallbacks = [];

    // Fetch dependencies from DOM
    //
    $.each(provider.list, function(name, vendor) {

      // Check if it's already loaded
      if ( loaded.indexOf(name) > -1 ) {
        return;
      }

      // Check if any element exists for the plugin
      if ( ! $( provider.getSelector(vendor.selector) ).length ) {
        return;
      }


      // Load css files
      if ( 'css' in vendor ) {
        app.loadStyle(vendor.css, app.dir.vendor);
      }


      // Load js files
      if ( 'js' in vendor ) {
        var js = vendor.js;

        if ( Array.isArray(js) ) {
          for (var i = 0; i < js.length; i++) {
            $LAB.queueScript(js[i]);
          }
        }
        else {
          $LAB.queueScript(js);
        }
      }


      // Queue callbacks
      if ( 'callback' in vendor ) {
        localCallbacks.push(vendor.callback);
      }


      // Add to loaded list
      loaded.push(name);

    });



    if (firstLoad) {
      provider.injectExtra();

      $LAB.queueWait(function() {
        provider.callbacks = localCallbacks;
        app.isReady();
      });
      firstLoad = false;
    }
    else {
      $LAB.queueWait(function() {
        for (var i =0; i < localCallbacks.length; i++) {
          app.call('provider.'+ localCallbacks[i]);
        }
      });
    }


    $LAB.runQueue();

  }





  provider.injectExtra = function() {

    // Load Mapael required maps
    //
    $('[data-mapael-map]').each(function(){
      var js = 'mapael/maps/'+ $(this).data('mapael-map') +'.min.js';
      $LAB.queueScript(js);
    });

    // Load Bootstrap Select languages
    //
    $('[data-provide="selectpicker"][data-lang]').each(function(){
      var js = 'bootstrap-select/js/i18n/defaults-'+ $(this).data('lang') +'.min.js';
      $LAB.queueScript(js);
    });

  }





  // Inject plugins if they called in app.ready()
  //
  provider.injectCalledVendors = function() {
    var callbacksStr = app.getReadyCallbacksString();
    var localCallbacks = [];

    var searchList = {
      typeahead: ').typeahead('
    }


    $.each(searchList, function(name, keyword){
      if ( callbacksStr.indexOf(keyword) == -1 ) {
        return;
      }

      var vendor = provider.list[name];


      // Check if it's already loaded
      if ( loaded.indexOf(name) > -1 ) {
        return;
      }

      // Load css files
      if ( 'css' in vendor ) {
        app.loadStyle(vendor.css, app.dir.vendor);
      }


      // Load js files
      if ( 'js' in vendor ) {
        var js = vendor.js;

        if ( Array.isArray(js) ) {
          for (var i = 0; i < js.length; i++) {
            $LAB.queueScript(js[i]);
          }
        }
        else {
          $LAB.queueScript(js);
        }
      }


      // Queue callbacks
      if ( 'callback' in vendor ) {
        localCallbacks.push(vendor.callback);
      }


      // Add to loaded list
      loaded.push(name);

    });



    $LAB.queueWait(function() {
      for (var i =0; i < localCallbacks.length; i++) {
        app.call('provider.'+ localCallbacks[i]);
      }
    });

    $LAB.runQueue();

  }




  provider.callCallbacks = function(list) {
    for (var i =0; i < provider.callbacks.length; i++) {
      app.call('provider.'+ provider.callbacks[i]);
    }
    provider.callbacks = [];
  }





  provider.getSelector = function(str) {
    var selector = '[data-provide~="'+ str +'"]';
    if ( str.indexOf('$ ') == 0 ) {
      selector = str.substr(2);
    }
    return selector;
  }



  window.provider = provider;
}(jQuery, window);



// =====================
// provider list
// =====================
//
+function($){


  //
  //
  provider.list = {

    // ======================================================================
    // Chart
    //
    easypie: {
      selector: 'easypie',
      callback: 'initEasyPieChart',
      css:      '',
      js:       'easypiechart/jquery.easypiechart.min.js',
    },


    peity: {
      selector: 'peity',
      callback: 'initPeity',
      css:      '',
      js:       'jquery.peity/jquery.peity.min.js',
    },


    sparkline: {
      selector: 'sparkline',
      callback: 'initSparkline',
      css:      '',
      js:       'sparkline/sparkline.min.js',
    },


    chartjs: {
      selector: 'chartjs',
      callback: 'initChartjs',
      css:      '',
      js:       [
                  'chartjs/Chart.min.js',
                  'moment/moment.min.js',
                ]
    },


    morris: {
      selector: 'morris',
      callback: 'initMorris',
      css:      'morris/morris.css',
      js:       [
                  'raphael/raphael.min.js',
                  'morris/morris.min.js',
                ]
    },






    // ======================================================================
    // Code
    //
    prism: {
      selector: '$ code[class*="language-"]',
      callback: 'initPrism',
      css:      'prism/prism.css',
      js:       [
                  'prism/prism.js',
                  'clipboard/clipboard.min.js'
                ]
    },



    clipboard: {
      selector: '$ [data-clipboard-text]',
      callback: 'initClipboard',
      js:       'clipboard/clipboard.min.js'
    },




    // ======================================================================
    // Editor
    //
    summernote: {
      selector: 'summernote',
      callback: 'initSummernote',
      css:      'summernote/summernote.css',
      js:       'summernote/summernote.min.js',
    },




    quill: {
      selector: 'quill',
      callback: 'initQuill',
      css:      [
                  //'highlight/styles/monokai-sublime.css',
                  'quill/quill.bubble.css',
                  'quill/quill.snow.css',
                ],
      js:       [
                  //'highlight/highlight.pack.js',
                  'quill/quill.min.js',
                ]
    },




    // ======================================================================
    // Emoji
    //
    emoji: {
      selector: 'emoji',
      callback: 'initEmojione',
      css:      '',
      js:       'emojione/emojione.min.js',
    },





    // ======================================================================
    // Form
    //
    selectpicker: {
      selector: 'selectpicker',
      callback: 'initSelectpicker',
      css:      'bootstrap-select/css/bootstrap-select.min.css',
      js:       'bootstrap-select/js/bootstrap-select.min.js',
    },


    datepicker: {
      selector: 'datepicker',
      callback: 'initDatepicker',
      css:      'bootstrap-datepicker/css/bootstrap-datepicker3.min.css',
      js:       'bootstrap-datepicker/js/bootstrap-datepicker.min.js',
    },


    timepicker: {
      selector: 'timepicker',
      //callback: '',
      css:      'bootstrap-timepicker/bootstrap-timepicker.min.css',
      js:       'bootstrap-timepicker/bootstrap-timepicker.min.js',
    },


    colorpicker: {
      selector: 'colorpicker',
      callback: 'initMinicolor',
      css:      'jquery-minicolors/jquery.minicolors.css',
      js:       'jquery-minicolors/jquery.minicolors.min.js',
    },


    clockpicker: {
      selector: 'clockpicker',
      callback: 'initClockpicker',
      css:      'bootstrap-clockpicker/bootstrap-clockpicker.min.css',
      js:       'bootstrap-clockpicker/bootstrap-clockpicker.min.js',
    },


    maxlength: {
      selector: 'maxlength',
      callback: 'initMaxlength',
      css:      '',
      js:       'bootstrap-maxlength/bootstrap-maxlength.min.js',
    },


    pwstrength: {
      selector: 'pwstrength',
      callback: 'initPwStrength',
      css:      '',
      js:       'bootstrap-pwstrength/pwstrength-bootstrap.min.js',
    },


    tagsinput: {
      selector: 'tagsinput',
      callback: 'initTagsinput',
      css:      'bootstrap-tagsinput/bootstrap-tagsinput.css',
      js:       'bootstrap-tagsinput/bootstrap-tagsinput.min.js',
    },


    knob: {
      selector: 'knob',
      callback: 'initKnob',
      css:      '',
      js:       'knob/jquery.knob.min.js',
    },


    slider: {
      selector: 'slider',
      callback: 'initNouislider',
      css:      'nouislider/nouislider.min.css',
      js:       'nouislider/nouislider.min.js',
    },


    switchery: {
      selector: 'switchery',
      callback: 'initSwitchery',
      css:      'switchery/switchery.min.css',
      js:       'switchery/switchery.min.js',
    },


    formatter: {
      selector: '$ [data-format]',
      callback: 'initFormatter',
      css:      '',
      js:       'formatter/jquery.formatter.min.js',
    },


    // New version upon finishing alpha releases of Bootstrap
    validation: {
      selector: 'validation',
      callback: 'initValidation',
      css:      '',
      js:       'bootstrap-validator/validator-bs4.min.js',
    },


    wizard: {
      selector: 'wizard',
      callback: 'initWizard',
      css:      '',
      js:       'bootstrap-wizard/bootstrap-wizard.min.js',
    },


    typeahead: {
      selector: 'typeahead',
      js:       [
                  'typeahead/bloodhound.min.js',
                  'typeahead/typeahead.jquery.min.js'
                ],
    },


    bloodhound: {
      selector: 'bloodhound',
      js:       'typeahead/bloodhound.min.js',
    },




    // ======================================================================
    // Icon
    //
    iconMaterial: {
      selector: '$ .material-icons',
      css:      'material-icons/css/material-icons.css',
    },


    icon7Stroke: {
      selector: '$ [class*="pe-7s-"]',
      css:      [
                  'pe-icon-7-stroke/css/pe-icon-7-stroke.min.css',
                  'pe-icon-7-stroke/css/helper.min.css'
                ]
    },


    iconIon: {
      selector: '$ [class*="ion-"]',
      css:      'ionicons/css/ionicons.min.css',
    },


    iconI8: {
      selector: '$ [data-i8-icon]',
      callback: 'initI8icons',
      css:      '',
      js:       'i8-icon/jquery-i8-icon.min.js',
    },





    // ======================================================================
    // Map
    //
    map: {
      selector: 'map',
      callback: 'initMap',
      css:      '',
      js:       'https://maps.googleapis.com/maps/api/js?key='+ app.defaults.googleApiKey +'&callback=app.map',
    },


    mapael: {
      selector: 'mapael',
      callback: 'initMapael',
      css:      '',
      js:       [
                  'jquery.mousewheel/jquery.mousewheel.min.js',
                  'raphael/raphael.min.js',
                  'mapael/jquery.mapael.min.js'
                ],
    },






    // ======================================================================
    // Table
    //
    table: {
      selector: 'table',
      callback: 'initBootstrapTable',
      css:      'bootstrap-table/bootstrap-table.min.css',
      js:       [
                  'bootstrap-table/bootstrap-table.min.js',
                  'bootstrap-table/extensions/editable/bootstrap-table-editable.min.js',
                  'bootstrap-table/extensions/export/bootstrap-table-export.min.js',
                  'bootstrap-table/extensions/resizable/bootstrap-table-resizable.min.js',
                  'bootstrap-table/extensions/mobile/bootstrap-table-mobile.min.js',
                  'bootstrap-table/extensions/filter-control/bootstrap-table-filter-control.min.js',
                  'bootstrap-table/extensions/multiple-sort/bootstrap-table-multiple-sort.min.js'
                ]
    },



    jsgrid: {
      selector: 'jsgrid',
      callback: 'initJsGrid',
      css:      [
                  'jsgrid/jsgrid.min.css',
                  'jsgrid/jsgrid-theme.min.css'
                ],
      js:       'jsgrid/jsgrid.min.js'
    },



    datatables: {
      selector: 'datatables',
      callback: 'initDatatables',
      css:      'datatables/css/dataTables.bootstrap4.min.css',
      js:       [
                  'datatables/js/jquery.dataTables.min.js',
                  'datatables/js/dataTables.bootstrap4.min.js',
                ]
    },





    // ======================================================================
    // UI
    //
    sweetalert: {
      selector: 'sweetalert',
      callback: 'initSweetalert2',
      css:      'sweetalert2/sweetalert2.min.css',
      js:       'sweetalert2/sweetalert2.min.js',
    },


    lity: {
      selector: 'lity',
      callback: 'initLity',
      css:      'lity/lity.min.css',
      js:       'lity/lity.min.js',
    },


    sortable: {
      selector: 'sortable',
      callback: 'initSortable',
      css:      '',
      js:       'html5sortable/html.sortable.min.js',
    },


    shepherd: {
      selector: 'shepherd',
      callback: 'initShepherd',
      css:      'shepherd/css/shepherd-theme-arrows-plain-buttons.css',
      js:       [
                  'shepherd/js/tether.js',
                  'shepherd/js/shepherd.min.js',
                ],
    },


    shuffle: {
      selector: 'shuffle',
      callback: 'initShuffle',
      css:      '',
      js:       [
                  'imagesloaded/imagesloaded.pkgd.min.js',
                  'shuffle/shuffle.min.js',
                ]
    },


    photoswipe: {
      selector: 'photoswipe',
      callback: 'initPhotoswipe',
      css:      [
                  'photoswipe/photoswipe.min.css',
                  'photoswipe/default-skin/default-skin.min.css'
                ],
      js:       'photoswipe/jquery.photoswipe-global.js',
    },


    swiper: {
      selector: 'swiper',
      callback: 'initSwiper',
      css:      'swiper/css/swiper.min.css',
      js:       'swiper/js/swiper.min.js',
    },


    fullscreen: {
      selector: 'fullscreen',
      callback: 'initFullscreen',
      js:       'screenfull/screenfull.min.js',
    },


    jqueryui: {
      selector: 'jqueryui',
      //callback: 'initFullscreen',
      js:       'jqueryui/jquery-ui.min.js',
    },




    // ======================================================================
    // Upload
    //
    dropify: {
      selector: 'dropify',
      callback: 'initDropify',
      css:      'dropify/css/dropify.min.css',
      js:       'dropify/js/dropify.min.js',
    },


    dropzone: {
      selector: 'dropzone',
      callback: 'initDropzone',
      css:      'dropzone/min/dropzone.min.css',
      js:       'dropzone/min/dropzone.min.js',
    },



    // ======================================================================
    // Misc
    //
    fullcalendar: {
      selector: 'fullcalendar',
      callback: 'initFullcalendar',
      css:      'fullcalendar/fullcalendar.min.css',
      js:       [
                  'moment/moment.min.js',
                  'fullcalendar/fullcalendar.min.js',
                ]
    },



    justified: {
      selector: 'justified-gallery',
      callback: 'initJustifiedGallery',
      css:      'justified-gallery/css/justifiedGallery.min.css',
      js:       'justified-gallery/js/jquery.justifiedGallery.min.js',
    },



    animate: {
      selector: '$ .animated',
      css:      'animate/animate.min.css',
    },



    intercoolerjs: {
      selector: '$ [ic-get-from], [ic-post-to], [ic-put-to], [ic-patch-to], [ic-delete-from], [data-ic-get-from], [data-ic-post-to], [data-ic-put-to], [data-ic-patch-to], [data-ic-delete-from]',
      js:       'intercoolerjs/intercooler.min.js',
    },



    smoothscroll: {
      selector: 'smoothscroll',
      js:       'smoothscroll/smoothscroll.min.js',
    },



    aos: {
      selector: '$ [data-aos]',
      callback: 'initAos',
      css:      'aos/aos.css',
      js:       'aos/aos.js',
    },



    typed: {
      selector: 'typing',
      callback: 'initTyped',
      js:       'typed.js/typed.min.js',
    },





    // ======================================================================
    // Misc
    //


    vuejs: {
      selector: 'vuejs',
      js:       'vuejs/vue.min.js',
    },


    reactjs: {
      selector: 'reactjs',
      js:       [
                  'reactjs/react.min.js',
                  'reactjs/react-dom.min.js',
                ],
    },


  }



}(jQuery);



// =====================
// Chart plugins
// =====================
//
+function($){


  provider.initCharts = function() {

    provider.initPeity();
    provider.initSparkline();
    provider.initEasyPieChart();
    provider.initChartjs();

  };




  // Peity
  //
  provider.initPeity = function() {
    if ( ! $.fn.peity ) {
      return;
    }

    provider.provide('peity', function(){
      var type = $(this).dataAttr('type', '');

      switch(type) {
        case 'pie':
          var options = {
            width: 38,
            height: 38,
            radius: 8,
            fill: app.colors.primary +','+ app.colors.bg,
          }
          options = $.extend(options, app.getDataOptions( $(this) ));

          if ( options.size ) {
            options.width = options.height = options.size;
          }

          options.fill = options.fill.split(',');

          $(this).peity("pie", options);
          break;


        case 'donut':
          var options = {
            width: 38,
            height: 38,
            radius: 8,
            fill: app.colors.primary +','+ app.colors.bg,
          }
          options = $.extend(options, app.getDataOptions( $(this) ));

          if ( options.size ) {
            options.width = options.height = options.size;
          }

          options.fill = options.fill.split(',');

          $(this).peity("donut", options);
          break;


        case 'line':
          var options = {
            height: 38,
            width: 120,
            delimiter: ',',
            min: 0,
            max: null,
            fill: app.colors.bg,
            stroke: app.colors.primary,
            strokeWidth: 1,
          }
          options = $.extend(options, app.getDataOptions( $(this) ));

          $(this).peity("line", options);
          break;


        case 'bar':
          var options = {
            height: 38,
            width: 120,
            delimiter: ',',
            min: 0,
            max: null,
            padding: 0.2,
            fill: app.colors.primary,
          }
          options = $.extend(options, app.getDataOptions( $(this) ));

          options.fill = options.fill.split(',');

          $(this).peity("bar", options);
          break;
      }


    });

  };




  // Easy pie chart
  //
  provider.initEasyPieChart = function() {
    if ( !$.fn.easyPieChart ) {
      return;
    }


    provider.provide('easypie', function(){
      var options = {
        barColor: app.colors.primary,
        trackColor: app.colors.bg,
      };
      options = $.extend(options, app.getDataOptions( $(this) ));

      if ( options.color ) {
        options.barColor = options.color;
        options.trackColor = app.colors.bg;
      }

      $(this).easyPieChart(options);
    });

  };





  // Sparkline
  //
  provider.initSparkline = function() {
    if ( !$.fn.sparkline ) {
      return;
    }


    var defColor = 'rgba(51,202,185,0.5)',
        spotColor = app.colors.primary,
        spotHighlightColor = app.colors.danger,
        negColor = app.colors.danger;

    $.extend($.fn.sparkline.defaults.common, {
      enableTagOptions: true,
      tagOptionsPrefix: 'data-',
      tagValuesAttribute: 'data-values',
      lineColor: defColor,
      fillColor: defColor,
    });


    $.extend($.fn.sparkline.defaults.line, {
      spotColor: spotColor,
      minSpotColor: spotColor,
      maxSpotColor: spotColor,
      highlightSpotColor: spotHighlightColor,
      highlightLineColor: null,
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.bar, {
      barWidth: 7,
      barSpacing: 4,
      barColor: defColor,
      negBarColor: negColor,
      zeroColor: defColor,
      stackedBarColor: [defColor, negColor],
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.tristate, {
      barWidth: 7,
      barSpacing: 4,
      posBarColor: defColor,
      negBarColor: negColor,
      zeroBarColor: '#e3e4e5',
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.discrete, {
      thresholdColor: negColor,
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.pie, {
      sliceColors: [defColor, negColor],
      width: 38,
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.box, {
      boxLineColor: '#e3e4e5',
      boxFillColor: '#f3f5f6',
      whiskerColor: app.colors.primary,
      outlierLineColor: defColor,
      outlierFillColor: defColor,
      medianColor: negColor,
      targetColor: defColor,
    });


    $.extend($.fn.sparkline.defaults.bullet, {
      targetWidth: 2,
      targetColor: negColor,
      performanceColor: defColor,
      rangeColors: ['#f3f5f6', '#ebeced', '#e3e4e5'],
    });



    provider.provide('sparkline', function(){
      var options = {}
      options = $.extend(options, app.getDataOptions( $(this) ));

      $(this).sparkline('html', options);
    });


  };



  // Chart.js
  //
  provider.initChartjs = function() {
    if ( !window['Chart'] != undefined ) {
      return;
    }


    // Globals
    //
    $.extend(Chart.defaults.global, {
      defaultFontColor: app.colors.text,
      defaultFontSize: 13,
      defaultColor: 'rgba(0,0,0,0.05)',
    });


    // Globals
    //
    $.extend(Chart.defaults.scale.gridLines, {
      color: 'rgba(0,0,0,0.05)',
      zeroLineColor: 'rgba(0,0,0,0.15)',
    });



    // Legend labels
    //
    $.extend(Chart.defaults.global.legend.labels, {
      boxWidth: 24,
      padding: 16,
    });


    // Tooltip
    //
    $.extend(Chart.defaults.global.tooltips, {
      backgroundColor: 'rgba(0,0,0,0.7)',
      bodySpacing: 6,
      titleMarginBottom: 8,

      xPadding: 12,
      yPadding: 12,
      caretSize: 8,
      cornerRadius: 2,
    });


    // Arc
    //
    $.extend(Chart.defaults.global.elements.arc, {
      backgroundColor: 'rgba(51,202,185,0.5)',
    });


    // Line
    //
    $.extend(Chart.defaults.global.elements.line, {
      backgroundColor: 'rgba(51,202,185,0.5)',
      borderColor: 'rgba(51,202,185,0.5)',
      borderWidth: 1,
    });


    // Point
    //
    $.extend(Chart.defaults.global.elements.point, {
      backgroundColor: 'rgba(51,202,185,0.5)',
      borderColor: '#fff',
    });


    // Rectangle
    //
    $.extend(Chart.defaults.global.elements.rectangle, {
      backgroundColor: 'rgba(51,202,185,0.5)',
      borderColor: '#fff',
    });


  };



  // Morris
  //
  provider.initMorris = function() {
    if ( !window['Morris'] != undefined ) {
      return;
    }

  };




}(jQuery);



// =====================
// Code plugins
// =====================
//
+function($){


  provider.initCodes = function() {

    provider.initPrism();
    provider.initClipboard();

  };



  provider.initPrism = function() {


    $('pre:not(.no-copy) > code[class*="language-"]').each(function() {
      $(this).before('<button class="btn btn-sm btn-bold btn-secondary clipboard-copy">Copy</button>');
    });

    // Move copy button when the content is scrolling
    $('.clipboard-copy').parent().on('scroll', function(){
      $(this).find('.clipboard-copy').css('transform', 'translate('+ $(this).scrollLeft() +'px, '+ $(this).scrollTop() +'px)');
    });

    if ($('.clipboard-copy').length > 0) {
      var clipboardSnippets = new Clipboard('.clipboard-copy', {
        target: function(trigger) {
          return trigger.nextElementSibling;
        }
      });

      clipboardSnippets.on('success', function(e) {
        e.clearSelection();
        app.toast('Copied.');
      });
    }
  };





  provider.initClipboard = function() {
    new Clipboard('[data-clipboard-text]');

  };




}(jQuery);



// =====================
// Editor plugins
// =====================
//
+function($){


  provider.initEditors = function() {

    provider.initSummernote();

  };





  provider.initSummernote = function() {
    if ( ! $.fn.summernote ) {
      return;
    }


    provider.provide('summernote', function(){
      var options = {
        dialogsInBody: true,
        dialogsFade: true
      };
      options = $.extend(options, app.getDataOptions( $(this) ));

      if ( options.toolbar ) {
        switch( options.toolbar.toLowerCase() ) {
          case 'slim':
            options.toolbar = [
              // [groupName, [list of button]]
              ['style', ['bold', 'underline', 'clear']],
              ['color', ['color']],
              ['para', ['ul', 'ol']],
              ['insert', ['link', 'picture']]
            ];
            break;

          case 'full':
            options.toolbar = [
              // [groupName, [list of button]]
              ['para_style', ['style']],
              ['style', ['bold', 'italic', 'underline', 'clear']],
              ['font', ['strikethrough', 'superscript', 'subscript']],
              ['fontsize', ['fontname', 'fontsize', 'height']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph', 'hr']],
              ['table', ['table']],
              ['insert', ['link', 'picture', 'video']],
              ['do', ['undo', 'redo']],
              ['misc', ['fullscreen', 'codeview', 'help']]
            ];
            break;
        }
      }

      $(this).summernote(options);
    });



    $(document).on('click', '[data-summernote-edit]', function(){
      var target = $(this).data('summernote-edit');
      $(target).summernote({focus: true});
    });


    $(document).on('click', '[data-summernote-save]', function(){
      var target = $(this).data('summernote-save');
      var callback = $(this).data('callback');
      var markup = $(target).summernote('code');
      $(target).summernote('destroy');
      app.call(callback, markup);
    });

  };






  provider.initQuill = function() {
    if ( window['Quill'] === undefined ) {
      return;
    }


    provider.provide('quill', function(){

      var options = {
        theme: 'snow'
      };

      var toolbarFullOptions = [
        [
          { 'font': [] },
          { 'header': [1, 2, 3, 4, 5, 6, false] },
          { 'size': ['small', false, 'large', 'huge'] }
        ],
        ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
        [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
        [{ 'script': 'sub'}, { 'script': 'super' }],
        [{ 'header': 1 }, { 'header': 2 }, 'blockquote', 'code-block'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'indent': '-1'}, { 'indent': '+1' }],
        [{ 'direction': 'rtl' }, { 'align': [] }],        // text direction
        ['link', 'image', 'video'],
        ['clean']                                         // remove formatting button
      ];

      $.extend(options, app.getDataOptions( $(this) ));

      if ( options.toolbar !== undefined ) {
        var toolbar = options.toolbar.toLowerCase();
        if ( toolbar == 'full' ) {

          // TODO:
          // Load highlight js
          /*
          $LAB.script('highlight/highlight.pack.js');
          if ( options.codeStyle === undefined ) {
            app.loadStyle('highlight/styles/monokai-sublime.css', app.dir.vendor);
          }
          else {
            app.loadStyle('highlight/styles/'+ options.codeStyle +'.css', app.dir.vendor);
          }
          */

          options.modules = {
            //syntax: 'true',
            toolbar: toolbarFullOptions
          };
        }
      }

      new Quill( $(this)[0], options);

    });


  };



}(jQuery);



// =====================
// Emoji plugins
// =====================
//
+function($){


  provider.initEmojies = function() {

    provider.initEmojione();

  };


  provider.initEmojione = function() {
    if ( window["emojione"] === undefined ) {
      return;
    }

    emojione.imageType = 'svg';
    emojione.sprites = true;
    emojione.ascii = true;
    emojione.imagePathSVGSprites = app.dir.vendor +'/emojione/emojione.svg';

    provider.provide('emoji', function(){
      var original = $(this).html();
      // use .shortnameToImage if only converting shortnames (for slightly better performance)
      var converted = emojione.toImage(original);
      $(this).html(converted);
    });

  };


}(jQuery);



// =====================
// Form plugins
// =====================
//
+function($){


  provider.initForms = function() {

    provider.initSelectpicker();
    provider.initDatepicker();
    provider.initMinicolor();
    provider.initClockpicker();
    provider.initMaxlength();
    provider.initStrength();
    provider.initTagsinput();
    provider.initKnob();
    provider.initNouislider();
    provider.initSwitchery();
    provider.initFormatter();
    provider.initValidation();
    provider.initWizard();

  };



  // Selectpicker
  //
  provider.initSelectpicker = function() {

    if ( ! $.fn.selectpicker ) {
      return;
    }

    provider.provide('selectpicker', function(){
      $(this).selectpicker({
        iconBase: '',
        tickIcon: 'ti-check',
        style: 'btn-light'
      });
    });

  };




  // Datepicker
  //
  provider.initDatepicker = function() {
    if ( ! $.fn.datepicker ) {
      return;
    }

    $.fn.datepicker.defaults.multidateSeparator = ", ";

    provider.provide('datepicker', function(){
      if ( $(this).prop("tagName") == 'INPUT' ) {
        $(this).datepicker();
      }
      else {
        $(this).datepicker({
          inputs: [$(this).find('input:first'), $(this).find('input:last')]
        });
      }
    });
  };




  // Minicolor
  //
  provider.initMinicolor = function() {
    if ( ! $.fn.minicolors ) {
      return;
    }

    provider.provide('colorpicker', function(){
      var options = {
        change: function(value, opacity) {
          if( !value ) return;
          if( opacity ) value += ', ' + opacity;
        },
        theme: 'bootstrap'
      };


      options = $.extend( options, app.getDataOptions( $(this) ));

      if ( 'rgba' === options.format ) {
        options.format = 'rgb';
        options.opacity = true;
      }

      if ( $(this).attr('data-swatches') ) {
        options.swatches = $(this).attr('data-swatches').split('|');
      }


      $(this).minicolors( options );
    });


  }




  // Clockpicker
  //
  provider.initClockpicker = function() {
    if ( ! $.fn.clockpicker ) {
      return;
    }

    provider.provide('clockpicker', function(){
      $(this).clockpicker({
        donetext: 'Done'
      });
    });

  }




  // Max length control
  //
  provider.initMaxlength = function() {
    if ( ! $.fn.maxlength ) {
      return;
    }

    provider.provide('maxlength', function(){
      var options = {
        warningClass: 'badge badge-warning',
        limitReachedClass: 'badge badge-danger',
        placement: 'bottom-right-inside',
      };

      options = $.extend( options, app.getDataOptions( $(this) ));
      $(this).maxlength(options);
    });

  }




  // Password strength
  //
  provider.initPwStrength = function() {
    if ( ! $.fn.pwstrength ) {
      return;
    }

    provider.provide('pwstrength', function(){
      var options = {
        ui : {
          bootstrap4: true,
          progressBarEmptyPercentage: 0,
          showVerdicts: false
        },
        common : {
          usernameField: $(this).dataAttr('username', '#username')
        }
      }

      $(this).pwstrength(options);
      $(this).add( $(this).next() ).wrapAll('<div class="pwstrength"></div>');

      // Vertical progress
      if ( $(this).is('[data-vertical="true"]') ) {
        var height = $(this).outerHeight() - 10,
            right  = -height / 2 + 7,
            bottom = height / 2 + 4;
        $(this).next('.progress').css({
          width: height,
          right: right,
          bottom: bottom
        });
      }
    });

  }




  // Tags input
  //
  provider.initTagsinput = function() {
    if ( ! $.fn.tagsinput ) {
      return;
    }

    provider.provide('tagsinput', function(){
      $(this).tagsinput();
    });

  }




  // Knob
  //
  provider.initKnob = function() {
    if ( ! $.fn.knob ) {
      return;
    }

    provider.provide('knob', function(){
      var options = {
        thickness: .1,
        width: 120,
        height: 120,
        fgColor: app.colors.primary,
        bgColor: app.colors.bg,
      };

      options = $.extend( options, app.getDataOptions( $(this) ));
      $(this).knob( options );
    });

  }




  // NoUiSlider
  //
  provider.initNouislider = function() {
    if ( window['noUiSlider'] === undefined ) {
      return;
    }

    provider.provide('slider', function(index, element){
      var options = {
        range: {
          'min'     : Number( $(this).dataAttr('min', 0) ),
          'max'     : Number( $(this).dataAttr('max', 100) )
        },
        step        : 1,
        start       : $(this).dataAttr('value', 0),
        connect     : 'lower',
        margin      : 0,
        limit       : 100,
        orientation : 'horizontal',
        direction   : 'ltr',
        tooltips    : false,
        animate     : true,
        behaviour   : 'tap',

        format: {
          to: function ( value ) {
            return value;
          },
          from: function ( value ) {
            return value;
          }
        }
      }

      options = $.extend( options, app.getDataOptions( $(this) ));

      var target      = $(this).dataAttr('target', 'none');

      // If it's range slider
      if ( typeof options.start === 'string' && options.start.indexOf(',') > -1 ) {
        options.start = options.start.split(",");


        if ( !$(this).hasDataAttr('connect') ) {
          options.connect = true;
        }

        if ( !$(this).hasDataAttr('behaviour') ) {
          options.behaviour = 'tap-drag';
        }
      }
      else {
        delete options.limit; // Limit option should be available for linear sliders
      }

      // If it's vertical
      if ( options.orientation == 'vertical' ) {
        if ( !$(this).hasDataAttr('direction') ) {
          options.direction = 'rtl';
        }
      }

      // Target
      if ( target != 'none' ) {
        if ( target == 'next' ) {
          target = $(this).next();
        }
        else if ( target == 'prev' ) {
          target = $(this).prev();
        }
      }


      // Create it
      noUiSlider.create(element, options);

      // Event update
      element.noUiSlider.on('update', function(values, handle) {
        var strVal = values.toString();
        $(target).text(strVal).val(strVal);

        if ( $(element).hasDataAttr('on-update') ) {
          app.call( $(element).data('on-update'), values );
        }

      });

      // Event change
      element.noUiSlider.on('change', function(values, handle) {
        if ( $(element).hasDataAttr('on-change') ) {
          app.call( $(element).data('on-change'), values );
        }

      });
    });

  }




  // Switchery
  //
  provider.initSwitchery = function() {
    if ( window['Switchery'] === undefined ) {
      return;
    }

    provider.provide('switchery', function(){
      var options = {
        color: app.colors.primary,
        speed: '0.5s'
      }

      options = $.extend( options, app.getDataOptions( $(this) ));
      new Switchery(this, options);
    });

  }




  // Mask / Formatter
  //
  provider.initFormatter = function() {
    if ( ! $.fn.formatter ) {
      return;
    }

    provider.provide('formatter', function(){
      var options = {
        pattern: $(this).data('format'),
        persistent: $(this).dataAttr('persistent', true),
      }

      $(this).formatter( options );
    });

  }




  // Validator
  //
  provider.initValidation = function() {
    if ( ! $.fn.validator ) {
      return;
    }

    $.fn.validator.Constructor.FOCUS_OFFSET = 100;

    provider.provide('validation', function(){
      $(this).validator();
    });


    $(document).on('click', '[data-perform="validation"]', function(){
      var target = app.getTarget($(this));

      if ( target == undefined) {
        $(this).parents('[data-provide="validation"]').validator('validate');
      }
      else {
        $(target).parents('[data-provide="validation"]').validator('validate');
      }
    });

  }




  // Wizard
  //
  provider.initWizard = function() {
    if ( ! $.fn.bootstrapWizard ) {
      return;
    }

    provider.provide('wizard', function(){

      var wizard   = $(this);
      var nav_item = $(this).find('.nav-item');
      var tab_pane = $(this).find('.tab-pane');

      wizard.bootstrapWizard({
        tabClass:         'nav-process',
        nextSelector:     '[data-wizard="next"]',
        previousSelector: '[data-wizard="prev"]',
        firstSelector:    '[data-wizard="first"]',
        lastSelector:     '[data-wizard="last"]',
        finishSelector:   '[data-wizard="finish"]',
        backSelector:     '[data-wizard="back"]',

        onTabClick: function(tab, navigation, index) {
          if ( !wizard.is('[data-navigateable="true"]') ) {
            return false;
          }
        },


        onNext: function(tab, navigation, index) {

          var current_index = wizard.bootstrapWizard('currentIndex');
          var curr_tab = tab_pane.eq(current_index);
          var tab = tab_pane.eq(index);

          // Validator
          var validator_selector = '[data-provide="validation"]';
          var validator = curr_tab.find(validator_selector).addBack(validator_selector);
          if ( validator.length ) {
            validator.validator('validate');
            if ( validator.find('.has-error').length ) {
              return false;
            }
          }


          // Callback
          //
          if ( wizard.hasDataAttr('on-next') ) {
            app.call( wizard.data('on-next'), tab, navigation, index );
          }
        },


        onBack: function(tab, navigation, index) {

          // Callback
          //
          if ( wizard.hasDataAttr('on-back') ) {
            app.call( wizard.data('on-back'), tab, navigation, index );
          }
        },


        onPrevious: function(tab, navigation, index) {

          // Callback
          //
          if ( wizard.hasDataAttr('on-previous') ) {
            app.call( wizard.data('on-previous'), tab, navigation, index );
          }
        },


        onTabShow: function(tab, navigation, index) {

          var tab = tab_pane.eq(index);
          var nav = nav_item.eq(index);
          var max = wizard.bootstrapWizard('navigationLength');

          // Finish button
          if ( index == max ) {
            wizard.find('[data-wizard="next"]').addClass('d-none');
            wizard.find('[data-wizard="finish"]').removeClass('d-none');
          }
          else {
            wizard.find('[data-wizard="next"]').removeClass('d-none');
            wizard.find('[data-wizard="finish"]').addClass('d-none');
          }

          // Nav classes
          navigation.children().removeClass('processing');
          navigation.children(':lt('+ index +'):not(.complete)').addClass('complete');
          nav.addClass('processing');

          if ( !wizard.is('[data-stay-complete="true"]') ) {
            navigation.children(':gt('+ index +').complete').removeClass('complete');
          }

          // Ajax load
          if ( tab.hasDataAttr('url') ) {
            tab.load( tab.data('url') );
          }

          // Callback for tab
          if ( tab.hasDataAttr('callback') ) {
            app.call( tab.data('callback'), tab );
          }

          // Callback for wizard
          //
          if ( wizard.hasDataAttr('on-tab-show') ) {
            app.call( wizard.data('on-tab-show'), tab, navigation, index );
          }

        },


        onFinish: function(tab, navigation, index) {

          var curr_tab = tab_pane.eq(index);

          // Validator
          var validator_selector = '[data-provide="validation"]';
          var validator = curr_tab.find(validator_selector).addBack(validator_selector);
          if ( validator.length ) {
            validator.validator('validate');
            if ( validator.find('.has-error').length ) {
              validator.closest('form').one('submit', function(e) {
                e.preventDefault();
              });
              return false;
            }
          }

          // Navigation
          var nav = nav_item.eq(index);
          nav.addClass('complete').removeClass('processing');

          // Callback
          //
          if ( wizard.hasDataAttr('on-finish') ) {
            app.call( wizard.data('on-finish'), tab, navigation, index );
          }

        },


      });

    });

  }







  // Typeahead
  //
  provider.initTypeahead = function() {



  };






}(jQuery);



// =====================
// Icon plugins
// =====================
//
+function($){


  provider.initIcons = function() {

    provider.initI8icons();

  };


  provider.initI8icons = function() {

    provider.provide('iconI8', function(){
      $(document).i8icons(function(icons) {
        icons.defaultIconSetUrl(app.dir.vendor +'i8-icon/i8-color-icons.svg');
      });
    });

  };



}(jQuery);



// =====================
// Map plugins
// =====================
//
+function($){

  provider.initMaps = function() {

  };


  provider.initMap = function() {

  };



  provider.initMapael = function() {

  };


}(jQuery);



// =====================
// Table plugins
// =====================
//
+function($){



  provider.initTables = function() {

    provider.initBootstrapTable();

  };




  provider.initBootstrapTable = function() {
    if ( ! $.fn.bootstrapTable ) {
      return;
    }

    jQuery.fn.bootstrapTable.defaults.classes = 'table';

    provider.provide('table', function(){
      $(this).bootstrapTable();
    });


    $('.fixed-table-body').perfectScrollbar();

  };




  provider.initJsGrid = function() {
    if ( ! $.fn.jsGrid ) {
      return;
    }
  };




  provider.initDatatables = function() {
    if ( ! $.fn.DataTable ) {
      return;
    }

    provider.provide('datatables', function(){
      $(this).DataTable();
    });

  };





}(jQuery);



// =====================
// UI plugins
// =====================
//
+function($){



  provider.initUIs = function() {

    provider.initSweetalert2();
    provider.initAnimsition();
    provider.initLity();
    provider.initSortable();
    provider.initShepherd();
    provider.initFilterizr();

  };





  provider.initSweetalert2 = function() {
    if ( window['swal'] === undefined ) {
      return;
    }

    sweetAlert.setDefaults({
      confirmButtonClass: 'btn btn-bold btn-primary',
      cancelButtonClass: 'btn btn-bold btn-secondary',
      buttonsStyling: false
    });

  };



  // Animsition page transition
  //
  provider.initAnimsition = function() {
    if ( ! $.fn.animsition ) {
      return;
    }

    provider.provide('.animsition', function(){

      $(this).animsition({
        linkElement: '[data-provide~="animsition"], .animsition-link',
        loadingInner: '',
      });
    }, true);

  };




  // Lity
  //
  provider.initLity = function() {
    if ( window['lity'] === undefined ) {
      return;
    }

    $(document).on('click', '[data-provide~="lity"]', lity);

  };




  // Dragable / Sortable
  //
  provider.initSortable = function() {
    if ( window['sortable'] === undefined ) {
      return;
    }

    provider.provide('sortable', function(index, element){
      sortable(element, {
        dragImage: null,
        forcePlaceholderSize: true,
        items: $(this).dataAttr('items', null),
        handle: $(this).dataAttr('sortable-handle', null)
      });

      sortable($(this))[0].addEventListener('sortupdate', function(e) {

        if ( !$(this).hasDataAttr('on-change') ) {
          return;
        }

        var callback = $(this).data('on-change');

        app.call(callback, e.detail);
      });
    });

  };




  // Tour
  //
  provider.initShepherd = function() {
    if ( window['Shepherd'] === undefined ) {
      return;
    }

    Shepherd.on('start', function() {
      $('body').prepend('<div class="app-backdrop backdrop-tour"></div>');
    });

    Shepherd.on('inactive', function() {
      $('.app-backdrop.backdrop-tour').remove();
    });

  };




  // Shuffle
  //
  provider.initShuffle = function() {
    if ( window['Shuffle'] === undefined ) {
      return;
    }

    var Shuffle = window.Shuffle;

    Shuffle.options.itemSelector = '[data-shuffle="item"]';
    Shuffle.options.sizer = '[data-shuffle="sizer"]';
    Shuffle.options.delimeter = ',';
    Shuffle.options.speed = 500;


    provider.provide('shuffle', function(){

      var list = $(this).find('[data-shuffle="list"]');
      var filter = $(this).find('[data-shuffle="filter"]');
      var shuffleInstance = new Shuffle(list);



      if ( filter.length ) {

        $(filter).find('[data-shuffle="button"]').each( function() {
          $(this).on('click', function() {
            var btn = $(this);
            var isActive = btn.hasClass('active');
            var btnGroup = btn.data('group');

            $(this).closest('[data-shuffle="filter"]').find('[data-shuffle="button"].active').removeClass('active');

            var filterGroup;
            if (isActive) {
              btn.removeClass('active');
              filterGroup = Shuffle.ALL_ITEMS;
            } else {
              btn.addClass('active');
              filterGroup = btnGroup;
            }

            shuffleInstance.filter(filterGroup);
          });
        });

      }


      $( this ).imagesLoaded( function() {
        shuffleInstance.layout()
      } );

    });

  };




  // PhotoSwipe
  //
  provider.initPhotoswipe = function() {
    if ( ! $.fn.photoSwipe ) {
      return;
    }

    provider.provide('photoswipe', function(){
      var photoswipe = $(this);
      var selector = $(this).dataAttr('slide-selector', 'img');

      var options = {};
      var cast = {
        escKey: 'bool',
        loop: 'bool',
        pinchToClose: 'bool',
        arrowKeys: 'bool',
        history: 'bool',
        modal: 'bool',
        index: 'num',
        bgOpacity: 'num',
        timeToIdle: 'num',
        spacing: 'num',
      }

      options = $.extend( options, app.getDataOptions( $(this), cast ));

      var events = {
        close: function() {
          if ( photoswipe.hasDataAttr('on-close') ) {
            app.call( photoswipe.data('on-close') );
          }
        }
      };

      $(this).photoSwipe(selector, options, events);
    });

  };



  // Make an element fullscreen
  //
  provider.initFullscreen = function() {
    if ( window['screenfull'] === undefined ) {
      return;
    }

    if ( ! screenfull.enabled ) {
      return;
    }

    var selector = '[data-provide~="fullscreen"]';

    $(selector).each(function(){
      $(this).data('fullscreen-default-html', $(this).html());
    });

    document.addEventListener(screenfull.raw.fullscreenchange, function() {
      if (screenfull.isFullscreen) {
        $(selector).each(function(){
          $(this).addClass('is-fullscreen')
        });
      }
      else {
        $(selector).each(function(){
          $(this).removeClass('is-fullscreen')
        });
      }
    });

    $(document).on('click', selector, function(){
      screenfull.toggle();
    });

  };



  // Swiper carousel/slider
  //
  provider.initSwiper = function() {
    if ( window['Swiper'] === undefined ) {
      return;
    }

    provider.provide('swiper', function(){
      var options = {
        autoplay: 0,
        speed: 1000,
        loop: true,
        breakpoints: {
          // when window width is <= 640px
          480: {
            slidesPerView: 1
          }
        }
      };

      var swiper = $(this);

      if ( swiper.find('.swiper-button-next').length ) {
        options.nextButton = '.swiper-button-next';
      }

      if ( swiper.find('.swiper-button-prev').length ) {
        options.prevButton = '.swiper-button-prev';
      }

      if ( swiper.find('.swiper-pagination').length ) {
        options.pagination = '.swiper-pagination';
        options.paginationClickable = true;

        swiper.addClass('swiper-pagination-outside');
      }

      options = $.extend( options, app.getDataOptions( $(this) ));

      new Swiper ( swiper, options );

    });

  };




}(jQuery);



// =====================
// Upload plugins
// =====================
//
+function($){



  provider.initUploads = function() {

    provider.initDropify();
    provider.initDropzone();

  };


  provider.initDropify = function() {
    if ( ! $.fn.dropify ) {
      return;
    }

    provider.provide('dropify', function(){
      $(this).dropify();
    });

  }



  provider.initDropzone = function() {
    if ( ! $.fn.dropzone ) {
      return;
    }

    Dropzone.autoDiscover = false;

    provider.provide('dropzone', function(){
      var options = {};
      options = $.extend( options, app.getDataOptions( $(this) ));
      $(this).addClass('dropzone');
      $(this).dropzone( options );
    });

  }


}(jQuery);



// =====================
// Editor plugins
// =====================
//
+function($){


  provider.initMiscs = function() {

    provider.initJustifiedGallery();

  };


  provider.initFullcalendar = function() {
    if ( ! $.fn.fullCalendar ) {
      return;
    }

  };






  provider.initJustifiedGallery = function() {
    if ( ! $.fn.justifiedGallery ) {
      return;
    }


    provider.provide('justified', function(){
      var options = {
        captions: false,
        cssAnimation: true,
        imagesAnimationDuration: 500
      };

      $.extend(options, app.getDataOptions( $(this) ))
      $(this).justifiedGallery(options);
    });

  };





  // Animate On Scroll
  //
  provider.initAos = function() {

    if ( window['AOS'] === undefined ) {
      return;
    }

    provider.provide('aos', function(){
      AOS.init({
        duration: 800
      });
    });

  };





  provider.initTyped = function() {

    if ( window['Typed'] === undefined ) {
      return;
    }


    provider.provide('typed', function(){
      var strings = $(this).data('type').split('|');
      var options = {
        strings: strings,
        typeSpeed: 50,
        backSpeed: 30,
        loop: true
      };

      $.extend(options, app.getDataOptions( $(this) ))
      var typed = new Typed( $(this)[0], options );

    });

  };




}(jQuery);


// =====================
// Map
// =====================
//
+function($){


  app.map = function() {

    $('[data-provide~="map"]').each(function() {

      var setting = $.extend({}, app.defaults.googleMap, app.getDataOptions($(this)));

      var map = new google.maps.Map( $(this)[0], {
        center: {
          lat: Number(setting.lat),
          lng: Number(setting.lng)
        },
        zoom: Number(setting.zoom)
      });

      var marker = new google.maps.Marker({
        position: {
          lat: Number(setting.markerLat),
          lng: Number(setting.markerLng)
        },
        map: map,
        animation: google.maps.Animation.DROP,
        icon: setting.markerIcon
      });

      var infowindow = new google.maps.InfoWindow({
        content: $(this).dataAttr('info', '')
      });

      marker.addListener('click', function() {
        infowindow.open(map, marker);
      });

      switch (setting.style) {
        case 'light':
          map.set('styles', [{"featureType":"water","elementType":"geometry","stylers":[{"color":"#e9e9e9"},{"lightness":17}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#f5f5f5"},{"lightness":20}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ffffff"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#ffffff"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#ffffff"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#ffffff"},{"lightness":16}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#f5f5f5"},{"lightness":21}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#dedede"},{"lightness":21}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#ffffff"},{"lightness":16}]},{"elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#333333"},{"lightness":40}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#f2f2f2"},{"lightness":19}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#fefefe"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#fefefe"},{"lightness":17},{"weight":1.2}]}]);
          break;

        case 'dark':
          map.set('styles', [{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":16}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":17}]}])
          break;

        default:
          if ( Array.isArray(setting.style) ) {
            map.set('styles', setting.style);
          }
      }

    });
  }



}(jQuery);




// =====================
// Modaler
// =====================
//
+function($){



  app.modaler = function(options) {

    var setting = $.extend({}, app.defaults.modaler, options);



    var handleCallback = function() {

      // Bootstrap modal events
      //
      if ( setting.onShow ) {
        $('#'+ id).on('show.bs.modal', function(e){
          app.call( setting.onShow, e);
        });
      }

      if ( setting.onShown ) {
        $('#'+ id).on('shown.bs.modal', function(e){
          app.call( setting.onShown, e);
        });
      }

      if ( setting.onHide ) {
        $('#'+ id).on('hide.bs.modal', function(e){
          app.call( setting.onHide, e);
        });
      }

      if ( setting.onHidden ) {
        $('#'+ id).on('hidden.bs.modal', function(e){
          app.call( setting.onHidden, e);
        });
      }


      // Handle confirm callback
      //
      $('#'+ id).find('[data-perform="confirm"]').on('click', function(){

        // Hasn't set
        if ( setting.onConfirm == null ) {
          return;
        }

        // Is a function
        if ( $.isFunction(setting.onConfirm) ) {
          setting.onConfirm($('#'+ id));
          return;
        }

        // Is string value, so call it
        if ( setting.onConfirm.substring ) {
          app.call( setting.onConfirm, $('#'+ id) );
        }

      });


      // Handle cancel callback
      //
      $('#'+ id).find('[data-perform="cancel"]').on('click', function(){

        // Hasn't set
        if ( setting.onCancel == null ) {
          return;
        }

        // Is a function
        if ( $.isFunction(setting.onCancel) ) {
          setting.onCancel($('#'+ id));
          return;
        }

        // Is string value, so call it
        if ( setting.onCancel.substring ) {
          app.call( setting.onCancel, $('#'+ id) );
        }

      });
    }





    if ( setting.modalId ) {
      $('#'+ setting.modalId).modal('show');
      return;
    }


    var id = 'modal-'+ app.guid();



    //----------------------------------
    // We recieve modal markup from url
    //
    if (setting.isModal) {

      $('<div>').load( setting.url, function(){
        $('body').append( $(this).find('.modal').attr('id', id).outerHTML() );

        $('#'+ id).modal('show');


        // Destroy after close
        //
        if ( setting.autoDestroy ) {
          $('#'+ id).on('hidden.bs.modal', function(){
            $('#'+ id).remove();
          });
        }
        else {
          $(setting.this).attr('data-modal-id', id);
        }


        handleCallback();


      });
    }





    ////----------------------------------
    // We should design the modal
    //
    else {

      switch (setting.size) {
        case 'sm':
          setting.size = 'modal-sm';
          break;

        case 'lg':
          setting.size = 'modal-lg';
          break;

        default:
          //setting.size = '';
      }


      if ( setting.type ) {
        setting.type = 'modal-'+ setting.type;
      }


      // Header code
      //
      var html_header = '';
      if ( setting.headerVisible ) {
        html_header +=
          '<div class="modal-header"> \
            <h5 class="modal-title">'+ setting.title +'</h5> \
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button> \
          </div>';
      }


      // Footer code
      //
      var html_footer = '';
      if ( setting.footerVisible ) {
        html_footer += '<div class="modal-footer">';

        if ( setting.cancelVisible ) {
          html_footer += '<button class="'+ setting.cancelClass +'" data-dismiss="modal" data-perform="cancel">'+ setting.cancelText +'</button>';
        }

        if ( setting.confirmVisible ) {
          html_footer += '<button class="'+ setting.confirmClass +'" data-dismiss="modal" data-perform="confirm">'+ setting.confirmText +'</button>';
        }

        html_footer += '</div>';
      }

      // Modal code
      //
      var modal_html =
          '<div class="modal fade '+ setting.type +'" id="'+ id +'" tabindex="-1"'+ ( !setting.backdrop ? ' data-backdrop="false"' : '') +'> \
            <div class="modal-dialog '+ setting.size +'"> \
              <div class="modal-content"> \
                '+ html_header +' \
                <div class="modal-body '+ setting.bodyExtraClass +'"> \
                  '+ setting.spinner +' \
                </div> \
                '+ html_footer +' \
              </div> \
            </div> \
          </div>';


      // Show modal
      $('body').append(modal_html);
      $('#'+ id).modal('show');


      // Destroy after close
      //
      if ( setting.autoDestroy ) {
        $('#'+ id).on('hidden.bs.modal', function(){
          $('#'+ id).remove();
        });
      }
      else {
        $(setting.this).attr('data-modal-id', id);
      }


      // Load data into the modal
      //
      if ( setting.url ) {
        $('#'+ id).find('.modal-body').load(setting.url, function(){
          //$(this).removeClass('p-a-0');
          handleCallback();
        });
      }
      else if ( setting.html ) {
        $('#'+ id).find('.modal-body').html(setting.html);
        handleCallback();
      }
      else if ( setting.target ) {
        $('#'+ id).find('.modal-body').html( $(setting.target).html() );
        handleCallback();
      }




    }




  }


  // Enable data attribute options
  $(document).on('click', '[data-provide~="modaler"]', function(){
    app.modaler( app.getDataOptions($(this)) );
    //app.modaler.apply($(this), options);
  });




}(jQuery);



// =====================
// Toast plugin
// =====================
//
+function($){


  app.toast = function(text, options) {

    var setting = $.extend({}, app.defaults.toast, options);


    // Make sure .the-toast exists
    if ($('.toast').length < 1) {
      $('<div class="toast"><div class="text"></div><div class="action"></div></div>').appendTo('body');
    }
    var $toast = $('.toast');

    // Action HTML
    var action = '';
    if (setting.actionTitle != '') {
      action = '<a class="text-'+ setting.actionColor +'" href="'+ setting.actionUrl +'">'+ setting.actionTitle +'</a>'
    }

    // Close previous toast if it is open
    if ($toast.hasClass('reveal')) {
      $toast
        .finish()
        .queue(function(next){
          $(this).removeClass('reveal');
          next();
        })
        .delay(300);
    }

    // Configure the toast and show it
    $toast
      .delay(1)
      .queue(function(next){
        $(this).find('.text').text(text).next('.action').html(action);
        $(this).addClass('reveal');
        next();
      })
      .delay(setting.duration)
      .queue(function(next){
        $(this).removeClass('reveal');
        next();
      });

  }


  // Enable data attribute options
  $(document).on('click', '[data-provide~="toast"]', function(){
    var text = $(this).data('text');
    app.toast(text, app.getDataOptions($(this)) );
  });



}(jQuery);



// =====================
// Page aside
// =====================
//
+function($, window){

  var aside = {};

  aside.init = function() {

    $('.aside-body').perfectScrollbar();

    // Handle page aside toggler
    $(document).on('click', '.aside-toggler', function() {
      aside.toggle();
    });

  };


  aside.toggle = function() {
    $('body').toggleClass('aside-open');
  }


  aside.open = function() {
    $('body').addClass('aside-open');
  }


  aside.close = function() {
    $('body').removeClass('side-open');
  }


  window.aside = aside;
}(jQuery, window);



// =====================
// Topbar
// =====================
//
+function($, window){

  var topbar = {};

  topbar.init = function() {

    // Scrollable
    //
    $('.topbar .list-group').each(function() {
      if ($(this).height() > 265) {
        $(this).perfectScrollbar();
      }
    });


    // Topbar search
    //
    $(document).on( 'focus', '.topbar-search input', function(){
      $(this).closest('.topbar-search').find('.lookup-placeholder span').css('opacity', '0');
    });

    $(document).on( 'blur', '.topbar-search input', function(){
      $(this).closest('.topbar-search').find('.lookup-placeholder span').css('opacity', '1');
    });

  };


  // Toggle fix/unfix state
  //
  topbar.toggleFix = function() {
    $('.topbar').toggleClass('topbar-unfix');
    app.toggleState('topbar.fixed');
  }


  // Fix to top
  //
  topbar.fix = function() {
    $('.topbar').removeClass('topbar-unfix');
    app.state('topbar.fixed', true);
  }


  // Unfix from top
  //
  topbar.unfix = function() {
    $('.topbar').addClass('topbar-unfix');
    app.state('topbar.fixed', false);
  }



  // Return 'true' if topbar is fixed to top
  //
  topbar.isFixed = function() {
    if ( $('.topbar.topbar-unfix').length ) {
      return false;
    }
    return true;
  }

  window.topbar = topbar;
}(jQuery, window);



// =====================
// Sidebar
// =====================
//
+function($, window){

  var sidebar = {};

  sidebar.init = function() {

    // Scrollable
    //
    $('.sidebar-navigation').perfectScrollbar();



    // Handle sidebar openner
    //
    $(document).on('click', '.sidebar-toggler', function() {
      sidebar.open();
    });



    // Close sidebar when backdrop touches
    //
    $(document).on('click', '.backdrop-sidebar', function(){
      sidebar.close();
    });



    // Slide up/down menu item on click
    //
    $(document).on('click', '.sidebar .menu-link', function(){
      var $submenu = $(this).next('.menu-submenu');
      if ($submenu.length < 1)
        return;

      if ($submenu.is(":visible")) {
        $submenu.slideUp(function(){
          $('.sidebar .menu-item.open').removeClass('open');
        });
        $(this).removeClass('open');
        return;
      }

      $('.sidebar .menu-submenu:visible').slideUp();
      $('.sidebar .menu-link').removeClass('open');
      $submenu.slideToggle(function(){
        $('.sidebar .menu-item.open').removeClass('open');
      });
      $(this).addClass('open');
    });

    // Handle fold toggler
    //
    $(document).on('click', '.sidebar-toggle-fold', function() {
      sidebar.toggleFold();
    });

  };




  sidebar.toggleFold = function() {
    $('body').toggleClass('sidebar-folded');
    app.toggleState('sidebar.folded');
  }

  sidebar.fold = function() {
    $('body').addClass('sidebar-folded');
    app.state('sidebar.folded', true);
  }

  sidebar.unfold = function() {
    $('body').removeClass('sidebar-folded');
    app.state('sidebar.folded', false);
  }




  sidebar.open = function() {
    $('body').addClass('sidebar-open').prepend('<div class="app-backdrop backdrop-sidebar"></div>');
  }

  sidebar.close = function() {
    $('body').removeClass('sidebar-open');
    $('.backdrop-sidebar').remove();
  }


  window.sidebar = sidebar;
}(jQuery, window);



// =====================
// Quickview
// =====================
//
+function($, window){

  var quickview = {};

  quickview.init = function() {


    $('.quickview-body').perfectScrollbar();

    // Update scrollbar on tab change
    //
    $(document).on('shown.bs.tab', '.quickview-header a[data-toggle="tab"]', function (e) {
      $(this).closest('.quickview').find('.quickview-body').perfectScrollbar('update');
    })



    // Quickview closer
    //
    $(document).on('click', '[data-dismiss="quickview"]', function(){
      quickview.close( $(this).closest('.quickview') );
    });



    // Handle quickview openner
    //
    $(document).on('click', '[data-toggle="quickview"]', function(e) {
      e.preventDefault();
      var target = app.getTarget($(this));

      if (target == false) {
        quickview.close( $(this).closest('.quickview') )
      }
      else {
        var url = '';
        if ( $(this).hasDataAttr('url') ) {
          url = $(this).data('url');
        }
        quickview.toggle(target, url);
      }
    });



    // Close quickview when backdrop touches
    //
    $(document).on('click', '.backdrop-quickview', function(){
      var qv = $(this).attr('data-target');
      quickview.close(qv);
    });
    $(document).on('click', '.quickview .close, [data-dismiss="quickview"]', function(){
      var qv = $(this).closest('.quickview');
      quickview.close(qv);
    });

  };



  // Toggle open/close state
  //
  quickview.toggle = function(e, url) {
    if ( $(e).hasClass('reveal') ) {
      quickview.close(e);
    }
    else {
      if ( url !== '' ) {
        $(e).html('<div class="spinner-linear"><div class="line"></div></div>');
        $(e).load(url, function() {
          $('.quickview-body').perfectScrollbar();
        });
      }
      quickview.open(e);
    }
  }



  // Open quickview
  //
  quickview.open = function(e) {
    var quickview = $(e);

    // Load content from URL if required
    if ( quickview.hasDataAttr('url') && 'true' !== quickview.data('url-has-loaded') ) {
      quickview.load( quickview.data('url'), function() {
        $('.quickview-body').perfectScrollbar();
        // Don't load it next time, if don't need to
        if ( quickview.hasDataAttr('always-reload') && 'true' === quickview.data('always-reload') ) {

        } else {
          quickview.data('url-has-loaded', 'true');
        }
      });
    }

    // Open it
    quickview.addClass('reveal').not('.backdrop-remove').after('<div class="app-backdrop backdrop-quickview" data-target="'+ e +'"></div>');
  };



  // Close quickview
  //
  quickview.close = function(e) {
    $(e).removeClass('reveal');
    $('.backdrop-quickview').remove();
  };



  window.quickview = quickview;
}(jQuery, window);



// =====================
// Dock
// =====================
//
+function($, window){

  var dock = {};
  var interval_blink = [],
      interval_shake = [];

  dock.init = function() {

    $('.dock-body').perfectScrollbar({
      wheelPropagation: false
    });



    // Handle dock openner
    //
    $(document).on('click', '[data-toggle="dock"]', function(e) {
      e.preventDefault();

      var target = app.getTarget( $(this) );
      dock.toggle( target, $(this) );
    });



    // Dock closer
    //
    $(document).on('click', '[data-dock="close"], [data-dismiss="dock"]', function(){
      dock.close( $(this).closest('.dock') );
    });


    // Handle minimize
    //
    $(document).on('click', '[data-dock="minimize"], .dock.minimize .dock-header', function(){
      dock.toggleMinimize( $(this).closest('.dock') );
    });


    // Handle maximize
    //
    $(document).on('click', '[data-dock="maximize"]', function(){
      dock.toggleMaximize( $(this).closest('.dock') );
    });


    // TODO:
    // Stop blink/shake when dock get focus
    //
    $(document).on('click', '.dock', function(){
      //var e = $(this).attr('id');
      //dock.stopBlink(e);
      //dock.stopShake(e);
    });



    // Close dock when backdrop touches
    //
    $(document).on('click', '.dock .close', function(){
      var dock = $(this).closest('.dock');
      dock.close(dock);
    });

  };


  // Toggle open/close
  //
  dock.toggle = function(target, toggler) {
    if ( $(target).hasClass('reveal') ) {
      dock.close(target);
    }
    else {
      dock.open(target, toggler);
    }
  };


  // Open dock
  //
  dock.open = function(target, toggler) {
    var dock_el = $(target),
        body_el = dock_el.find('.dock-body');

    dock_el.prependTo( dock_el.closest('.dock-list') ).addClass('reveal');

    // Load data from url
    if ( dock_el.hasDataAttr('url') && 'true' !== dock_el.data('url-has-loaded') ) {
      dock._loader( dock_el );
    }
    else if ( body_el.hasDataAttr('url') && 'true' !== body_el.data('url-has-loaded') ) {
      dock._loader( body_el );
    }

  };



  // Close dock
  //
  dock.close = function(e) {
    dock.unMaximize(e);
    $(e).removeClass('reveal minimize');
  };



  // Toggle minimize state
  //
  dock.toggleMinimize = function(e) {

    if ( $(e).hasClass('minimize') ) {
      $(e).removeClass('minimize');
    }
    else {
      dock.unMaximize(e);
      $(e).addClass('minimize');
    }

  };



  // Toggle maximize/fullscreen state
  dock.toggleMaximize = function(e) {
    if ( $(e).hasClass('maximize') ) {
      dock.unMaximize(e);
    }
    else {
      dock.maximize(e);
    }
  };


  // Make it fullscreen
  //
  dock.maximize = function(e) {
    $(e).removeClass('minimize').addClass('maximize').closest('.dock-list').addClass('maximize');
  };


  // Back to initial size from maximize state
  //
  dock.unMaximize = function(e) {
    $(e).removeClass('maximize').closest('.dock-list').removeClass('maximize');
  };



  // Blinking
  //
  dock.blink = function(e) {
    clearInterval(interval_blink[e]);
    $(e).toggleClass("blink");
    interval_blink[e] = setInterval(function(){
      $(e).toggleClass("blink");
    },1000)
  };

  dock.stopBlink = function(e) {
    clearInterval(interval_blink[e]);
    $(e).removeClass("blink");
  };



  // Shakeing
  //
  dock.shake = function(e) {
    clearInterval(interval_shake[e]);
    $(e).toggleClass("shake");
    interval_shake[e] = setInterval(function(){
      $(e).toggleClass("shake");
    },1500)
  };

  dock.stopShake = function(e) {
    clearInterval(interval_shake[e]);
    $(e).removeClass("shake");
  };


  // Private methods
  //
  dock._loader = function(target) {
    target.load( target.data('url'), function(){

      target.find('.dock-body').perfectScrollbar({
        wheelPropagation: false
      });

      // Callback function
      if ( target.hasDataAttr('on-load') ) {
        window[ target.data('on-load') ].call();
      }

      // Don't load it next time, if don't need to
      if ( target.hasDataAttr('always-reload') && 'true' === target.data('always-reload') ) {

      } else {
        target.data('url-has-loaded', 'true');
      }

    });
  };



  window.dock = dock;
}(jQuery, window);


// =====================
// Topbar menu (Horizontal menu)
// =====================
//
+function($, window){

  var topbar_menu = {};

  topbar_menu.init = function() {

    // Handle sidebar openner
    //
    $(document).on('click', '.topbar-menu-toggler', function() {
      topbar_menu.open();
    });



    // Close sidebar when backdrop touches
    //
    $(document).on('click', '.backdrop-topbar-menu', function(){
      topbar_menu.close();
    });



    // Don't follow in large devices
    //
    var breakon = app.breakpoint.lg;

    if ($('body').hasClass('topbar-toggleable-xs')) {
      breakon = app.breakpoint.xs;
    }
    else if ($('body').hasClass('topbar-toggleable-sm')) {
      breakon = app.breakpoint.sm;
    }
    else if ($('body').hasClass('topbar-toggleable-md')) {
      breakon = app.breakpoint.md;
    }

    if ($(document).width() > breakon) {
      return;
    }



    // Slide up/down menu item on click
    //
    $(document).on('click', '.topbar .menu-link', function(){
      var $submenu = $(this).next('.menu-submenu');
      if ($submenu.length < 1)
        return;

      if ($submenu.is(":visible")) {
        $submenu.slideUp(function(){
          $('.topbar .menu-item.open').removeClass('open');
        });
        $(this).removeClass('open');
        return;
      }

      $('.topbar .menu-submenu:visible').slideUp();
      $('.topbar .menu-link').removeClass('open');
      $submenu.slideDown(function(){
        $('.topbar .menu-item.open').removeClass('open');
      });
      $(this).addClass('open');
    });

  };



  // Open menu
  //
  topbar_menu.open = function() {
    $('body').addClass('topbar-menu-open').find('.topbar').prepend('<div class="app-backdrop backdrop-topbar-menu"></div>');
  }


  // Close menu
  //
  topbar_menu.close = function() {
    $('body').removeClass('topbar-menu-open');
    $('.backdrop-topbar-menu').remove();
  }


  window.topbar_menu = topbar_menu;
}(jQuery, window);



// =====================
// Lookup
// =====================
//
+function($, window){

  var lookup = {};

  lookup.init = function() {

    // Handle lookup openner
    //
    $(document).on('click', '[data-toggle="lookup"]', function(e) {
      e.preventDefault();
      var target = app.getTarget($(this));

      if (target == false) {
        lookup.close( $(this).closest('.lookup-fullscreen') )
      }
      else {
        lookup.toggle(target);
      }
    });

  };



  // Toggle open/close state of fullscreen lookup
  //
  lookup.toggle = function(e) {
    if ( $(e).hasClass('reveal') ) {
      lookup.close(e);
    }
    else {
      lookup.open(e);
    }
  };



  // Close fullscreen lookup
  //
  lookup.close = function(e) {
    $(e).removeClass('reveal');
    $('body').removeClass('no-scroll');
  };



  // Close fullscreen lookup
  //
  lookup.open = function(e) {
    $(e).addClass('reveal');
    $(e).find('.lookup-form input').focus();
    $('body').addClass('no-scroll');
  };


  window.lookup = lookup;
}(jQuery, window);


// =====================
// Cards
// =====================
//
+function($, window){

  var cards = {};

  cards.init = function() {

    // Close
    //
    $(document).on('click', '.card-btn-close', function() {
      $(this).closest('.card').fadeOut(600, function() {
        if ($(this).parent().children().length == 1) {
          $(this).parent().remove();
        }
        else {
          $(this).remove();
        }
      });
    });



    // Slide up/down
    //
    $(document).on('click', '.card-btn-slide', function(){
      $(this).toggleClass('rotate-180').closest('.card').find('.card-content').slideToggle();
    });



    // Maximize
    //
    $(document).on('click', '.card-btn-maximize', function(){
      $(this).closest('.card').toggleClass('card-maximize').removeClass('card-fullscreen');
    });



    // Fullscreen
    //
    $(document).on('click', '.card-btn-fullscreen', function(){
      $(this).closest('.card').toggleClass('card-fullscreen').removeClass('card-maximize');
    });



    // Refresh
    //
    $(document).on('click', '.card-btn-reload', function(e) {
      e.preventDefault();
      var url = $(this).attr('href');
      var $card = $(this).closest('.card');

      if (url == "#") {
        return;
      }

      $card.find('.card-loading').addClass('reveal');
      $card.find('.card-content').load(url, function(){
        $card.find('.card-loading').removeClass('reveal');
      });
    });



    // Carousel
    //
    $('.card-carousel').each(function(){
      var interval = false;

      if ( $(this).hasDataAttr('ride') ) {
        interval = 5000;
      }

      $(this).carousel({
        interval: interval
      });
    });

    $(document).on('click', '.card-btn-next', function(){
      $(this).parents('.card-carousel').carousel('next');
    });
    $(document).on('click', '.card-btn-prev', function(){
      $(this).parents('.card-carousel').carousel('prev');
    });
    $(document).on('click', '.card-carousel .carousel-indicators li', function(){
      $(this).parents('.card-carousel').carousel($(this).data('slide-to'));
      $(this).parent().find('.active').removeClass('active');
      $(this).addClass('active');
    });

  };

  cards.fix = function() {

  }

  window.cards = cards;
}(jQuery, window);





// =====================
// App
// =====================
//
+function($){



  // Plugins that embedded inside code.min.js
  //
  app.initCorePlugins = function() {

    provider.initAnimsition();

    // Enable using transform for Popper
    Popper.Defaults.modifiers.computeStyle.gpuAcceleration = false;

    // Enable tooltip
    //
    $('[data-provide~="tooltip"]').each(function() {
      var color = '';

      if ( $(this).hasDataAttr('tooltip-color') ) {
        color = ' tooltip-'+ $(this).data('tooltip-color');
      }

      $(this).tooltip({
        container: 'body',
        trigger: 'hover',
        template: '<div class="tooltip'+ color +'" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
      });
    });


    // Enable popover
    //
    $('[data-provide~="popover"]').popover({
      container: 'body'
    });



    // Scrollable
    //
    $('.modal-right .modal-body, .modal-left .modal-body').perfectScrollbar();
    $('.scrollable').perfectScrollbar({
      wheelPropagation: false,
      wheelSpeed: .5,
    });


    // Child areas that shouldn't work with Bootstrap's collapse plugin
    //
    $(document).on('click', '.no-collapsing', function(e){
      e.stopPropagation();
    })


  }



  // Plugins and small codes for theadmin
  //
  app.initThePlugins = function() {


    // Disable demonstrative links!
    //
    $(document).on('click', 'a[href="#"]', function(e){
      e.preventDefault();
    });


    // Back to top
    //
    $(document).on('click', '[data-provide~="scrollup"]', function() {
      $('html, body').animate({scrollTop : 0}, 600);
      return false;
    });


    // Fix for .nav-tabs dropdown-menu
    //
    $(document).on('click', '.nav-tabs .dropdown-item', function() {
      $(this).siblings('.dropdown-item.active').removeClass('active');
    });



    // Custom control check
    //
    // Since BS4-beta-3, custom-controls needs id and for attributes.
    // We bypass this requirement.
    //
    $(document).on('click', '.custom-checkbox', function() {
      var input = $(this).children('.custom-control-input').not(':disabled');
      input.prop('checked', ! input.prop('checked')).trigger( "change" );
    });

    $(document).on('click', '.custom-radio', function() {
      var input = $(this).children('.custom-control-input').not(':disabled');
      input.prop('checked', true).trigger( "change" );
    });



    // Upload
    //
    $(document).on('click', '.file-browser', function() {
      var $browser = $(this);
      if ( $browser.hasClass('form-control') ) {
        setTimeout(function(){
          $browser.closest('.file-group').find('[type="file"]').trigger('click');
        },300);
      }
      else {
        var file = $browser.closest('.file-group').find('[type="file"]');
        file.on( 'click', function(e) {
          e.stopPropagation();
        });
        file.trigger('click');
      }
    });

    // Event to change file name after file selection
    $(document).on('change', '.file-group [type="file"]', function(){
      var input = $(this)[0];
      var len = input.files.length;
      var filename = '';

      for (var i = 0; i < len; ++i) {
        filename += input.files.item(i).name + ', ';
      }
      filename = filename.substr(0, filename.length-2);
      $(this).closest('.file-group').find('.file-value').val(filename).text(filename).focus();
    });

    // Update file name for bootstrap custom file upload
    $(document).on('change', '.custom-file-input', function(){
      var filename = $(this).val().split('\\').pop();
      $(this).next('.custom-file-control').attr('data-input-value', filename);
    });
    $('.custom-file-control:not([data-input-value])').attr('data-input-value', 'Choose file...');







    // Combined group
    //
    var form_combined_selector = '.form-type-combine .form-group, .form-type-combine.form-group, .form-type-combine .input-group-input';
    $(document).on('click', form_combined_selector, function(){
      $(this).find('.form-control').focus();
    });
    $(document).on('focusin', form_combined_selector, function(){
      $(this).addClass('focused');
    });
    $(document).on('focusout', form_combined_selector, function(){
      $(this).removeClass('focused');
    });



    // Material input
    //
    $(document).on('focus', '.form-type-material .form-control:not(.bootstrap-select)', function(){
      materialDoFloat($(this));
    });

    $(document).on('focusout', '.form-type-material .form-control:not(.bootstrap-select)', function(){
      if($(this).val() === "") {
        materialNoFloat($(this));
      }
    });


    $(".form-type-material .form-control").each(function(){
      if ( $(this).val().length > 0 ) {
        if ( $(this).is('[data-provide~="selectpicker"]') ) {
          return;
        }
        materialDoFloat($(this));
      }
    });

    // Select picker
    $(document).on('show.bs.select', '.form-type-material [data-provide~="selectpicker"]', function(){
      materialDoFloat($(this));
    });

    $(document).on('hidden.bs.select', '.form-type-material [data-provide~="selectpicker"]', function(){
      if ( $(this).selectpicker('val').length == 0 ) {
        materialNoFloat($(this));
      }
    });

    $(document).on('loaded.bs.select', '.form-type-material [data-provide~="selectpicker"]', function(){
      if ( $(this).selectpicker('val').length > 0 ) {
        materialDoFloat($(this));
      }
    });


    function materialDoFloat(e) {
      if ( e.parent('.input-group-input').length ) {
        e.parent('.input-group-input').addClass('do-float');
      }
      else {
        e.closest('.form-group').addClass("do-float");
      }
    }


    function materialNoFloat(e) {
      if ( e.parent('.input-group-input').length ) {
        e.parent('.input-group-input').removeClass('do-float');
      }
      else {
        e.closest('.form-group').removeClass("do-float");
      }
    }






    // Sticky block
    //
    $(window).on('scroll', function() {

      var window_top = $(window).scrollTop();

      $('[data-provide~="sticker"]').each(function(){
        if ( !$(this).hasDataAttr('original-top') ) {
          $(this).attr('data-original-top', $(this).offset().top);
        }

        var target      = app.getTarget( $(this) ),
            stick_start = $(this).dataAttr('original-top'),
            stick_end   = $(target).offset().top + $(target).height(),
            el_width    = $(this).width(),
            el_top      = 0;


        if ( topbar.isFixed() ) {
          el_top = $('.topbar').height();
        }


        var styles = {
          left: $(this).offset().left,
          width: el_width,
          top: el_top
        }

        if (window_top > stick_start && window_top <= stick_end) {
          if ( !$(this).hasClass('sticker-stick') ) {
            $(this).addClass('sticker-stick').css(styles);
            $(target).css('margin-top', $(this).height());
          }
        }
        else {
          $(this).removeClass('sticker-stick');
          $(target).css('margin-top', 0);
        }
      });

    });



    // Tables
    //

    // Selectall
    $(document).on('change', '[data-provide~="selectall"] thead .custom-checkbox :checkbox', function(){
      var th      = $(this).closest('th'),
          index   = th.closest('tr').children().index(th),
          checked = $(this).prop("checked");
      $(this).closest('table').find('tr td:nth-child('+ (index+1) +') :checkbox').each(function(){
        $(this).prop('checked', checked);
        if ( checked ) {
          $(this).closest('tr').addClass('active');
        }
        else {
          $(this).closest('tr').removeClass('active');
        }
      });
    });


    $(document).on('change', '[data-provide~="selectall"] tbody .custom-checkbox :checkbox', function(){
      if ( $(this).prop("checked") ) {
        $(this).closest('tr').addClass('active');
      }
      else {
        $(this).closest('tr').removeClass('active');
      }
    });


    // Selectable
    $(document).on('click', '.table[data-provide~="selectable"] tbody tr', function(){
      var input = $(this).children('td:nth-child(1)').find('input');
      input.prop('checked', !input.prop("checked"));

      if ( input.prop("checked") ) {
        $(this).addClass('active');
      }
      else {
        $(this).removeClass('active');
      }
    });



    // Media
    //

    // Selectall
    $(document).on('change', '.media-list[data-provide~="selectall"] .media-list-header :checkbox, .media-list[data-provide~="selectall"] .media-list-footer :checkbox', function(){
      var list = $(this).closest('.media-list');
      var checked = $(this).prop("checked");
      $(list).find('.media-list-body .custom-checkbox [type="checkbox"]').each(function(){

        $(this).prop('checked', checked);
        if ( checked ) {
          $(this).closest('.media').addClass('active');
        }
        else {
          $(this).closest('.media').removeClass('active');
        }
      });
    });


    $(document).on('change', '[data-provide~="selectall"] .media .custom-checkbox input', function(){
      if ( $(this).prop("checked") ) {
        $(this).closest('.media').addClass('active');
      }
      else {
        $(this).closest('.media').removeClass('active');
      }
    });

    // TODO:
    // Checkable
    /*
    $(document).on('click', '.media[data-provide~="checkable"], .media-list[data-provide~="checkable"] .media:not(.media-list-header):not(.media-list-footer)', function(){
      var input = $(this).find(':checkbox, :radio');
      input.prop('checked', !input.prop("checked"));

      if ( input.prop("checked") ) {
        $(this).addClass('active');
      }
      else {
        $(this).removeClass('active');
      }
    });
    */

    // Click to select
    $(document).on('click', '.media[data-provide~="selectable"], .media-list[data-provide~="selectable"] .media:not(.media-list-header):not(.media-list-footer)', function(){
      var input = $(this).find('input');
      input.prop('checked', !input.prop("checked"));

      if ( input.prop("checked") ) {
        $(this).addClass('active');
      }
      else {
        $(this).removeClass('active');
      }
    });


    // Search
    $('[data-provide~="media-search"]').on('keyup', function(e) {
      var s       = $(this).val().trim(),
          medias  = $(this).closest('.media-list').find('.media:not(.media-list-header):not(.media-list-footer)');

      if (s === '') {
        medias.show();
      }
      else {
        medias.not(':search(' + s + ')').hide();
        medias.filter(':search(' + s + ')').show();
      }
    });



    // Auto-exapnd textareas
    //
    $(document).on('keydown', '.auto-expand', function(){
      var e = $(this);
      setTimeout(function(){
        e.scrollTop(0).css('height', e.prop('scrollHeight') +'px');
      },0);
    });



    // Pre toggler
    //
    $(document).on('click', '.code-toggler .btn', function(){
      $(this).closest('.code').find('pre').slideToggle();
    });


    // TODO:
    // Media collapsable
    //
    //$(document).on('click', '.media-collapsible [data-toggle="collapse"]', function(e) {
      //e.stopPropagation();
      //$(this).parent('.media-collapsible').children('.collapse').collapse('toggle');
    //});



    // Input range
    //
    $(document).on('change mousemove', '.input-range input', function() {
      $(this).closest('.input-range').find('.value').text($(this).val());
    });






    // Avatar
    //

    // Remove button
    $(document).on('click', '.avatar-pill .close', function() {
      $(this).closest('.avatar').fadeOut(function(){
        $(this).remove();
      });
    });

    // More button
    $(document).on('click', '[data-provide~="more-avatar"]', function(){
      var list = $(this).closest('.avatar-list');

      $(this).fadeOut(function(){
        $(this).remove();

        if ( $(this).hasDataAttr('url') ) {
          $('<div>').load( $(this).data('url'), function(){
            var avatars = $(this).html();
            list.append(avatars);
          });

        }
      });
    });




    // Ripple for flat button
    //
    $(document).on('click', '.btn-flat:not(.no-wave)', function(e){
      var x = e.pageX;
      var y = e.pageY;
      var clickY = y - $(this).offset().top;
      var clickX = x - $(this).offset().left;
      var box = this;

      var setX = parseInt(clickX);
      var setY = parseInt(clickY);
      $(this).find("svg").remove();
      $(this).append('<svg><circle cx="'+setX+'" cy="'+setY+'" r="'+0+'"></circle></svg>');

      var circle = $(box).find("circle");
      circle.animate(
        {
          "r" : $(box).outerWidth()
        },
        {
          duration: 400,
          step: function(val){
            circle.attr("r", val);
          },
          complete: function() {
            circle.fadeOut('fast');
          }
        }
      );
    });




    // Callout
    //
    $(document).on('click', '[data-dismiss="callout"]', function(){
      $(this).closest('.callout').fadeOut(function(){
        $(this).remove();
      });
    });




    // Tabs
    //
    $(document).on('click', '[data-dismiss="tab"]', function(){
      $(this).closest('.nav-item').fadeOut(function(){
        $(this).remove();
      });
    });



    // Rating
    //
    var ratingCheckHandle = function(rating) {
      if ( rating.find('input:checked').length ) {
        rating.attr('data-has-rate', 'true');
      }
      else {
        rating.attr('data-has-rate', 'false');
      }
    }

    $(document).on('click', '.rating-remove', function(){
      $(this).closest('.rating').find('input').prop('checked', false);
      ratingCheckHandle( $(this).closest('.rating') );
    });

    $('.rating').each(function(){
      ratingCheckHandle( $(this) );
    });

    $(document).on('change', '.rating input', function(){
      ratingCheckHandle( $(this).closest('.rating') );
    });




    // Loader
    //
    $(document).on('click', '[data-provide~="loader"]', function(e){
      e.preventDefault();

      var target  = app.getTarget( $(this) );
      var url     = app.getURL( $(this) );

      if ( $(this).hasDataAttr('spinner') ) {
        var spinner = $(this).data('spinner');
        $(target).html(spinner);
      }

      $(target).load(url);
    });






    // Lookup textual
    //
    $(document).on('click', '.lookup-textual .lookup-placeholder', function(){
      $(this).closest('.lookup').find('input').focus();
    });

    $(document).on('focus blur keyup', '.lookup-textual input', function(){
      var placeholder = $(this).closest('.lookup').find('.lookup-placeholder');
      if ( $(this).val() == '' ) {
        placeholder.css('display', 'inline-block');
      }
      else {
        placeholder.css('display', 'none');
      }
    });



    // Fullscreen lookup
    //
    $(document).on('keyup', '.lookup-fullscreen[data-url] .lookup-form input', function(){
      var keyword = $(this).val();
      var lookup = $(this).closest('.lookup-fullscreen');
      var url = lookup.data('url');
      lookup.find('.lookup-results').load(url, {s: keyword});
    });




  }



}(jQuery);


// initialize app
//
+function($) {
  app.init();
  topbar.init();
  sidebar.init();
  topbar_menu.init();
  quickview.init();
  dock.init();
  aside.init();
  lookup.init();

  cards.init();

  app.isReady();

}(jQuery);

