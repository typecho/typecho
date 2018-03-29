



// =====================
// App
// =====================
//
+function($){



  // Plugins that embedded inside code.min.js
  //
  app.initCorePlugins = function() {

    provider.initAnimsition();

    // Enable using transform for Popper
    Popper.Defaults.modifiers.computeStyle.gpuAcceleration = false;

    // Enable tooltip
    //
    $('[data-provide~="tooltip"]').each(function() {
      var color = '';

      if ( $(this).hasDataAttr('tooltip-color') ) {
        color = ' tooltip-'+ $(this).data('tooltip-color');
      }

      $(this).tooltip({
        container: 'body',
        trigger: 'hover',
        template: '<div class="tooltip'+ color +'" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
      });
    });


    // Enable popover
    //
    $('[data-provide~="popover"]').popover({
      container: 'body'
    });



    // Scrollable
    //
    $('.modal-right .modal-body, .modal-left .modal-body').perfectScrollbar();
    $('.scrollable').perfectScrollbar({
      wheelPropagation: false,
      wheelSpeed: .5,
    });


    // Child areas that shouldn't work with Bootstrap's collapse plugin
    //
    $(document).on('click', '.no-collapsing', function(e){
      e.stopPropagation();
    })


  }



  // Plugins and small codes for theadmin
  //
  app.initThePlugins = function() {


    // Disable demonstrative links!
    //
    $(document).on('click', 'a[href="#"]', function(e){
      e.preventDefault();
    });


    // Back to top
    //
    $(document).on('click', '[data-provide~="scrollup"]', function() {
      $('html, body').animate({scrollTop : 0}, 600);
      return false;
    });


    // Fix for .nav-tabs dropdown-menu
    //
    $(document).on('click', '.nav-tabs .dropdown-item', function() {
      $(this).siblings('.dropdown-item.active').removeClass('active');
    });



    // Custom control check
    //
    // Since BS4-beta-3, custom-controls needs id and for attributes.
    // We bypass this requirement.
    //
    $(document).on('click', '.custom-checkbox', function() {
      var input = $(this).children('.custom-control-input').not(':disabled');
      input.prop('checked', ! input.prop('checked')).trigger( "change" );
    });

    $(document).on('click', '.custom-radio', function() {
      var input = $(this).children('.custom-control-input').not(':disabled');
      input.prop('checked', true).trigger( "change" );
    });



    // Upload
    //
    $(document).on('click', '.file-browser', function() {
      var $browser = $(this);
      if ( $browser.hasClass('form-control') ) {
        setTimeout(function(){
          $browser.closest('.file-group').find('[type="file"]').trigger('click');
        },300);
      }
      else {
        var file = $browser.closest('.file-group').find('[type="file"]');
        file.on( 'click', function(e) {
          e.stopPropagation();
        });
        file.trigger('click');
      }
    });

    // Event to change file name after file selection
    $(document).on('change', '.file-group [type="file"]', function(){
      var input = $(this)[0];
      var len = input.files.length;
      var filename = '';

      for (var i = 0; i < len; ++i) {
        filename += input.files.item(i).name + ', ';
      }
      filename = filename.substr(0, filename.length-2);
      $(this).closest('.file-group').find('.file-value').val(filename).text(filename).focus();
    });

    // Update file name for bootstrap custom file upload
    $(document).on('change', '.custom-file-input', function(){
      var filename = $(this).val().split('\\').pop();
      $(this).next('.custom-file-control').attr('data-input-value', filename);
    });
    $('.custom-file-control:not([data-input-value])').attr('data-input-value', 'Choose file...');







    // Combined group
    //
    var form_combined_selector = '.form-type-combine .form-group, .form-type-combine.form-group, .form-type-combine .input-group-input';
    $(document).on('click', form_combined_selector, function(){
      $(this).find('.form-control').focus();
    });
    $(document).on('focusin', form_combined_selector, function(){
      $(this).addClass('focused');
    });
    $(document).on('focusout', form_combined_selector, function(){
      $(this).removeClass('focused');
    });



    // Material input
    //
    $(document).on('focus', '.form-type-material .form-control:not(.bootstrap-select)', function(){
      materialDoFloat($(this));
    });

    $(document).on('focusout', '.form-type-material .form-control:not(.bootstrap-select)', function(){
      if($(this).val() === "") {
        materialNoFloat($(this));
      }
    });


    $(".form-type-material .form-control").each(function(){
      if ( $(this).val().length > 0 ) {
        if ( $(this).is('[data-provide~="selectpicker"]') ) {
          return;
        }
        materialDoFloat($(this));
      }
    });

    // Select picker
    $(document).on('show.bs.select', '.form-type-material [data-provide~="selectpicker"]', function(){
      materialDoFloat($(this));
    });

    $(document).on('hidden.bs.select', '.form-type-material [data-provide~="selectpicker"]', function(){
      if ( $(this).selectpicker('val').length == 0 ) {
        materialNoFloat($(this));
      }
    });

    $(document).on('loaded.bs.select', '.form-type-material [data-provide~="selectpicker"]', function(){
      if ( $(this).selectpicker('val').length > 0 ) {
        materialDoFloat($(this));
      }
    });


    function materialDoFloat(e) {
      if ( e.parent('.input-group-input').length ) {
        e.parent('.input-group-input').addClass('do-float');
      }
      else {
        e.closest('.form-group').addClass("do-float");
      }
    }


    function materialNoFloat(e) {
      if ( e.parent('.input-group-input').length ) {
        e.parent('.input-group-input').removeClass('do-float');
      }
      else {
        e.closest('.form-group').removeClass("do-float");
      }
    }






    // Sticky block
    //
    $(window).on('scroll', function() {

      var window_top = $(window).scrollTop();

      $('[data-provide~="sticker"]').each(function(){
        if ( !$(this).hasDataAttr('original-top') ) {
          $(this).attr('data-original-top', $(this).offset().top);
        }

        var target      = app.getTarget( $(this) ),
            stick_start = $(this).dataAttr('original-top'),
            stick_end   = $(target).offset().top + $(target).height(),
            el_width    = $(this).width(),
            el_top      = 0;


        if ( topbar.isFixed() ) {
          el_top = $('.topbar').height();
        }


        var styles = {
          left: $(this).offset().left,
          width: el_width,
          top: el_top
        }

        if (window_top > stick_start && window_top <= stick_end) {
          if ( !$(this).hasClass('sticker-stick') ) {
            $(this).addClass('sticker-stick').css(styles);
            $(target).css('margin-top', $(this).height());
          }
        }
        else {
          $(this).removeClass('sticker-stick');
          $(target).css('margin-top', 0);
        }
      });

    });



    // Tables
    //

    // Selectall
    $(document).on('change', '[data-provide~="selectall"] thead .custom-checkbox :checkbox', function(){
      var th      = $(this).closest('th'),
          index   = th.closest('tr').children().index(th),
          checked = $(this).prop("checked");
      $(this).closest('table').find('tr td:nth-child('+ (index+1) +') :checkbox').each(function(){
        $(this).prop('checked', checked);
        if ( checked ) {
          $(this).closest('tr').addClass('active');
        }
        else {
          $(this).closest('tr').removeClass('active');
        }
      });
    });


    $(document).on('change', '[data-provide~="selectall"] tbody .custom-checkbox :checkbox', function(){
      if ( $(this).prop("checked") ) {
        $(this).closest('tr').addClass('active');
      }
      else {
        $(this).closest('tr').removeClass('active');
      }
    });


    // Selectable
    $(document).on('click', '.table[data-provide~="selectable"] tbody tr', function(){
      var input = $(this).children('td:nth-child(1)').find('input');
      input.prop('checked', !input.prop("checked"));

      if ( input.prop("checked") ) {
        $(this).addClass('active');
      }
      else {
        $(this).removeClass('active');
      }
    });



    // Media
    //

    // Selectall
    $(document).on('change', '.media-list[data-provide~="selectall"] .media-list-header :checkbox, .media-list[data-provide~="selectall"] .media-list-footer :checkbox', function(){
      var list = $(this).closest('.media-list');
      var checked = $(this).prop("checked");
      $(list).find('.media-list-body .custom-checkbox [type="checkbox"]').each(function(){

        $(this).prop('checked', checked);
        if ( checked ) {
          $(this).closest('.media').addClass('active');
        }
        else {
          $(this).closest('.media').removeClass('active');
        }
      });
    });


    $(document).on('change', '[data-provide~="selectall"] .media .custom-checkbox input', function(){
      if ( $(this).prop("checked") ) {
        $(this).closest('.media').addClass('active');
      }
      else {
        $(this).closest('.media').removeClass('active');
      }
    });

    // TODO:
    // Checkable
    /*
    $(document).on('click', '.media[data-provide~="checkable"], .media-list[data-provide~="checkable"] .media:not(.media-list-header):not(.media-list-footer)', function(){
      var input = $(this).find(':checkbox, :radio');
      input.prop('checked', !input.prop("checked"));

      if ( input.prop("checked") ) {
        $(this).addClass('active');
      }
      else {
        $(this).removeClass('active');
      }
    });
    */

    // Click to select
    $(document).on('click', '.media[data-provide~="selectable"], .media-list[data-provide~="selectable"] .media:not(.media-list-header):not(.media-list-footer)', function(){
      var input = $(this).find('input');
      input.prop('checked', !input.prop("checked"));

      if ( input.prop("checked") ) {
        $(this).addClass('active');
      }
      else {
        $(this).removeClass('active');
      }
    });


    // Search
    $('[data-provide~="media-search"]').on('keyup', function(e) {
      var s       = $(this).val().trim(),
          medias  = $(this).closest('.media-list').find('.media:not(.media-list-header):not(.media-list-footer)');

      if (s === '') {
        medias.show();
      }
      else {
        medias.not(':search(' + s + ')').hide();
        medias.filter(':search(' + s + ')').show();
      }
    });



    // Auto-exapnd textareas
    //
    $(document).on('keydown', '.auto-expand', function(){
      var e = $(this);
      setTimeout(function(){
        e.scrollTop(0).css('height', e.prop('scrollHeight') +'px');
      },0);
    });



    // Pre toggler
    //
    $(document).on('click', '.code-toggler .btn', function(){
      $(this).closest('.code').find('pre').slideToggle();
    });


    // TODO:
    // Media collapsable
    //
    //$(document).on('click', '.media-collapsible [data-toggle="collapse"]', function(e) {
      //e.stopPropagation();
      //$(this).parent('.media-collapsible').children('.collapse').collapse('toggle');
    //});



    // Input range
    //
    $(document).on('change mousemove', '.input-range input', function() {
      $(this).closest('.input-range').find('.value').text($(this).val());
    });






    // Avatar
    //

    // Remove button
    $(document).on('click', '.avatar-pill .close', function() {
      $(this).closest('.avatar').fadeOut(function(){
        $(this).remove();
      });
    });

    // More button
    $(document).on('click', '[data-provide~="more-avatar"]', function(){
      var list = $(this).closest('.avatar-list');

      $(this).fadeOut(function(){
        $(this).remove();

        if ( $(this).hasDataAttr('url') ) {
          $('<div>').load( $(this).data('url'), function(){
            var avatars = $(this).html();
            list.append(avatars);
          });

        }
      });
    });




    // Ripple for flat button
    //
    $(document).on('click', '.btn-flat:not(.no-wave)', function(e){
      var x = e.pageX;
      var y = e.pageY;
      var clickY = y - $(this).offset().top;
      var clickX = x - $(this).offset().left;
      var box = this;

      var setX = parseInt(clickX);
      var setY = parseInt(clickY);
      $(this).find("svg").remove();
      $(this).append('<svg><circle cx="'+setX+'" cy="'+setY+'" r="'+0+'"></circle></svg>');

      var circle = $(box).find("circle");
      circle.animate(
        {
          "r" : $(box).outerWidth()
        },
        {
          duration: 400,
          step: function(val){
            circle.attr("r", val);
          },
          complete: function() {
            circle.fadeOut('fast');
          }
        }
      );
    });




    // Callout
    //
    $(document).on('click', '[data-dismiss="callout"]', function(){
      $(this).closest('.callout').fadeOut(function(){
        $(this).remove();
      });
    });




    // Tabs
    //
    $(document).on('click', '[data-dismiss="tab"]', function(){
      $(this).closest('.nav-item').fadeOut(function(){
        $(this).remove();
      });
    });



    // Rating
    //
    var ratingCheckHandle = function(rating) {
      if ( rating.find('input:checked').length ) {
        rating.attr('data-has-rate', 'true');
      }
      else {
        rating.attr('data-has-rate', 'false');
      }
    }

    $(document).on('click', '.rating-remove', function(){
      $(this).closest('.rating').find('input').prop('checked', false);
      ratingCheckHandle( $(this).closest('.rating') );
    });

    $('.rating').each(function(){
      ratingCheckHandle( $(this) );
    });

    $(document).on('change', '.rating input', function(){
      ratingCheckHandle( $(this).closest('.rating') );
    });




    // Loader
    //
    $(document).on('click', '[data-provide~="loader"]', function(e){
      e.preventDefault();

      var target  = app.getTarget( $(this) );
      var url     = app.getURL( $(this) );

      if ( $(this).hasDataAttr('spinner') ) {
        var spinner = $(this).data('spinner');
        $(target).html(spinner);
      }

      $(target).load(url);
    });






    // Lookup textual
    //
    $(document).on('click', '.lookup-textual .lookup-placeholder', function(){
      $(this).closest('.lookup').find('input').focus();
    });

    $(document).on('focus blur keyup', '.lookup-textual input', function(){
      var placeholder = $(this).closest('.lookup').find('.lookup-placeholder');
      if ( $(this).val() == '' ) {
        placeholder.css('display', 'inline-block');
      }
      else {
        placeholder.css('display', 'none');
      }
    });



    // Fullscreen lookup
    //
    $(document).on('keyup', '.lookup-fullscreen[data-url] .lookup-form input', function(){
      var keyword = $(this).val();
      var lookup = $(this).closest('.lookup-fullscreen');
      var url = lookup.data('url');
      lookup.find('.lookup-results').load(url, {s: keyword});
    });




  }



}(jQuery);
