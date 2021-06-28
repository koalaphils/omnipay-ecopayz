(function($){
    
    $.fn.list = function(options, params){
        var settings = options;
        params = params || [];
        return this.each(function(){
            var elem = $(this);
            
            settings = $.extend(true,_defaults(),options);
            return init(elem, settings);
        });
    };
    
    function init(elem, settings) {
        var dataTable = initTable(elem, settings);
        
    }
    
    function initTable(elem, settings) {
        var table = $(elem).find('table');
        var listUrl = settings.url.list;
        
        var dataTable = $(table).dataTable({
            'processing': true,
            'serverSide': true,
            'ajax': {
                'url': settings.url.list,
                'type': 'POST',
                'data': function(d){
                    d.datatable = 1;
                    d.route = 1;
                }
            },
            'columns': settings.columns,
            'columnDefs': settings.columnDefs
        }).api();
        
        $(elem).on('datatable', {"dataTable": dataTable}, function(e) {
            return e.data.dataTable;
        });
        
        $(elem).on('refresh', {"dataTable": dataTable}, function(e) {
            return e.data.dataTable.ajax.reload();
        });
    }
    
    function _defaults() {
        return {
            'columns': [
                {
                    'data': 'bonus.id',
                    'defaultContent': ''
                },
                { 'data': 'bonus.subject', 'defaultContent': '' },
                { 'data': 'bonus.startAt', 'defaultContent': '' },
                { 'data': 'bonus.endAt', 'defaultContent': '' },
                {
                    'data': null,
                    'className': "actions",
                    'orderable' : false,
                    'render': function(data, type, full) {
                        return '<a href="' +full.routes.update+ '" class="btn-edit table-action-btn" data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="md md-edit"></i></a>'
                            + '<a href="' + full.routes.delete + '" class="btn-delete table-action-btn" data-toggle="tooltip" data-placement="bottom" title="Delete"><i class="md md-close"></i></a>';
                    }
                }
            ],
            'columnDefs': [
                { 'targets': [0], 'visible': false  }
            ]
        };
    }
    
})(jQuery);