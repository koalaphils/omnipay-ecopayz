(function($){

    $.fn.ipList = function(options, params){
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
            'ajax': {
                'url': settings.url.list,
                'type': 'POST',
                'data': function(d){
                    d.datatable = 1;
                    d.route = 1;
                },
            },
            'columns': settings.columns,
            'columnDefs': settings.columnDefs,
            'bLengthChange': false,
            'searching' : false,
            'ordering' : false
        }).api();

        $(elem).on('refresh', { "dataTable": dataTable}, function(event){
            event.data.dataTable.ajax.reload();
        });
    }

    function _defaults() {
        return {
            'columns': [
                {
                    'data': 'clientIp',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        return data;
                    }
                },
                {
                    'data': 'timestamp',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        if (data !== null) {
                            return moment(data.date).format('MMM D, YYYY h:mm A');
                        }
                        
                        return '';
                    }
                }
            ]
        };
    }
})(jQuery);
var loginSubscription = function(session) {
    session.subscribe('ms.topic.user_logged_in.' + channelId, function(args) {       
        $('#customerIpHistoryList').trigger('refresh');
    });
};