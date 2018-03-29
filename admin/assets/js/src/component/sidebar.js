

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
