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
        
        var dataTable = $(table).dataTable($.extend(true,{
            'processing': true,
            'serverSide': true,
            'ajax': {
                'url': settings.url.list,
                'type': 'POST',
                'data': function(d){
                    d.datatable = 1;
                }
            },
            'columns': settings.columns,
            'columnsDefs': settings.columnsDefs
        }, settings.dataTable)).api();
        
        $(elem).on('refresh',{"dataTable": dataTable}, function(event){
            event.data.dataTable.ajax.reload();
        });
        
    }
    
    function _defaults() {
        return {
            'columns': [
                {
                    'data': 'code',
                    'defaultContent': ''
                },
                {
                    'data': 'name',
                    'defaultContent': ''
                },
                {
                    'data': '@link',
                    'defaultContent': '',
                    'render': function (data) {
                        return "<a href='" + data + "' class='table-action-btn' data-toggle='tooltip' data-placement='bottom' title='Edit'><i class='md md-edit'></i></a>";
                    }
                }
            ],
            'columnsDefs': []
        };
    }
    
})(jQuery);