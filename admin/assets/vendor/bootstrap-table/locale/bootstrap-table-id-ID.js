/**
 * Bootstrap Table Indonesian translation
 * Author: Andre Gardiner<andre@sirdre.com> 
 */
(function ($) {
    'use strict';

    $.fn.bootstrapTable.locales['id-ID'] = {
        formatLoadingMessage: function () {
            return 'Memuat, mohon tunggu...';
        },
        formatRecordsPerPage: function (pageNumber) {
            return pageNumber + ' baris per halaman';
        },
        formatShowingRows: function (pageFrom, pageTo, totalRows) {
            return 'Menampilkan ' + pageFrom + ' sampai ' + pageTo + ' dari ' + totalRows + ' baris';
        }, 
        formatSearch: function () {
            return 'Pencarian';
        },
        formatNoMatches: function () {
            return 'Tidak ditemukan data yang cocok';
        },
        formatPaginationSwitch: function () {
            return 'Sembunyikan/Tampilkan halaman';
        },
        formatRefresh: function () {
            return 'Muat ulang';
        },
        formatToggle: function () {
            return 'Beralih';
        },
        formatColumns: function () {
            return 'kolom';
        },
        formatAllRows: function () {
            return 'Semua';
        },
        formatExport: function () {
            return 'Ekspor data';
        },
        formatClearFilters: function () {
            return 'Bersihkan filter';
        }
    };

    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['id-ID']);

})(jQuery);
