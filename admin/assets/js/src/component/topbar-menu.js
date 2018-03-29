
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
