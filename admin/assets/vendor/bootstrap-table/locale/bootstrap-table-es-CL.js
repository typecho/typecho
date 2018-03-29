/**
 * Traducción de librería Bootstrap Table a Español (Chile)
 * @author Brian Álvarez Azócar
 * email brianalvarezazocar@gmail.com
 */
(function($) {
  'use strict';

  $.fn.bootstrapTable.locales['es-CL'] = {
    formatLoadingMessage: function() {
      return 'Cargando, espere por favor...';
    },
    formatRecordsPerPage: function(pageNumber) {
      return pageNumber + ' filas por p\u00E1gina';
    },
    formatShowingRows: function(pageFrom, pageTo, totalRows) {
      return 'Mostrando ' + pageFrom + ' a ' + pageTo + ' de ' + totalRows + ' filas';
    },
    formatSearch: function() {
      return 'Buscar';
    },
    formatNoMatches: function() {
      return 'No se encontraron registros';
    },
    formatPaginationSwitch: function() {
      return 'Ocultar/Mostrar paginaci\u00F3n';
    },
    formatRefresh: function() {
      return 'Refrescar';
    },
    formatToggle: function() {
      return 'Cambiar';
    },
    formatColumns: function() {
      return 'Columnas';
    },
    formatAllRows: function() {
      return 'Todo';
    }
  };

  $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['es-CL']);

})(jQuery);
