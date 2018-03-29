/**
 * Bootstrap Table Ukrainian translation
 * Author: Vitaliy Timchenko <vitaliy.timchenko@gmail.com>
 */
 (function ($) {
    'use strict';
    
    $.fn.bootstrapTable.locales['uk-UA'] = {
        formatLoadingMessage: function () {
            return 'Завантаження, будь ласка, зачекайте...';
        },
        formatRecordsPerPage: function (pageNumber) {
            return pageNumber + ' записів на сторінку';
        },
        formatShowingRows: function (pageFrom, pageTo, totalRows) {
            return 'Показано з ' + pageFrom + ' по ' + pageTo + '. Всього: ' + totalRows;
        },
        formatSearch: function () {
            return 'Пошук';
        },
        formatNoMatches: function () {
            return 'Не знайдено жодного запису';
        },
        formatRefresh: function () {
            return 'Оновити';
        },
        formatToggle: function () {
            return 'Змінити';
        },
        formatColumns: function () {
            return 'Стовпці';
        },
        formatClearFilters: function () {
            return 'Очистити фільтри';
        },
        formatMultipleSort: function () {
            return 'Сортування за кількома стовпцями';
        },
        formatAddLevel: function () {
            return 'Додати рівень';
        },
        formatDeleteLevel: function () {
            return 'Видалити рівень';
        },
        formatColumn: function () {
            return 'Стовпець';
        },
        formatOrder: function () {
            return 'Порядок';
        },
        formatSortBy: function () {
            return 'Сортувати за';
        },
        formatThenBy: function () {
            return 'потім за';
        },
        formatSort: function () {
            return 'Сортувати';
        },
        formatCancel: function () {
            return 'Скасувати';
        },
        formatDuplicateAlertTitle: function () {
            return 'Дублювання стовпців!';
        },
        formatDuplicateAlertDescription: function () {
            return 'Видаліть, будь ласка, дублюючий стовпець, або замініть його на інший.';
        }
    };

    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['uk-UA']);

})(jQuery);
