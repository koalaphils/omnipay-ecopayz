(function($){

    $.fn.list = function(options, params){
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
            'ordering': false,
            'ajax': {
                'url': settings.url.list,
                'type': 'POST',
                'data': function(d){
                    d = $.extend(true, d, settings.data);
                    d.datatable = 1;
                    d.route = 1;
                }
            },
            'columns': settings.columns,
            'columnDefs': settings.columnDefs
        }).api();

        $(table).on('click', 'tr a.active-state-action', function(e) {
            settings.rowOnclick(e, dataTable);
        });

        $(elem).on('refresh', { "dataTable": dataTable}, function(event){
            event.data.dataTable.ajax.reload();
        });
    }

    function _defaults() {
        return {
            'data': {},
            'columns': [
                {
                    'data': 'customer.id',
                    'defaultContent': '',
                    'render': function(data, type, full) {
                        return "<a href='"+full.routes.update+"'>"+data+"</a>";
                    }
                },
                {   'data': 'customer',
                    'defaultContent': '',
                    'render': function(data, type, full) {
                            return full.customer.fName + '&nbsp;' + full.customer.lName;
                    }
                },
                { 'data': 'customer.user.username', 'defaultContent': '' },
                { 'data': 'customer.user.email', 'defaultContent': '' },
                { 'data': 'customer.country.name', 'defaultContent': '' },
                { 'data': 'customer.currency.name', 'defaultContent': '' },
                {
                    'data': 'customer.balance',
                    'defaultContent': '',
                    'render': function(data) {
                        return (new Decimal(data)).toFixed(2);
                    }
                },
                {
                    'data': 'customer',
/*                    'render': function(data, text, full) {
                        var action = "<a href='" + full.routes.update + "' class='table-action-btn' data-toggle='tooltip' data-placement='bottom' title='Edit'><i class='md md-edit'></i></a>";
                        var suspend = "<a action='suspend' class='table-action-btn achnor-status' data-toggle='tooltip' data-placement='bottom' title='User is active. Click to suspend this user'><i class='ion-alert-circled'></i></a>";
                        var activate = "<a action='activate' class='table-action-btn achnor-status' data-toggle='tooltip' data-placement='bottom' title='User is suspended. Click to activate this user'><i class='ion-checkmark-circled'></i></a>";

                        if (data.user.isActive === true) {
                            action = suspend + ' ' + action;
                        } else {
                            action = activate + ' ' + action;
                        }

                        return action;
                    }*/

                    'render': function(data, text, full) {
                        var profile = '<button class="btn btn-icon waves-effect waves-light btn-xs btn-primary btn-icn"><i class="glyphicon glyphicon-user"></i></button> ';
                        var suspend = '<button class="btn btn-icon waves-effect waves-light btn-xs btn-purple btn-icn"><i class="glyphicon glyphicon-usd"></i></button> ';
                        var product = '<button class="btn btn-icon waves-effect waves-light btn-warning btn-xs btn-icn"><i class="glyphicon glyphicon-book"></i></button>';
                        var remove = '<button class="btn btn-icon waves-effect waves-light btn-danger btn-xs btn-icn btn-del" data-toggle="modal" data-target="#delete-affiliate"><i class="glyphicon glyphicon-trash"></i></button>';
                         var convert = '<a class="btn btn-icon waves-effect waves-light btn-success btn-xs btn-icn" data-toggle="modal" data-target="#add-affiliate"><i class="glyphicon glyphicon-plus"></i></a>';
                        
                        
                        var actions = profile + suspend + product + remove + convert;

                        return actions;
                    }
                }
            ],
            'columnDefs': [
                { "targets": [ 0 ], "visible": false }
            ]
        };
    }
})(jQuery);