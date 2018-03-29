

// =====================
// Emoji plugins
// =====================
//
+function($){


  provider.initEmojies = function() {

    provider.initEmojione();

  };


  provider.initEmojione = function() {
    if ( window["emojione"] === undefined ) {
      return;
    }

    emojione.imageType = 'svg';
    emojione.sprites = true;
    emojione.ascii = true;
    emojione.imagePathSVGSprites = app.dir.vendor +'/emojione/emojione.svg';

    provider.provide('emoji', function(){
      var original = $(this).html();
      // use .shortnameToImage if only converting shortnames (for slightly better performance)
      var converted = emojione.toImage(original);
      $(this).html(converted);
    });

  };


}(jQuery);
