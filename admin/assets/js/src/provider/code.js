

// =====================
// Code plugins
// =====================
//
+function($){


  provider.initCodes = function() {

    provider.initPrism();
    provider.initClipboard();

  };



  provider.initPrism = function() {


    $('pre:not(.no-copy) > code[class*="language-"]').each(function() {
      $(this).before('<button class="btn btn-sm btn-bold btn-secondary clipboard-copy">Copy</button>');
    });

    // Move copy button when the content is scrolling
    $('.clipboard-copy').parent().on('scroll', function(){
      $(this).find('.clipboard-copy').css('transform', 'translate('+ $(this).scrollLeft() +'px, '+ $(this).scrollTop() +'px)');
    });

    if ($('.clipboard-copy').length > 0) {
      var clipboardSnippets = new Clipboard('.clipboard-copy', {
        target: function(trigger) {
          return trigger.nextElementSibling;
        }
      });

      clipboardSnippets.on('success', function(e) {
        e.clearSelection();
        app.toast('Copied.');
      });
    }
  };





  provider.initClipboard = function() {
    new Clipboard('[data-clipboard-text]');

  };




}(jQuery);
