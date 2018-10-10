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
            'columnsDefs': settings.columnsDefs
        }).api();
    }
    
    function _defaults() {
        return {
            'columns': [
                {
                    'data': 'notice.id',
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        return "<a href='"+full.routes.update+"'>"+data+"</a>";
                    }
                },
                { 'data': 'notice.title', 'defaultContent': '' },
                { 'data': 'notice.description', 'defaultContent': '' },
                { 'data': 'notice.type', 'defaultContent': '' },
                { 'data': 'notice.startAt', 'defaultContent': '' },
                { 'data': 'notice.endAt', 'defaultContent': '' }
            ],
            'columnsDefs': []
        };
    }
    
})(jQuery);