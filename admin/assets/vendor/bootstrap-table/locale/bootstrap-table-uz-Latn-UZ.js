/**
 * Bootstrap Table Uzbek translation
 * Author: Nabijon Masharipov <mnabijonz@gmail.com>
 */
(function ($) {
    'use strict';

    $.fn.bootstrapTable.locales['uz-Latn-UZ'] = {
        formatLoadingMessage: function () {
            return 'Yuklanyapti, iltimos kuting...';
        },
        formatRecordsPerPage: function (pageNumber) {
            return pageNumber + ' qator har sahifada';
        },
        formatShowingRows: function (pageFrom, pageTo, totalRows) {
            return 'Ko\'rsatypati ' + pageFrom + ' dan ' + pageTo + ' gacha ' + totalRows + ' qatorlarni';
        },
        formatSearch: function () {
            return 'Qidirish';
        },
        formatNoMatches: function () {
            return 'Hech narsa topilmadi';
        },
        formatPaginationSwitch: function () {
            return 'Sahifalashni yashirish/ko\'rsatish';
        },
        formatRefresh: function () {
            return 'Yangilash';
        },
        formatToggle: function () {
            return 'Ko\'rinish';
        },
        formatColumns: function () {
            return 'Ustunlar';
        },
        formatAllRows: function () {
            return 'Hammasi';
        },
        formatExport: function () {
            return 'Eksport';
        },
        formatClearFilters: function () {
            return 'Filtrlarni tozalash';
        }
    };

    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['uz-Latn-UZ']);

})(jQuery);
