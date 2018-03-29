
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

