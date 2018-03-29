

// =====================
// Form plugins
// =====================
//
+function($){


  provider.initForms = function() {

    provider.initSelectpicker();
    provider.initDatepicker();
    provider.initMinicolor();
    provider.initClockpicker();
    provider.initMaxlength();
    provider.initStrength();
    provider.initTagsinput();
    provider.initKnob();
    provider.initNouislider();
    provider.initSwitchery();
    provider.initFormatter();
    provider.initValidation();
    provider.initWizard();

  };



  // Selectpicker
  //
  provider.initSelectpicker = function() {

    if ( ! $.fn.selectpicker ) {
      return;
    }

    provider.provide('selectpicker', function(){
      $(this).selectpicker({
        iconBase: '',
        tickIcon: 'ti-check',
        style: 'btn-light'
      });
    });

  };




  // Datepicker
  //
  provider.initDatepicker = function() {
    if ( ! $.fn.datepicker ) {
      return;
    }

    $.fn.datepicker.defaults.multidateSeparator = ", ";

    provider.provide('datepicker', function(){
      if ( $(this).prop("tagName") == 'INPUT' ) {
        $(this).datepicker();
      }
      else {
        $(this).datepicker({
          inputs: [$(this).find('input:first'), $(this).find('input:last')]
        });
      }
    });
  };




  // Minicolor
  //
  provider.initMinicolor = function() {
    if ( ! $.fn.minicolors ) {
      return;
    }

    provider.provide('colorpicker', function(){
      var options = {
        change: function(value, opacity) {
          if( !value ) return;
          if( opacity ) value += ', ' + opacity;
        },
        theme: 'bootstrap'
      };


      options = $.extend( options, app.getDataOptions( $(this) ));

      if ( 'rgba' === options.format ) {
        options.format = 'rgb';
        options.opacity = true;
      }

      if ( $(this).attr('data-swatches') ) {
        options.swatches = $(this).attr('data-swatches').split('|');
      }


      $(this).minicolors( options );
    });


  }




  // Clockpicker
  //
  provider.initClockpicker = function() {
    if ( ! $.fn.clockpicker ) {
      return;
    }

    provider.provide('clockpicker', function(){
      $(this).clockpicker({
        donetext: 'Done'
      });
    });

  }




  // Max length control
  //
  provider.initMaxlength = function() {
    if ( ! $.fn.maxlength ) {
      return;
    }

    provider.provide('maxlength', function(){
      var options = {
        warningClass: 'badge badge-warning',
        limitReachedClass: 'badge badge-danger',
        placement: 'bottom-right-inside',
      };

      options = $.extend( options, app.getDataOptions( $(this) ));
      $(this).maxlength(options);
    });

  }




  // Password strength
  //
  provider.initPwStrength = function() {
    if ( ! $.fn.pwstrength ) {
      return;
    }

    provider.provide('pwstrength', function(){
      var options = {
        ui : {
          bootstrap4: true,
          progressBarEmptyPercentage: 0,
          showVerdicts: false
        },
        common : {
          usernameField: $(this).dataAttr('username', '#username')
        }
      }

      $(this).pwstrength(options);
      $(this).add( $(this).next() ).wrapAll('<div class="pwstrength"></div>');

      // Vertical progress
      if ( $(this).is('[data-vertical="true"]') ) {
        var height = $(this).outerHeight() - 10,
            right  = -height / 2 + 7,
            bottom = height / 2 + 4;
        $(this).next('.progress').css({
          width: height,
          right: right,
          bottom: bottom
        });
      }
    });

  }




  // Tags input
  //
  provider.initTagsinput = function() {
    if ( ! $.fn.tagsinput ) {
      return;
    }

    provider.provide('tagsinput', function(){
      $(this).tagsinput();
    });

  }




  // Knob
  //
  provider.initKnob = function() {
    if ( ! $.fn.knob ) {
      return;
    }

    provider.provide('knob', function(){
      var options = {
        thickness: .1,
        width: 120,
        height: 120,
        fgColor: app.colors.primary,
        bgColor: app.colors.bg,
      };

      options = $.extend( options, app.getDataOptions( $(this) ));
      $(this).knob( options );
    });

  }




  // NoUiSlider
  //
  provider.initNouislider = function() {
    if ( window['noUiSlider'] === undefined ) {
      return;
    }

    provider.provide('slider', function(index, element){
      var options = {
        range: {
          'min'     : Number( $(this).dataAttr('min', 0) ),
          'max'     : Number( $(this).dataAttr('max', 100) )
        },
        step        : 1,
        start       : $(this).dataAttr('value', 0),
        connect     : 'lower',
        margin      : 0,
        limit       : 100,
        orientation : 'horizontal',
        direction   : 'ltr',
        tooltips    : false,
        animate     : true,
        behaviour   : 'tap',

        format: {
          to: function ( value ) {
            return value;
          },
          from: function ( value ) {
            return value;
          }
        }
      }

      options = $.extend( options, app.getDataOptions( $(this) ));

      var target      = $(this).dataAttr('target', 'none');

      // If it's range slider
      if ( typeof options.start === 'string' && options.start.indexOf(',') > -1 ) {
        options.start = options.start.split(",");


        if ( !$(this).hasDataAttr('connect') ) {
          options.connect = true;
        }

        if ( !$(this).hasDataAttr('behaviour') ) {
          options.behaviour = 'tap-drag';
        }
      }
      else {
        delete options.limit; // Limit option should be available for linear sliders
      }

      // If it's vertical
      if ( options.orientation == 'vertical' ) {
        if ( !$(this).hasDataAttr('direction') ) {
          options.direction = 'rtl';
        }
      }

      // Target
      if ( target != 'none' ) {
        if ( target == 'next' ) {
          target = $(this).next();
        }
        else if ( target == 'prev' ) {
          target = $(this).prev();
        }
      }


      // Create it
      noUiSlider.create(element, options);

      // Event update
      element.noUiSlider.on('update', function(values, handle) {
        var strVal = values.toString();
        $(target).text(strVal).val(strVal);

        if ( $(element).hasDataAttr('on-update') ) {
          app.call( $(element).data('on-update'), values );
        }

      });

      // Event change
      element.noUiSlider.on('change', function(values, handle) {
        if ( $(element).hasDataAttr('on-change') ) {
          app.call( $(element).data('on-change'), values );
        }

      });
    });

  }




  // Switchery
  //
  provider.initSwitchery = function() {
    if ( window['Switchery'] === undefined ) {
      return;
    }

    provider.provide('switchery', function(){
      var options = {
        color: app.colors.primary,
        speed: '0.5s'
      }

      options = $.extend( options, app.getDataOptions( $(this) ));
      new Switchery(this, options);
    });

  }




  // Mask / Formatter
  //
  provider.initFormatter = function() {
    if ( ! $.fn.formatter ) {
      return;
    }

    provider.provide('formatter', function(){
      var options = {
        pattern: $(this).data('format'),
        persistent: $(this).dataAttr('persistent', true),
      }

      $(this).formatter( options );
    });

  }




  // Validator
  //
  provider.initValidation = function() {
    if ( ! $.fn.validator ) {
      return;
    }

    $.fn.validator.Constructor.FOCUS_OFFSET = 100;

    provider.provide('validation', function(){
      $(this).validator();
    });


    $(document).on('click', '[data-perform="validation"]', function(){
      var target = app.getTarget($(this));

      if ( target == undefined) {
        $(this).parents('[data-provide="validation"]').validator('validate');
      }
      else {
        $(target).parents('[data-provide="validation"]').validator('validate');
      }
    });

  }




  // Wizard
  //
  provider.initWizard = function() {
    if ( ! $.fn.bootstrapWizard ) {
      return;
    }

    provider.provide('wizard', function(){

      var wizard   = $(this);
      var nav_item = $(this).find('.nav-item');
      var tab_pane = $(this).find('.tab-pane');

      wizard.bootstrapWizard({
        tabClass:         'nav-process',
        nextSelector:     '[data-wizard="next"]',
        previousSelector: '[data-wizard="prev"]',
        firstSelector:    '[data-wizard="first"]',
        lastSelector:     '[data-wizard="last"]',
        finishSelector:   '[data-wizard="finish"]',
        backSelector:     '[data-wizard="back"]',

        onTabClick: function(tab, navigation, index) {
          if ( !wizard.is('[data-navigateable="true"]') ) {
            return false;
          }
        },


        onNext: function(tab, navigation, index) {

          var current_index = wizard.bootstrapWizard('currentIndex');
          var curr_tab = tab_pane.eq(current_index);
          var tab = tab_pane.eq(index);

          // Validator
          var validator_selector = '[data-provide="validation"]';
          var validator = curr_tab.find(validator_selector).addBack(validator_selector);
          if ( validator.length ) {
            validator.validator('validate');
            if ( validator.find('.has-error').length ) {
              return false;
            }
          }


          // Callback
          //
          if ( wizard.hasDataAttr('on-next') ) {
            app.call( wizard.data('on-next'), tab, navigation, index );
          }
        },


        onBack: function(tab, navigation, index) {

          // Callback
          //
          if ( wizard.hasDataAttr('on-back') ) {
            app.call( wizard.data('on-back'), tab, navigation, index );
          }
        },


        onPrevious: function(tab, navigation, index) {

          // Callback
          //
          if ( wizard.hasDataAttr('on-previous') ) {
            app.call( wizard.data('on-previous'), tab, navigation, index );
          }
        },


        onTabShow: function(tab, navigation, index) {

          var tab = tab_pane.eq(index);
          var nav = nav_item.eq(index);
          var max = wizard.bootstrapWizard('navigationLength');

          // Finish button
          if ( index == max ) {
            wizard.find('[data-wizard="next"]').addClass('d-none');
            wizard.find('[data-wizard="finish"]').removeClass('d-none');
          }
          else {
            wizard.find('[data-wizard="next"]').removeClass('d-none');
            wizard.find('[data-wizard="finish"]').addClass('d-none');
          }

          // Nav classes
          navigation.children().removeClass('processing');
          navigation.children(':lt('+ index +'):not(.complete)').addClass('complete');
          nav.addClass('processing');

          if ( !wizard.is('[data-stay-complete="true"]') ) {
            navigation.children(':gt('+ index +').complete').removeClass('complete');
          }

          // Ajax load
          if ( tab.hasDataAttr('url') ) {
            tab.load( tab.data('url') );
          }

          // Callback for tab
          if ( tab.hasDataAttr('callback') ) {
            app.call( tab.data('callback'), tab );
          }

          // Callback for wizard
          //
          if ( wizard.hasDataAttr('on-tab-show') ) {
            app.call( wizard.data('on-tab-show'), tab, navigation, index );
          }

        },


        onFinish: function(tab, navigation, index) {

          var curr_tab = tab_pane.eq(index);

          // Validator
          var validator_selector = '[data-provide="validation"]';
          var validator = curr_tab.find(validator_selector).addBack(validator_selector);
          if ( validator.length ) {
            validator.validator('validate');
            if ( validator.find('.has-error').length ) {
              validator.closest('form').one('submit', function(e) {
                e.preventDefault();
              });
              return false;
            }
          }

          // Navigation
          var nav = nav_item.eq(index);
          nav.addClass('complete').removeClass('processing');

          // Callback
          //
          if ( wizard.hasDataAttr('on-finish') ) {
            app.call( wizard.data('on-finish'), tab, navigation, index );
          }

        },


      });

    });

  }







  // Typeahead
  //
  provider.initTypeahead = function() {



  };






}(jQuery);
