/**
 * Bootstrap Table Italian translation
 * Author: Davide Renzi<davide.renzi@gmail.com>
 * Author: Davide Borsatto <davide.borsatto@gmail.com>
 * Author: Alessio Felicioni <alessio.felicioni@gmail.com>
 */
(function ($) {
    'use strict';

    $.fn.bootstrapTable.locales['it-IT'] = {
        formatLoadingMessage: function () {
            return 'Caricamento in corso...';
        },
        formatRecordsPerPage: function (pageNumber) {
            return pageNumber + ' elementi per pagina';
        },
        formatShowingRows: function (pageFrom, pageTo, totalRows) {
            return 'Elementi mostrati da ' + pageFrom + ' a ' + pageTo + ' (Numero totali di elementi ' + totalRows + ')';
        },
        formatSearch: function () {
            return 'Cerca';
        },
        formatNoMatches: function () {
            return 'Nessun elemento trovato';
        },
        formatPaginationSwitch: function () {
            return 'Nascondi/Mostra paginazione';
        },
        formatRefresh: function () {
            return 'Aggiorna';
        },
        formatToggle: function () {
            return 'Attiva/Disattiva';
        },
        formatColumns: function () {
            return 'Colonne';
        },
        formatAllRows: function () {
            return 'Tutto';
        },
        formatExport: function () {
            return 'Esporta dati';
        },
        formatClearFilters: function () {
            return 'Pulisci filtri';
        }
        
    };

    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['it-IT']);

})(jQuery);
