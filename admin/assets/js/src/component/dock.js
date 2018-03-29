

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
