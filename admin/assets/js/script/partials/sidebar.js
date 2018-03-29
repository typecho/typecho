
/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
|
| Handle some behaviors in sidebar demo page
|
*/

// Reset button
$(document).on('click', '#sidebar-reset-btn', function(){
  $('.sidebar').attr('class', 'sidebar');
  $('.sidebar-header').removeClass('sidebar-header-inverse')
  $('.sidebar .menu').attr('class', 'menu');
  $('body').removeClass('sidebar-folded');
});

// Header background color
$(document).on('change', 'input[name="sidebar-header-bg-color"]', function(){
  var val = $('input[name="sidebar-header-bg-color"]:checked').val();
  $('.sidebar-header').css('background-color', val);
});

