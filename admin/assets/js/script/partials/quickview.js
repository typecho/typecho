
/*
 * Setting tab in the global quickview (#qv-global)
 */

// Topbar background color
$(document).on('change', 'input[name="global-topbar-color"]', function(){
  var val = $('input[name="global-topbar-color"]:checked').val();
  if ( val == 'default' ) {
    $('body > .topbar').removeClass('topbar-inverse').css('background-color', '#fff');
  }
  else {
    $('body > .topbar').addClass('topbar-inverse').css('background-color', '#'+ val);
  }
});

// Sidebar background color
$(document).on('change', 'input[name="global-sidebar-color"]', function(){
  var val = $('input[name="global-sidebar-color"]:checked').val();
  $('.sidebar').removeClass('sidebar-light sidebar-dark sidebar-default');
  $('.sidebar').addClass('sidebar-'+ val);
});

// Sidebar menu color
$(document).on('change', 'input[name="global-sidebar-menu-color"]', function(){
  var val = $('input[name="global-sidebar-menu-color"]:checked').val();
  $(".sidebar").removeClass (function (index, className) {
      return (className.match (/(^|\s)sidebar-color-\S+/g) || []).join(' ');
  }).addClass( 'sidebar-color-'+ val );

});

