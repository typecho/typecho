
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
