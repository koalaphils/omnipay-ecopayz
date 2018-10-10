(function($){
    
    $.fn.dwlitems = function(options, params){
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
        var allData = {'items': [], 'total': {'turnover': 0, 'grossCommission': 0, 'memberAmount': 0, 'memberCommission': 0, 'memberWinLoss': 0}};
        var dataTable = $(table).dataTable($.extend(true,{
            "dom": 'Clfrtip',
            "colVis": {
                "buttonText": "<i class='fa fa-columns'></i>",
                "exclude": [0]
            },
            'processing': true,
            'ajax': {
                'url': settings.url.list,
                'type': "POST",
                'dataSrc': function (data) {
                    allData = data;
                    return data.items;
                }
            },
            'columns': settings.columns,
            'columnsDefs': settings.columnsDefs,
            'footerCallback': function (tfoot, data, start, end, display) {
                var api = this.api();
                $(api.column(1).footer()).html(renderColumn('turnover', (new Decimal(allData.total.turnover)).toFixed(2), {}));
                $(api.column(2).footer()).html(renderColumn('gross', (new Decimal(allData.total.grossCommission)).toFixed(2), {}));
                $(api.column(3).footer()).html(renderColumn('winLoss', (new Decimal(allData.total.memberWinLoss)).toFixed(2), {}));
                $(api.column(4).footer()).html(renderColumn('commission', (new Decimal(allData.total.memberCommission)).toFixed(2), {}));
                $(api.column(5).footer()).html(renderColumn('amount', (new Decimal(allData.total.memberAmount)).toFixed(2), {}));
            }
        }, settings.dataTable)).api();
        
        $(table).on('draw.dt', {'dTable': dataTable, 'settings': settings}, function (e, s) {
            var ids = [];
            var rows = $(e.currentTarget).find('tbody tr');
            rows.each(function (i, val) {
                var data = e.data.dTable.row(this).data();
                if (data.id !== null && typeof data.cbalance == 'undefined') {
                    ids.push(data.id);
                }
            });
            if (ids.length > 0) {
                $.ajax({
                    'url': e.data.settings.url.cproducts,
                    'type': "POST",
                    'dataType': 'json',
                    'data': {'ids': ids},
                    'rows': rows,
                    'dTable': e.data.dTable,
                    'success': function (cproducts) {
                        var _cproducts = {};
                        var xhr = this;
                        $.each(cproducts, function () {
                            _cproducts[this.id] = this;
                        });
                        this.rows.each(function () {
                            var d = xhr.dTable.row(this).data();
                            if (typeof _cproducts[d.id] != 'undefined' ) {
                                d.cbalance = _cproducts[d.id].balance;
                                xhr.dTable.row(this).data(d);
                            }
                        });
                        xhr.dTable.draw();
                    }
                });
            }
        });
    }
    
    function renderColumn(info, data, errors) {
        var render = data;
        if (errors[info]) {
            render = "<i class='fa fa-exclamation-circle has-tooltip text-danger' data-title='" + errors[info] + "' data-placement='auto'></i> " + render;
        }
        return render;
    }
    
    function _defaults() {
        return {
            'columns': [
                {
                    'data': 'username',
                    'defaultContent': '',
                    'className': 'uname',
                    'render': function (data, type, full) {
                        return renderColumn('username', data, full.errors);
                    }
                },
                {
                    'data': 'turnover',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        return renderColumn('turnover', (new Decimal(data)).toFixed(2), full.errors);
                    }
                },
                {
                    'data': 'gross',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        return renderColumn('gross', (new Decimal(data)).toFixed(2), full.errors);
                    }
                },
                {
                    'data': 'winLoss',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        return renderColumn('winLoss', (new Decimal(data)).toFixed(2), full.errors);
                    }
                },
                {
                    'data': 'commission',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        return renderColumn('commission', (new Decimal(data)).toFixed(2), full.errors);
                    }
                },
                {
                    'data': 'amount',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        return renderColumn('amount', (new Decimal(data)).toFixed(2), full.errors);
                    }
                },
                {
                    'data': '',
                    'defaultContent': 0,
                    'render': function (data, type, full) {
                        if (full.id !== null) {
                            if (full.cbalance) {
                                return (new Decimal(full.cbalance)).toFixed(2)
                            }
                            return "<i class='fa fa-spinner fa-spin'></i>";
                        }
                        return "N/A";
                    }
                },
                {
                    'data': '',
                    'defaultContent': '',
                    'render': function (data, type, full) {
                        
                    }
                }
            ],
            'columnsDefs': []
        };
    }
    
})(jQuery);