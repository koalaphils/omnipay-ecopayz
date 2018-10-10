(function($){
    
    $.fn.productList = function(options, params){
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
            'ordering': false,
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
        
    
        $(elem).on('refresh',{"dataTable": dataTable}, function(event){
            event.data.dataTable.ajax.reload();
        });
        
        $(table).on('click', 'tr a', function(event) {
            settings.rowOnclick(event, dataTable);
        });
        
    }
    
    function _defaults() {
        return {
            'columns': [
                {
                    'data': 'customerProduct.id',
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        return data;
                    }
                },
                { 
                    'data': 'customerProduct', 
                    'defaultContent': '' ,
                    'render': function(data, type, full) {
                        var display = '';
                        if (data.product.deletedAt != null) {
                            display = data.userName;
                        } else {
                            display = '<a action="edit" href="#" data-toggle="modal">'+ data.userName +'</a>';
                        }
                        
                        return display;
                    }             
                },
                { 'data': 'customerProduct.product.name', 'defaultContent': '' },
                {
                    'data': 'customerProduct.balance',
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        var baBalance = full.ba_balance ? full.ba_balance : '';
                        var baBalanceDisplay = '';
                        if (baBalance) {
                            if (!isNaN(baBalance)) {
                                var baBalance = baBalance.toFixed(2);
                                baBalanceDisplay = '(BA: ' + baBalance + ')';
                            } else {
                                baBalanceDisplay = '(' + baBalance + ')';
                            }
                        } else {
                            if (full.ba_balance === 0) {
                                baBalanceDisplay = '(BA: ' + full.ba_balance + ')';
                            }
                        }

                        return (new Decimal(data)).toFixed(2) + ' ' + baBalanceDisplay;
                    }
                },
                {
                    'data': 'customerProduct.isActive', 
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        if(data == '1')
                            return "<i class='fa fa-check-circle-o'></i>";
                        return "<i class='fa fa-times-circle-o'></i>"
                    } 
                },
                {
                    'data': 'customerProduct',
                    'render': function(data, text, full) {
                            var suspend = '<a action="suspend" class="btn btn-icon waves-effect waves-light btn-danger btn-xs btn-icn">Suspend</a>';
                            var enable = '<a action="enable" class="btn btn-icon waves-effect waves-light btn-success btn-xs btn-icn">Enable</a>';
                            var edit = "<a action='edit' class='btn btn-icon waves-effect waves-light btn-primary btn-xs btn-icn active-state-action'>Edit</a>";

                            var actions = '';
                            if (data.isActive) {
                                actions = suspend + edit;
                            } else {
                                actions = enable + edit;
                            }
                            
                            if (data.product.deletedAt != null) {
                                actions = 'DELETED'; //this will hide action button when product is deleteds
                            }

                            return actions;
                    }
                }
            ],
            'columnDefs': [
                { 'targets': [0], 'visible': false }
            ],
        };
    }
    
})(jQuery);