(function (global, $) {
    if (typeof $ === 'undefined') {
        return;
    }

    function buildContainer(tableId) {
        return $('<div>', {
            class: 'dt-column-search-wrapper mb-3',
            'data-table': tableId
        }).append(
            $('<div>', { class: 'card shadow-sm' }).append(
                $('<div>', { class: 'card-body py-3' }).append(
                    $('<div>', { class: 'row g-2 dt-column-search-row' })
                )
            )
        );
    }

    global.attachDataTableColumnSearch = function (dataTable) {
        if (!dataTable || !dataTable.columns) {
            return;
        }

        var api = dataTable;
        var tableNode = $(api.table().node());
        var tableId = tableNode.attr('id') || 'datatable';

        tableNode.prev('.dt-column-search-wrapper[data-table="' + tableId + '"]').remove();

        var container = buildContainer(tableId);
        var row = container.find('.dt-column-search-row');

        api.columns().every(function () {
            var column = this;
            var header = $(column.header());
            var searchableAttr = header.data('searchable');
            var isSearchable = searchableAttr !== false && !header.hasClass('no-search');

            if (!isSearchable) {
                return;
            }

            var title = header.data('search-label') || header.text().trim();
            var col = $('<div>', { class: 'col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-2' });
            var label = $('<label>', {
                class: 'small text-muted mb-1 d-block',
                text: title
            });
            var input = $('<input>', {
                type: 'text',
                class: 'form-control form-control-sm',
                placeholder: 'Search ' + title,
                'data-column-index': column.index()
            });

            col.append(label).append(input);
            row.append(col);
        });

        if (!row.children().length) {
            return;
        }

        container.insertBefore(tableNode);

        row.on('keyup change clear', 'input', function () {
            var input = $(this);
            var columnIndex = input.data('column-index');
            var value = this.value;
            var column = api.column(columnIndex);

            if (column.search() !== value) {
                column.search(value).draw();
            }
        });
    };
})(window, window.jQuery);
