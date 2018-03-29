

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
