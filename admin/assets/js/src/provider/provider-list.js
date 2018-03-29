

// =====================
// provider list
// =====================
//
+function($){


  //
  //
  provider.list = {

    // ======================================================================
    // Chart
    //
    easypie: {
      selector: 'easypie',
      callback: 'initEasyPieChart',
      css:      '',
      js:       'easypiechart/jquery.easypiechart.min.js',
    },


    peity: {
      selector: 'peity',
      callback: 'initPeity',
      css:      '',
      js:       'jquery.peity/jquery.peity.min.js',
    },


    sparkline: {
      selector: 'sparkline',
      callback: 'initSparkline',
      css:      '',
      js:       'sparkline/sparkline.min.js',
    },


    chartjs: {
      selector: 'chartjs',
      callback: 'initChartjs',
      css:      '',
      js:       [
                  'chartjs/Chart.min.js',
                  'moment/moment.min.js',
                ]
    },


    morris: {
      selector: 'morris',
      callback: 'initMorris',
      css:      'morris/morris.css',
      js:       [
                  'raphael/raphael.min.js',
                  'morris/morris.min.js',
                ]
    },






    // ======================================================================
    // Code
    //
    prism: {
      selector: '$ code[class*="language-"]',
      callback: 'initPrism',
      css:      'prism/prism.css',
      js:       [
                  'prism/prism.js',
                  'clipboard/clipboard.min.js'
                ]
    },



    clipboard: {
      selector: '$ [data-clipboard-text]',
      callback: 'initClipboard',
      js:       'clipboard/clipboard.min.js'
    },




    // ======================================================================
    // Editor
    //
    summernote: {
      selector: 'summernote',
      callback: 'initSummernote',
      css:      'summernote/summernote.css',
      js:       'summernote/summernote.min.js',
    },




    quill: {
      selector: 'quill',
      callback: 'initQuill',
      css:      [
                  //'highlight/styles/monokai-sublime.css',
                  'quill/quill.bubble.css',
                  'quill/quill.snow.css',
                ],
      js:       [
                  //'highlight/highlight.pack.js',
                  'quill/quill.min.js',
                ]
    },




    // ======================================================================
    // Emoji
    //
    emoji: {
      selector: 'emoji',
      callback: 'initEmojione',
      css:      '',
      js:       'emojione/emojione.min.js',
    },





    // ======================================================================
    // Form
    //
    selectpicker: {
      selector: 'selectpicker',
      callback: 'initSelectpicker',
      css:      'bootstrap-select/css/bootstrap-select.min.css',
      js:       'bootstrap-select/js/bootstrap-select.min.js',
    },


    datepicker: {
      selector: 'datepicker',
      callback: 'initDatepicker',
      css:      'bootstrap-datepicker/css/bootstrap-datepicker3.min.css',
      js:       'bootstrap-datepicker/js/bootstrap-datepicker.min.js',
    },


    timepicker: {
      selector: 'timepicker',
      //callback: '',
      css:      'bootstrap-timepicker/bootstrap-timepicker.min.css',
      js:       'bootstrap-timepicker/bootstrap-timepicker.min.js',
    },


    colorpicker: {
      selector: 'colorpicker',
      callback: 'initMinicolor',
      css:      'jquery-minicolors/jquery.minicolors.css',
      js:       'jquery-minicolors/jquery.minicolors.min.js',
    },


    clockpicker: {
      selector: 'clockpicker',
      callback: 'initClockpicker',
      css:      'bootstrap-clockpicker/bootstrap-clockpicker.min.css',
      js:       'bootstrap-clockpicker/bootstrap-clockpicker.min.js',
    },


    maxlength: {
      selector: 'maxlength',
      callback: 'initMaxlength',
      css:      '',
      js:       'bootstrap-maxlength/bootstrap-maxlength.min.js',
    },


    pwstrength: {
      selector: 'pwstrength',
      callback: 'initPwStrength',
      css:      '',
      js:       'bootstrap-pwstrength/pwstrength-bootstrap.min.js',
    },


    tagsinput: {
      selector: 'tagsinput',
      callback: 'initTagsinput',
      css:      'bootstrap-tagsinput/bootstrap-tagsinput.css',
      js:       'bootstrap-tagsinput/bootstrap-tagsinput.min.js',
    },


    knob: {
      selector: 'knob',
      callback: 'initKnob',
      css:      '',
      js:       'knob/jquery.knob.min.js',
    },


    slider: {
      selector: 'slider',
      callback: 'initNouislider',
      css:      'nouislider/nouislider.min.css',
      js:       'nouislider/nouislider.min.js',
    },


    switchery: {
      selector: 'switchery',
      callback: 'initSwitchery',
      css:      'switchery/switchery.min.css',
      js:       'switchery/switchery.min.js',
    },


    formatter: {
      selector: '$ [data-format]',
      callback: 'initFormatter',
      css:      '',
      js:       'formatter/jquery.formatter.min.js',
    },


    // New version upon finishing alpha releases of Bootstrap
    validation: {
      selector: 'validation',
      callback: 'initValidation',
      css:      '',
      js:       'bootstrap-validator/validator-bs4.min.js',
    },


    wizard: {
      selector: 'wizard',
      callback: 'initWizard',
      css:      '',
      js:       'bootstrap-wizard/bootstrap-wizard.min.js',
    },


    typeahead: {
      selector: 'typeahead',
      js:       [
                  'typeahead/bloodhound.min.js',
                  'typeahead/typeahead.jquery.min.js'
                ],
    },


    bloodhound: {
      selector: 'bloodhound',
      js:       'typeahead/bloodhound.min.js',
    },




    // ======================================================================
    // Icon
    //
    iconMaterial: {
      selector: '$ .material-icons',
      css:      'material-icons/css/material-icons.css',
    },


    icon7Stroke: {
      selector: '$ [class*="pe-7s-"]',
      css:      [
                  'pe-icon-7-stroke/css/pe-icon-7-stroke.min.css',
                  'pe-icon-7-stroke/css/helper.min.css'
                ]
    },


    iconIon: {
      selector: '$ [class*="ion-"]',
      css:      'ionicons/css/ionicons.min.css',
    },


    iconI8: {
      selector: '$ [data-i8-icon]',
      callback: 'initI8icons',
      css:      '',
      js:       'i8-icon/jquery-i8-icon.min.js',
    },





    // ======================================================================
    // Map
    //
    map: {
      selector: 'map',
      callback: 'initMap',
      css:      '',
      js:       'https://maps.googleapis.com/maps/api/js?key='+ app.defaults.googleApiKey +'&callback=app.map',
    },


    mapael: {
      selector: 'mapael',
      callback: 'initMapael',
      css:      '',
      js:       [
                  'jquery.mousewheel/jquery.mousewheel.min.js',
                  'raphael/raphael.min.js',
                  'mapael/jquery.mapael.min.js'
                ],
    },






    // ======================================================================
    // Table
    //
    table: {
      selector: 'table',
      callback: 'initBootstrapTable',
      css:      'bootstrap-table/bootstrap-table.min.css',
      js:       [
                  'bootstrap-table/bootstrap-table.min.js',
                  'bootstrap-table/extensions/editable/bootstrap-table-editable.min.js',
                  'bootstrap-table/extensions/export/bootstrap-table-export.min.js',
                  'bootstrap-table/extensions/resizable/bootstrap-table-resizable.min.js',
                  'bootstrap-table/extensions/mobile/bootstrap-table-mobile.min.js',
                  'bootstrap-table/extensions/filter-control/bootstrap-table-filter-control.min.js',
                  'bootstrap-table/extensions/multiple-sort/bootstrap-table-multiple-sort.min.js'
                ]
    },



    jsgrid: {
      selector: 'jsgrid',
      callback: 'initJsGrid',
      css:      [
                  'jsgrid/jsgrid.min.css',
                  'jsgrid/jsgrid-theme.min.css'
                ],
      js:       'jsgrid/jsgrid.min.js'
    },



    datatables: {
      selector: 'datatables',
      callback: 'initDatatables',
      css:      'datatables/css/dataTables.bootstrap4.min.css',
      js:       [
                  'datatables/js/jquery.dataTables.min.js',
                  'datatables/js/dataTables.bootstrap4.min.js',
                ]
    },





    // ======================================================================
    // UI
    //
    sweetalert: {
      selector: 'sweetalert',
      callback: 'initSweetalert2',
      css:      'sweetalert2/sweetalert2.min.css',
      js:       'sweetalert2/sweetalert2.min.js',
    },


    lity: {
      selector: 'lity',
      callback: 'initLity',
      css:      'lity/lity.min.css',
      js:       'lity/lity.min.js',
    },


    sortable: {
      selector: 'sortable',
      callback: 'initSortable',
      css:      '',
      js:       'html5sortable/html.sortable.min.js',
    },


    shepherd: {
      selector: 'shepherd',
      callback: 'initShepherd',
      css:      'shepherd/css/shepherd-theme-arrows-plain-buttons.css',
      js:       [
                  'shepherd/js/tether.js',
                  'shepherd/js/shepherd.min.js',
                ],
    },


    shuffle: {
      selector: 'shuffle',
      callback: 'initShuffle',
      css:      '',
      js:       [
                  'imagesloaded/imagesloaded.pkgd.min.js',
                  'shuffle/shuffle.min.js',
                ]
    },


    photoswipe: {
      selector: 'photoswipe',
      callback: 'initPhotoswipe',
      css:      [
                  'photoswipe/photoswipe.min.css',
                  'photoswipe/default-skin/default-skin.min.css'
                ],
      js:       'photoswipe/jquery.photoswipe-global.js',
    },


    swiper: {
      selector: 'swiper',
      callback: 'initSwiper',
      css:      'swiper/css/swiper.min.css',
      js:       'swiper/js/swiper.min.js',
    },


    fullscreen: {
      selector: 'fullscreen',
      callback: 'initFullscreen',
      js:       'screenfull/screenfull.min.js',
    },


    jqueryui: {
      selector: 'jqueryui',
      //callback: 'initFullscreen',
      js:       'jqueryui/jquery-ui.min.js',
    },




    // ======================================================================
    // Upload
    //
    dropify: {
      selector: 'dropify',
      callback: 'initDropify',
      css:      'dropify/css/dropify.min.css',
      js:       'dropify/js/dropify.min.js',
    },


    dropzone: {
      selector: 'dropzone',
      callback: 'initDropzone',
      css:      'dropzone/min/dropzone.min.css',
      js:       'dropzone/min/dropzone.min.js',
    },



    // ======================================================================
    // Misc
    //
    fullcalendar: {
      selector: 'fullcalendar',
      callback: 'initFullcalendar',
      css:      'fullcalendar/fullcalendar.min.css',
      js:       [
                  'moment/moment.min.js',
                  'fullcalendar/fullcalendar.min.js',
                ]
    },



    justified: {
      selector: 'justified-gallery',
      callback: 'initJustifiedGallery',
      css:      'justified-gallery/css/justifiedGallery.min.css',
      js:       'justified-gallery/js/jquery.justifiedGallery.min.js',
    },



    animate: {
      selector: '$ .animated',
      css:      'animate/animate.min.css',
    },



    intercoolerjs: {
      selector: '$ [ic-get-from], [ic-post-to], [ic-put-to], [ic-patch-to], [ic-delete-from], [data-ic-get-from], [data-ic-post-to], [data-ic-put-to], [data-ic-patch-to], [data-ic-delete-from]',
      js:       'intercoolerjs/intercooler.min.js',
    },



    smoothscroll: {
      selector: 'smoothscroll',
      js:       'smoothscroll/smoothscroll.min.js',
    },



    aos: {
      selector: '$ [data-aos]',
      callback: 'initAos',
      css:      'aos/aos.css',
      js:       'aos/aos.js',
    },



    typed: {
      selector: 'typing',
      callback: 'initTyped',
      js:       'typed.js/typed.min.js',
    },





    // ======================================================================
    // Misc
    //


    vuejs: {
      selector: 'vuejs',
      js:       'vuejs/vue.min.js',
    },


    reactjs: {
      selector: 'reactjs',
      js:       [
                  'reactjs/react.min.js',
                  'reactjs/react-dom.min.js',
                ],
    },


  }



}(jQuery);
