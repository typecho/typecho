

// =====================
// Table plugins
// =====================
//
+function($){



  provider.initTables = function() {

    provider.initBootstrapTable();

  };




  provider.initBootstrapTable = function() {
    if ( ! $.fn.bootstrapTable ) {
      return;
    }

    jQuery.fn.bootstrapTable.defaults.classes = 'table';

    provider.provide('table', function(){
      $(this).bootstrapTable();
    });


    $('.fixed-table-body').perfectScrollbar();

  };




  provider.initJsGrid = function() {
    if ( ! $.fn.jsGrid ) {
      return;
    }
  };




  provider.initDatatables = function() {
    if ( ! $.fn.DataTable ) {
      return;
    }

    provider.provide('datatables', function(){
      $(this).DataTable();
    });

  };





}(jQuery);
