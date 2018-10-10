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
                    d.route = 1;
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
                    'data': 'transaction.number',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        return "<a href='"+full.routes.update+"' target='_blank'>"+data+"</a>";
                    }
                },
                {
                    'data': 'transaction.date',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        return moment(data.date).format('MMM D, YYYY h:mm A');
                    }
                },
                {
                    'data': 'transaction.customer',
                    'defaultContent': '',
                    'render': function (data) {
                        return data.fName + " " + data.lName;
                    }
                },
                { 'data': 'transaction.currency.code', 'defaultContent': '' },
                {
                    'data': 'transaction.amount',
                    'defaultContent': '',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2);
                    }
                },
                {
                    'data': 'transaction.status', 
                    'defaultContent': ''
                },
                {
                    'data': 'transaction.type', 
                    'defaultContent': ''
                }
            ],
            'columnsDefs': []
        };
    }
    
})(jQuery);