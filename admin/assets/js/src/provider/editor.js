

// =====================
// Editor plugins
// =====================
//
+function($){


  provider.initEditors = function() {

    provider.initSummernote();

  };





  provider.initSummernote = function() {
    if ( ! $.fn.summernote ) {
      return;
    }


    provider.provide('summernote', function(){
      var options = {
        dialogsInBody: true,
        dialogsFade: true
      };
      options = $.extend(options, app.getDataOptions( $(this) ));

      if ( options.toolbar ) {
        switch( options.toolbar.toLowerCase() ) {
          case 'slim':
            options.toolbar = [
              // [groupName, [list of button]]
              ['style', ['bold', 'underline', 'clear']],
              ['color', ['color']],
              ['para', ['ul', 'ol']],
              ['insert', ['link', 'picture']]
            ];
            break;

          case 'full':
            options.toolbar = [
              // [groupName, [list of button]]
              ['para_style', ['style']],
              ['style', ['bold', 'italic', 'underline', 'clear']],
              ['font', ['strikethrough', 'superscript', 'subscript']],
              ['fontsize', ['fontname', 'fontsize', 'height']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph', 'hr']],
              ['table', ['table']],
              ['insert', ['link', 'picture', 'video']],
              ['do', ['undo', 'redo']],
              ['misc', ['fullscreen', 'codeview', 'help']]
            ];
            break;
        }
      }

      $(this).summernote(options);
    });



    $(document).on('click', '[data-summernote-edit]', function(){
      var target = $(this).data('summernote-edit');
      $(target).summernote({focus: true});
    });


    $(document).on('click', '[data-summernote-save]', function(){
      var target = $(this).data('summernote-save');
      var callback = $(this).data('callback');
      var markup = $(target).summernote('code');
      $(target).summernote('destroy');
      app.call(callback, markup);
    });

  };






  provider.initQuill = function() {
    if ( window['Quill'] === undefined ) {
      return;
    }


    provider.provide('quill', function(){

      var options = {
        theme: 'snow'
      };

      var toolbarFullOptions = [
        [
          { 'font': [] },
          { 'header': [1, 2, 3, 4, 5, 6, false] },
          { 'size': ['small', false, 'large', 'huge'] }
        ],
        ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
        [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
        [{ 'script': 'sub'}, { 'script': 'super' }],
        [{ 'header': 1 }, { 'header': 2 }, 'blockquote', 'code-block'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'indent': '-1'}, { 'indent': '+1' }],
        [{ 'direction': 'rtl' }, { 'align': [] }],        // text direction
        ['link', 'image', 'video'],
        ['clean']                                         // remove formatting button
      ];

      $.extend(options, app.getDataOptions( $(this) ));

      if ( options.toolbar !== undefined ) {
        var toolbar = options.toolbar.toLowerCase();
        if ( toolbar == 'full' ) {

          // TODO:
          // Load highlight js
          /*
          $LAB.script('highlight/highlight.pack.js');
          if ( options.codeStyle === undefined ) {
            app.loadStyle('highlight/styles/monokai-sublime.css', app.dir.vendor);
          }
          else {
            app.loadStyle('highlight/styles/'+ options.codeStyle +'.css', app.dir.vendor);
          }
          */

          options.modules = {
            //syntax: 'true',
            toolbar: toolbarFullOptions
          };
        }
      }

      new Quill( $(this)[0], options);

    });


  };



}(jQuery);
