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
        $(table).on('click', 'tr a.achnor-status', function(e) {
            settings.rowOnclick(e, dataTable);
        });
        $(elem).on('refresh', { "dataTable": dataTable}, function(event){
            event.data.dataTable.ajax.reload();
        });
        
    }
    
    function _defaults() {
        return {
            'columns': [
                {
                    'data': 'product.id',
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        return "<a href='"+full.routes.update+"'>"+data+"</a>";
                    }
                },
                { 
                    'data': 'product.code', 
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        return "<a data-toggle='modal' data-target='#update-modal'>"+data+"</a>";
                    }
                },
                { 'data': 'product.name', 'defaultContent': '' },
                {
                    'data': 'product.isActive', 
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        if(data == '1')
                            return "Enabled";
                        return "Suspend"
                    } 
                },
                {
                    'data': 'product',
                    'render': function(data, text, full) {
                        var suspend = "<a href='javascript:void(0);' action='suspend' class='achnor-status btn btn-danger waves-effect waves-light btn-sm' data-toggle='tooltip' data-placement='bottom' title='Suspend'>Suspend</a>";
                        var activate = "<a href='javascript:void(0);' action='activate' class='achnor-status btn btn-success waves-effect waves-light btn-sm' data-toggle='tooltip' data-placement='bottom' title='Enable'>Enable</a>";
                        var action = "<a href='" + full.routes.update + "' class='table-action-btn' data-toggle='tooltip' data-placement='bottom' title='Edit'><i class='md-edit'></i></a>";
                        // if (data.isActive == '1') {
                        //     action = suspend + action;
                        // } else {
                        //     action = activate + action;
                        // }
                        if (data.isActive == '1') {
                            action = suspend;
                        } else {
                            action = activate;
                        }
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