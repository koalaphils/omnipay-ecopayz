(function($){
    
    $.fn.report = function(options, params){
        var settings = options;
        params = params || [];
        return this.each(function(){
            var elem = $(this);
            if(settings === 'show') {
                $(elem).trigger('report:show');
            } else if(settings === 'hide'){
                $(elem).trigger('report:hide');
            } else if(settings === 'reset'){
                $(elem).trigger('report:reset');
            } else if(settings === 'filter'){
                if(false === $(elem).data('init')){
                    $(elem).data('dateFrom',params[0]);
                    $(elem).data('dateTo',params[1]);
                    $(elem).trigger('report:init');
                }
                else $(elem).trigger('report:filter',params);
            } else {
                settings = $.extend(true,_defaults(),options);
                init(elem, settings);
            }
        });
    };
    
    function _defaults(){
        return {
            'columns':[],
            'columnsDefs': [],
            'fnFooterCallback':function(nFoot, aData, iStart, iEnd, aiDisplay){},
            'paginate': false,
            'serverSide': false
        };
    }
    
    function init(elem, settings){
        $(elem).data('settings',settings);
        $(elem).data('init',false);
        $(elem).data('dateFrom',settings.defaultFrom);
        $(elem).data('dateTo',settings.defaultTo);
        
        $(elem).on('report:hide',function(){
            $(this).addClass('hide');
        });
        
        $(elem).on('report:show',function(){
            $(this).removeClass('hide');
        });
        
        $(elem).on('report:reset',settings,function(event){
            $(this).trigger('report:filter',[event.data.defaultFrom, event.data.defaultTo]);
        });
        
        $(elem).on('report:init',settings,initTable);
        
    }
    
    function initTable(event,params){
        var elem = this;
        var settings = event.data;
        
        var listUrl = $(elem).data('list-url');
        var table = $(elem).find('table');
        
        var dataTable = $(table).dataTable({
            "ordering": false,
            "bPaginate": settings.paginate,
            "info": false,
            "searching": false,
            "ajaxSource": listUrl,
            "processing":true,
            "serverSide": settings.serverSide,
            "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
                aoData.push({"name": "datatable", "value": "1"});
                aoData.push({"name": "dateFrom", "value": $(elem).data('dateFrom')});
                aoData.push({"name": "dateTo", "value": $(elem).data('dateTo')});
                oSettings.jqXHR = $.ajax({
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "success": fnCallback,
                    "error": function (resp) {
                        ajaxAlert.handleFailure(resp);
                    }
                });
            },
            "fnFooterCallback": settings.fnFooterCallback,
            "columns": settings.columns,
            "columnDefs": settings.columnDefs
        }).api();
        
        $(elem).data('init',true);
        
        $(elem).on('report:filter',{'dataTable':dataTable},function(event, dateFrom, dateTo){
            $(this).data('dateFrom',dateFrom);
            $(this).data('dateTo',dateTo);
            event.data.dataTable.ajax.reload();
        });
    }
})(jQuery);