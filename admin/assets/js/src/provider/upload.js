

// =====================
// Upload plugins
// =====================
//
+function($){



  provider.initUploads = function() {

    provider.initDropify();
    provider.initDropzone();

  };


  provider.initDropify = function() {
    if ( ! $.fn.dropify ) {
      return;
    }

    provider.provide('dropify', function(){
      $(this).dropify();
    });

  }



  provider.initDropzone = function() {
    if ( ! $.fn.dropzone ) {
      return;
    }

    Dropzone.autoDiscover = false;

    provider.provide('dropzone', function(){
      var options = {};
      options = $.extend( options, app.getDataOptions( $(this) ));
      $(this).addClass('dropzone');
      $(this).dropzone( options );
    });

  }


}(jQuery);
