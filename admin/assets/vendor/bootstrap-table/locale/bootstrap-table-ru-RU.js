/**
 * Bootstrap Table Russian translation
 * Author: Dunaevsky Maxim <dunmaksim@yandex.ru>
 */
(function ($) {
    'use strict';
    $.fn.bootstrapTable.locales['ru-RU'] = {
        formatLoadingMessage: function () {
            return 'Пожалуйста, подождите, идёт загрузка...';
        },
        formatRecordsPerPage: function (pageNumber) {
            return pageNumber + ' записей на страницу';
        },
        formatShowingRows: function (pageFrom, pageTo, totalRows) {
            return 'Записи с ' + pageFrom + ' по ' + pageTo + ' из ' + totalRows;
        },
        formatSearch: function () {
            return 'Поиск';
        },
        formatNoMatches: function () {
            return 'Ничего не найдено';
        },
        formatRefresh: function () {
            return 'Обновить';
        },
        formatToggle: function () {
            return 'Переключить';
        },
        formatColumns: function () {
            return 'Колонки';
        },
        formatClearFilters: function () {
            return 'Очистить фильтры';
        },
        formatMultipleSort: function () {
            return 'Множественная сортировка';
        },
        formatAddLevel: function () {
            return 'Добавить уровень';
        },
        formatDeleteLevel: function () {
            return 'Удалить уровень';
        },
        formatColumn: function () {
            return 'Колонка';
        },
        formatOrder: function () {
            return 'Порядок';
        },
        formatSortBy: function () {
            return 'Сортировать по';
        },
        formatThenBy: function () {
            return 'затем по';
        },
        formatSort: function () {
            return 'Сортировать';
        },
        formatCancel: function () {
            return 'Отмена';
        },
        formatDuplicateAlertTitle: function () {
            return 'Дублирование колонок!';
        },
        formatDuplicateAlertDescription: function () {
            return 'Удалите, пожалуйста, дублирующую колонку, или замените ее на другую.';
        }
    };

    $.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['ru-RU']);

})(jQuery);
