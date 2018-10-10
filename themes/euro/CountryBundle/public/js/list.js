(function($){
    
    $.fn.list = function(options, params){
        var settings = options;
        params = params || [];
        return this.each(function(){
            var elem = $(this);
            settings = $.extend(true,_defaults(),options);
            init(elem, settings);
        });
    };
    
    function init(elem, settings) {
        initTable(elem, settings);
    }
    
    function initTable(elem, settings) {
        var table = $(elem).find('table');
        var listUrl = settings.url.list;
        
        var dataTable = $(table).dataTable({
            'searching': false,
            'lengthChange': false,
            'processing': true,
            'serverSide': true,
            'ajax': {
                'url': settings.url.list,
                'type': 'POST',
                'data': function (datatableData) {
                    var orders = {};
                    for (var i in datatableData.order) {
                        var order = datatableData.order[i];
                        orders[i] =  {
                            'column': datatableData.columns[order.column].name,
                            'dir': order.dir
                        };
                    }

                    var data = {
                        "length": $(settings.lengthInput).val(),
                        "start": datatableData.start,
                        "datatable": true,
                        "route": true,
                        "search": $(settings.searchInput).val(),
                        "draw": datatableData.draw,
                        "order": orders
                    };

                    return data;
                }
            },
            'columns': settings.columns,
            'columnDefs': settings.columnDefs,
            'order': settings.order,
        }).api();
        
        var timer;
        $(settings.searchInput).keyup(function () {
            clearTimeout(timer);
            var delayInMilliseconds = 500;
            timer = setTimeout(
                function() {
                    dataTable.ajax.reload();
                },
                delayInMilliseconds
            );
        });
        
        $(settings.lengthInput).change(function () {
            dataTable.ajax.reload();
        });
        
        $(elem).on('refresh',{"dataTable": dataTable}, function(event){
            event.data.dataTable.ajax.reload();
        });
        
        $('tr', table).on('click', function (e) {
            $(elem).trigger('row.click', [dataTable, settings]);
        });
        
        $(elem).on('click', 'table tr td a', function (e) {
            $(elem).trigger('link.click', [{'target': this, 'event': e}, dataTable, settings]);
        });
    }
    
    function _defaults() {
        return {
            "order": [[ 1, "asc" ]],
            'columns': [
                {
                    'data': 'country.id',
                    'defaultContent': '',
                    'name': 'country.id',
                    'render': function (data, type, full) {
                        return $("<a href='"+full.routes.update+"'>"+data+"</a>");
                    }
                },
                {
                    'data': 'country.code',
                    'defaultContent': '',
                    'name': 'country.code',
                    'render': function (data, type, full) {
                        return "<a href='"+full.routes.update+"' class='update'>"+data+"</a>";
                    }
                },
                { 'data': 'country.name', 'defaultContent': '', 'name': 'country.name' },
                { 'data': 'country.currency.code', 'defaultContent': '', 'name': 'currency.code' }
            ],
            'columnDefs': [
                {
                    "targets": [ 0 ],
                    "visible": false
                }
            ],
            'filters': []
        };
    }
    
})(jQuery);