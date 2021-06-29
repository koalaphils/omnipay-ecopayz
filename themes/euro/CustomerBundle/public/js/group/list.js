(function($){
    
    $.fn.list = function(options, params){
        var settings = options;
        params = params || [];
        return this.each(function(){
            var elem = $(this);
            settings = $.extend(true,_defaults(),options);
            if(options.columns) settings.columns = options.columns;
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
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'ajax': {
                'url': settings.url.list,
                'type': 'POST',
                'data': function(d){
                    d = $.extend(true, d, settings.data);
                    d.search = d.search.value;
                    d.datatable = 1;
                },
                'dataFilter': function (str) {
                    var json = $.parseJSON( str );
                    return JSON.stringify({
                        'data': json.data,
                        'recordsFiltered': json.filtered,
                        'recordsTotal': json.total
                    });
                }
            },
            'columns': settings.columns,
            'columnDefs': settings.columnDefs
        }).api();
    }
    
    function _defaults() {
        return {
            'data': {},
            'columns': [
                {
                    'data': 'id',
                    'defaultContent': ''
                },
                {   'data': 'name', 
                    'defaultContent': ''
                },
                {
                    'data': '',
                    'render': function(data, text, full) {
                        var action = "<a href='" + full._ref + "' class='table-action-btn' data-toggle='tooltip' data-placement='bottom' title='Edit'><i class='md md-edit'></i></a>";
                        return action;
                    }
                }
            ],
            'columnDefs': [
                { "targets": [ 0 ], "visible": false }
            ]
        };
    }
    
})(jQuery);