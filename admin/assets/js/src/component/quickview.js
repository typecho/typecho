

// =====================
// Quickview
// =====================
//
+function($, window){

  var quickview = {};

  quickview.init = function() {


    $('.quickview-body').perfectScrollbar();

    // Update scrollbar on tab change
    //
    $(document).on('shown.bs.tab', '.quickview-header a[data-toggle="tab"]', function (e) {
      $(this).closest('.quickview').find('.quickview-body').perfectScrollbar('update');
    })



    // Quickview closer
    //
    $(document).on('click', '[data-dismiss="quickview"]', function(){
      quickview.close( $(this).closest('.quickview') );
    });



    // Handle quickview openner
    //
    $(document).on('click', '[data-toggle="quickview"]', function(e) {
      e.preventDefault();
      var target = app.getTarget($(this));

      if (target == false) {
        quickview.close( $(this).closest('.quickview') )
      }
      else {
        var url = '';
        if ( $(this).hasDataAttr('url') ) {
          url = $(this).data('url');
        }
        quickview.toggle(target, url);
      }
    });



    // Close quickview when backdrop touches
    //
    $(document).on('click', '.backdrop-quickview', function(){
      var qv = $(this).attr('data-target');
      quickview.close(qv);
    });
    $(document).on('click', '.quickview .close, [data-dismiss="quickview"]', function(){
      var qv = $(this).closest('.quickview');
      quickview.close(qv);
    });

  };



  // Toggle open/close state
  //
  quickview.toggle = function(e, url) {
    if ( $(e).hasClass('reveal') ) {
      quickview.close(e);
    }
    else {
      if ( url !== '' ) {
        $(e).html('<div class="spinner-linear"><div class="line"></div></div>');
        $(e).load(url, function() {
          $('.quickview-body').perfectScrollbar();
        });
      }
      quickview.open(e);
    }
  }



  // Open quickview
  //
  quickview.open = function(e) {
    var quickview = $(e);

    // Load content from URL if required
    if ( quickview.hasDataAttr('url') && 'true' !== quickview.data('url-has-loaded') ) {
      quickview.load( quickview.data('url'), function() {
        $('.quickview-body').perfectScrollbar();
        // Don't load it next time, if don't need to
        if ( quickview.hasDataAttr('always-reload') && 'true' === quickview.data('always-reload') ) {

        } else {
          quickview.data('url-has-loaded', 'true');
        }
      });
    }

    // Open it
    quickview.addClass('reveal').not('.backdrop-remove').after('<div class="app-backdrop backdrop-quickview" data-target="'+ e +'"></div>');
  };



  // Close quickview
  //
  quickview.close = function(e) {
    $(e).removeClass('reveal');
    $('.backdrop-quickview').remove();
  };



  window.quickview = quickview;
}(jQuery, window);
