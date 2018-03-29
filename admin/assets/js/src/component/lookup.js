

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
