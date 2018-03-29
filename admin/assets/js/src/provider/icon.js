

// =====================
// Icon plugins
// =====================
//
+function($){


  provider.initIcons = function() {

    provider.initI8icons();

  };


  provider.initI8icons = function() {

    provider.provide('iconI8', function(){
      $(document).i8icons(function(icons) {
        icons.defaultIconSetUrl(app.dir.vendor +'i8-icon/i8-color-icons.svg');
      });
    });

  };



}(jQuery);
