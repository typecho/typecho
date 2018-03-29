

// =====================
// Modaler
// =====================
//
+function($){



  app.modaler = function(options) {

    var setting = $.extend({}, app.defaults.modaler, options);



    var handleCallback = function() {

      // Bootstrap modal events
      //
      if ( setting.onShow ) {
        $('#'+ id).on('show.bs.modal', function(e){
          app.call( setting.onShow, e);
        });
      }

      if ( setting.onShown ) {
        $('#'+ id).on('shown.bs.modal', function(e){
          app.call( setting.onShown, e);
        });
      }

      if ( setting.onHide ) {
        $('#'+ id).on('hide.bs.modal', function(e){
          app.call( setting.onHide, e);
        });
      }

      if ( setting.onHidden ) {
        $('#'+ id).on('hidden.bs.modal', function(e){
          app.call( setting.onHidden, e);
        });
      }


      // Handle confirm callback
      //
      $('#'+ id).find('[data-perform="confirm"]').on('click', function(){

        // Hasn't set
        if ( setting.onConfirm == null ) {
          return;
        }

        // Is a function
        if ( $.isFunction(setting.onConfirm) ) {
          setting.onConfirm($('#'+ id));
          return;
        }

        // Is string value, so call it
        if ( setting.onConfirm.substring ) {
          app.call( setting.onConfirm, $('#'+ id) );
        }

      });


      // Handle cancel callback
      //
      $('#'+ id).find('[data-perform="cancel"]').on('click', function(){

        // Hasn't set
        if ( setting.onCancel == null ) {
          return;
        }

        // Is a function
        if ( $.isFunction(setting.onCancel) ) {
          setting.onCancel($('#'+ id));
          return;
        }

        // Is string value, so call it
        if ( setting.onCancel.substring ) {
          app.call( setting.onCancel, $('#'+ id) );
        }

      });
    }





    if ( setting.modalId ) {
      $('#'+ setting.modalId).modal('show');
      return;
    }


    var id = 'modal-'+ app.guid();



    //----------------------------------
    // We recieve modal markup from url
    //
    if (setting.isModal) {

      $('<div>').load( setting.url, function(){
        $('body').append( $(this).find('.modal').attr('id', id).outerHTML() );

        $('#'+ id).modal('show');


        // Destroy after close
        //
        if ( setting.autoDestroy ) {
          $('#'+ id).on('hidden.bs.modal', function(){
            $('#'+ id).remove();
          });
        }
        else {
          $(setting.this).attr('data-modal-id', id);
        }


        handleCallback();


      });
    }





    ////----------------------------------
    // We should design the modal
    //
    else {

      switch (setting.size) {
        case 'sm':
          setting.size = 'modal-sm';
          break;

        case 'lg':
          setting.size = 'modal-lg';
          break;

        default:
          //setting.size = '';
      }


      if ( setting.type ) {
        setting.type = 'modal-'+ setting.type;
      }


      // Header code
      //
      var html_header = '';
      if ( setting.headerVisible ) {
        html_header +=
          '<div class="modal-header"> \
            <h5 class="modal-title">'+ setting.title +'</h5> \
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button> \
          </div>';
      }


      // Footer code
      //
      var html_footer = '';
      if ( setting.footerVisible ) {
        html_footer += '<div class="modal-footer">';

        if ( setting.cancelVisible ) {
          html_footer += '<button class="'+ setting.cancelClass +'" data-dismiss="modal" data-perform="cancel">'+ setting.cancelText +'</button>';
        }

        if ( setting.confirmVisible ) {
          html_footer += '<button class="'+ setting.confirmClass +'" data-dismiss="modal" data-perform="confirm">'+ setting.confirmText +'</button>';
        }

        html_footer += '</div>';
      }

      // Modal code
      //
      var modal_html =
          '<div class="modal fade '+ setting.type +'" id="'+ id +'" tabindex="-1"'+ ( !setting.backdrop ? ' data-backdrop="false"' : '') +'> \
            <div class="modal-dialog '+ setting.size +'"> \
              <div class="modal-content"> \
                '+ html_header +' \
                <div class="modal-body '+ setting.bodyExtraClass +'"> \
                  '+ setting.spinner +' \
                </div> \
                '+ html_footer +' \
              </div> \
            </div> \
          </div>';


      // Show modal
      $('body').append(modal_html);
      $('#'+ id).modal('show');


      // Destroy after close
      //
      if ( setting.autoDestroy ) {
        $('#'+ id).on('hidden.bs.modal', function(){
          $('#'+ id).remove();
        });
      }
      else {
        $(setting.this).attr('data-modal-id', id);
      }


      // Load data into the modal
      //
      if ( setting.url ) {
        $('#'+ id).find('.modal-body').load(setting.url, function(){
          //$(this).removeClass('p-a-0');
          handleCallback();
        });
      }
      else if ( setting.html ) {
        $('#'+ id).find('.modal-body').html(setting.html);
        handleCallback();
      }
      else if ( setting.target ) {
        $('#'+ id).find('.modal-body').html( $(setting.target).html() );
        handleCallback();
      }




    }




  }


  // Enable data attribute options
  $(document).on('click', '[data-provide~="modaler"]', function(){
    app.modaler( app.getDataOptions($(this)) );
    //app.modaler.apply($(this), options);
  });




}(jQuery);

