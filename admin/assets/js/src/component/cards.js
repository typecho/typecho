
// =====================
// Cards
// =====================
//
+function($, window){

  var cards = {};

  cards.init = function() {

    // Close
    //
    $(document).on('click', '.card-btn-close', function() {
      $(this).closest('.card').fadeOut(600, function() {
        if ($(this).parent().children().length == 1) {
          $(this).parent().remove();
        }
        else {
          $(this).remove();
        }
      });
    });



    // Slide up/down
    //
    $(document).on('click', '.card-btn-slide', function(){
      $(this).toggleClass('rotate-180').closest('.card').find('.card-content').slideToggle();
    });



    // Maximize
    //
    $(document).on('click', '.card-btn-maximize', function(){
      $(this).closest('.card').toggleClass('card-maximize').removeClass('card-fullscreen');
    });



    // Fullscreen
    //
    $(document).on('click', '.card-btn-fullscreen', function(){
      $(this).closest('.card').toggleClass('card-fullscreen').removeClass('card-maximize');
    });



    // Refresh
    //
    $(document).on('click', '.card-btn-reload', function(e) {
      e.preventDefault();
      var url = $(this).attr('href');
      var $card = $(this).closest('.card');

      if (url == "#") {
        return;
      }

      $card.find('.card-loading').addClass('reveal');
      $card.find('.card-content').load(url, function(){
        $card.find('.card-loading').removeClass('reveal');
      });
    });



    // Carousel
    //
    $('.card-carousel').each(function(){
      var interval = false;

      if ( $(this).hasDataAttr('ride') ) {
        interval = 5000;
      }

      $(this).carousel({
        interval: interval
      });
    });

    $(document).on('click', '.card-btn-next', function(){
      $(this).parents('.card-carousel').carousel('next');
    });
    $(document).on('click', '.card-btn-prev', function(){
      $(this).parents('.card-carousel').carousel('prev');
    });
    $(document).on('click', '.card-carousel .carousel-indicators li', function(){
      $(this).parents('.card-carousel').carousel($(this).data('slide-to'));
      $(this).parent().find('.active').removeClass('active');
      $(this).addClass('active');
    });

  };

  cards.fix = function() {

  }

  window.cards = cards;
}(jQuery, window);
