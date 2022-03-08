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
                    'data': 'currency.id',
                    'name': 'id',
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        return "<a href='"+full.routes.update+"'>"+data+"</a>";
                    }
                },
                {   'data': 'currency.name', 'defaultContent': '',
                    'render': function(data, type, full) {
                        return "<a data-toggle='modal' data-target='#update-currency'>"+data+"</a>";
                    }
                },
                { 'data': 'currency.code', 'defaultContent': '' }
            ],
            'columnDefs': [
                {
                    targets: [0],
                    visible: false
                }
            ]
        };
    }

})(jQuery);