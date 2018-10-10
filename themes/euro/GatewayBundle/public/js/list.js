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
            'searching': false,
            'lengthChange': false,
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
    }
    
    function _defaults() {
        return {
            'columns': [
                {
                    'data': 'gateway.id',
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        return "<a href='"+full.routes.update+"'>"+data+"</a>";
                    }
                },
                {   'data': 'gateway.name', 
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        return "<a href='"+full.routes.update+"'>"+data+"</a>";
                    }
                },
                { 'data': 'gateway.paymentOption', 'defaultContent': '' },
                { 'data': 'gateway.currency.name', 'defaultContent': '' },
                {
                    'data': 'gateway.balance',
                    'defaultContent': '',
                    'render': function(data) {
                        return (new Decimal(data)).toFixed(2);
                    }
                },
                {
                    'data': 'gateway.isActive', 
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        if(data == '1')
                            return "Enabled";
                        return "Suspended"
                    } 
                },
                {
                    'data': 'gateway.isActive', 
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        if(data == '1')
                            return "<button type='button' class='btn btn-danger waves-effect waves-light btn-xs'>Suspend</button>";
                        return "<button type='button' class='btn btn-success waves-effect waves-light btn-xs'>Enable</button>"
                    } 
                }
            ],
            'columnDefs': [{
                targets: [0],
                visible: false
            }]
        };
    }
    
})(jQuery);