

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
