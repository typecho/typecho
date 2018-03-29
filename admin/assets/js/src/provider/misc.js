

// =====================
// Editor plugins
// =====================
//
+function($){


  provider.initMiscs = function() {

    provider.initJustifiedGallery();

  };


  provider.initFullcalendar = function() {
    if ( ! $.fn.fullCalendar ) {
      return;
    }

  };






  provider.initJustifiedGallery = function() {
    if ( ! $.fn.justifiedGallery ) {
      return;
    }


    provider.provide('justified', function(){
      var options = {
        captions: false,
        cssAnimation: true,
        imagesAnimationDuration: 500
      };

      $.extend(options, app.getDataOptions( $(this) ))
      $(this).justifiedGallery(options);
    });

  };





  // Animate On Scroll
  //
  provider.initAos = function() {

    if ( window['AOS'] === undefined ) {
      return;
    }

    provider.provide('aos', function(){
      AOS.init({
        duration: 800
      });
    });

  };





  provider.initTyped = function() {

    if ( window['Typed'] === undefined ) {
      return;
    }


    provider.provide('typed', function(){
      var strings = $(this).data('type').split('|');
      var options = {
        strings: strings,
        typeSpeed: 50,
        backSpeed: 30,
        loop: true
      };

      $.extend(options, app.getDataOptions( $(this) ))
      var typed = new Typed( $(this)[0], options );

    });

  };




}(jQuery);
