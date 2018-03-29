

// =====================
// UI plugins
// =====================
//
+function($){



  provider.initUIs = function() {

    provider.initSweetalert2();
    provider.initAnimsition();
    provider.initLity();
    provider.initSortable();
    provider.initShepherd();
    provider.initFilterizr();

  };





  provider.initSweetalert2 = function() {
    if ( window['swal'] === undefined ) {
      return;
    }

    sweetAlert.setDefaults({
      confirmButtonClass: 'btn btn-bold btn-primary',
      cancelButtonClass: 'btn btn-bold btn-secondary',
      buttonsStyling: false
    });

  };



  // Animsition page transition
  //
  provider.initAnimsition = function() {
    if ( ! $.fn.animsition ) {
      return;
    }

    provider.provide('.animsition', function(){

      $(this).animsition({
        linkElement: '[data-provide~="animsition"], .animsition-link',
        loadingInner: '',
      });
    }, true);

  };




  // Lity
  //
  provider.initLity = function() {
    if ( window['lity'] === undefined ) {
      return;
    }

    $(document).on('click', '[data-provide~="lity"]', lity);

  };




  // Dragable / Sortable
  //
  provider.initSortable = function() {
    if ( window['sortable'] === undefined ) {
      return;
    }

    provider.provide('sortable', function(index, element){
      sortable(element, {
        dragImage: null,
        forcePlaceholderSize: true,
        items: $(this).dataAttr('items', null),
        handle: $(this).dataAttr('sortable-handle', null)
      });

      sortable($(this))[0].addEventListener('sortupdate', function(e) {

        if ( !$(this).hasDataAttr('on-change') ) {
          return;
        }

        var callback = $(this).data('on-change');

        app.call(callback, e.detail);
      });
    });

  };




  // Tour
  //
  provider.initShepherd = function() {
    if ( window['Shepherd'] === undefined ) {
      return;
    }

    Shepherd.on('start', function() {
      $('body').prepend('<div class="app-backdrop backdrop-tour"></div>');
    });

    Shepherd.on('inactive', function() {
      $('.app-backdrop.backdrop-tour').remove();
    });

  };




  // Shuffle
  //
  provider.initShuffle = function() {
    if ( window['Shuffle'] === undefined ) {
      return;
    }

    var Shuffle = window.Shuffle;

    Shuffle.options.itemSelector = '[data-shuffle="item"]';
    Shuffle.options.sizer = '[data-shuffle="sizer"]';
    Shuffle.options.delimeter = ',';
    Shuffle.options.speed = 500;


    provider.provide('shuffle', function(){

      var list = $(this).find('[data-shuffle="list"]');
      var filter = $(this).find('[data-shuffle="filter"]');
      var shuffleInstance = new Shuffle(list);



      if ( filter.length ) {

        $(filter).find('[data-shuffle="button"]').each( function() {
          $(this).on('click', function() {
            var btn = $(this);
            var isActive = btn.hasClass('active');
            var btnGroup = btn.data('group');

            $(this).closest('[data-shuffle="filter"]').find('[data-shuffle="button"].active').removeClass('active');

            var filterGroup;
            if (isActive) {
              btn.removeClass('active');
              filterGroup = Shuffle.ALL_ITEMS;
            } else {
              btn.addClass('active');
              filterGroup = btnGroup;
            }

            shuffleInstance.filter(filterGroup);
          });
        });

      }


      $( this ).imagesLoaded( function() {
        shuffleInstance.layout()
      } );

    });

  };




  // PhotoSwipe
  //
  provider.initPhotoswipe = function() {
    if ( ! $.fn.photoSwipe ) {
      return;
    }

    provider.provide('photoswipe', function(){
      var photoswipe = $(this);
      var selector = $(this).dataAttr('slide-selector', 'img');

      var options = {};
      var cast = {
        escKey: 'bool',
        loop: 'bool',
        pinchToClose: 'bool',
        arrowKeys: 'bool',
        history: 'bool',
        modal: 'bool',
        index: 'num',
        bgOpacity: 'num',
        timeToIdle: 'num',
        spacing: 'num',
      }

      options = $.extend( options, app.getDataOptions( $(this), cast ));

      var events = {
        close: function() {
          if ( photoswipe.hasDataAttr('on-close') ) {
            app.call( photoswipe.data('on-close') );
          }
        }
      };

      $(this).photoSwipe(selector, options, events);
    });

  };



  // Make an element fullscreen
  //
  provider.initFullscreen = function() {
    if ( window['screenfull'] === undefined ) {
      return;
    }

    if ( ! screenfull.enabled ) {
      return;
    }

    var selector = '[data-provide~="fullscreen"]';

    $(selector).each(function(){
      $(this).data('fullscreen-default-html', $(this).html());
    });

    document.addEventListener(screenfull.raw.fullscreenchange, function() {
      if (screenfull.isFullscreen) {
        $(selector).each(function(){
          $(this).addClass('is-fullscreen')
        });
      }
      else {
        $(selector).each(function(){
          $(this).removeClass('is-fullscreen')
        });
      }
    });

    $(document).on('click', selector, function(){
      screenfull.toggle();
    });

  };



  // Swiper carousel/slider
  //
  provider.initSwiper = function() {
    if ( window['Swiper'] === undefined ) {
      return;
    }

    provider.provide('swiper', function(){
      var options = {
        autoplay: 0,
        speed: 1000,
        loop: true,
        breakpoints: {
          // when window width is <= 640px
          480: {
            slidesPerView: 1
          }
        }
      };

      var swiper = $(this);

      if ( swiper.find('.swiper-button-next').length ) {
        options.nextButton = '.swiper-button-next';
      }

      if ( swiper.find('.swiper-button-prev').length ) {
        options.prevButton = '.swiper-button-prev';
      }

      if ( swiper.find('.swiper-pagination').length ) {
        options.pagination = '.swiper-pagination';
        options.paginationClickable = true;

        swiper.addClass('swiper-pagination-outside');
      }

      options = $.extend( options, app.getDataOptions( $(this) ));

      new Swiper ( swiper, options );

    });

  };




}(jQuery);
