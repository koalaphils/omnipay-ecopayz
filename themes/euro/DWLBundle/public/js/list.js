(function ($, window, document, List) {
    
    var pluginName = "dwlItems";
    
    var ItemList = List;
    ItemList.prototype._defaults = {
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
                        return '<i class="fa fa-spinner fa-spin"></i>';
                    }
                    return "N/A";
                }
            },
            {
                'data': '',
                'defaultContent': '',
                'name': 'action',
                'responsivePriority': 1,
                'render': function (data, type, full) {
                    if (full.id !== null) {
                        return '<a href="" class="table-action-btn btn-edit" data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="md md-edit"></i></a>';
                    }
                    return '';
                }
            }
        ],
        'dt': {
            'dom': 'Clfrtip',
            'colVis': {
                "buttonText": "<i class='fa fa-columns'></i>",
                "exclude": [0, 7]
            },
            'processing': true,
            'serverSide': false,
            'ajax': {
                'dataSrc': function (data) {
                    return data.items;
                }
            },
            'footerCallback': function (tfoot, data, start, end, display) {
                var api = this.api();
                var record = 0;
                var turnover = new Decimal(0);
                var gross = new Decimal(0);
                var winLoss = new Decimal(0);
                var commission = new Decimal(0);
                var amount = new Decimal(0);
                
                for (var i in data) {
                    record++;
                    turnover = turnover.plus(data[i]['turnover']);
                    gross = gross.plus(data[i]['gross']);
                    winLoss = winLoss.plus(data[i]['winLoss']);
                    commission = commission.plus(data[i]['commission']);
                    amount = amount.plus(data[i]['amount']);
                }
                
                $(api.column(1).footer()).html(renderColumn('turnover', turnover.toFixed(2), {}));
                $(api.column(2).footer()).html(renderColumn('gross', gross.toFixed(2), {}));
                $(api.column(3).footer()).html(renderColumn('winLoss', winLoss.toFixed(2), {}));
                $(api.column(4).footer()).html(renderColumn('commission', commission.toFixed(2), {}));
                $(api.column(5).footer()).html(renderColumn('amount', amount.toFixed(2), {}));
            }
        },
        'editClick': function (e) {}
    };
    ItemList.prototype.tableEvents = function () {
        this.table.on('draw.dt', {'list': this, 'dTable': this.dt, 'settings': this.options}, function(e, s) {
            var ids = [];
            var rows = $(e.currentTarget).find('tbody tr');
            rows.each(function (i, val) {
                var data = e.data.dTable.row(this).data();
                if (data.id !== null && typeof data.cbalance == 'undefined') {
                    ids.push(data.id);
                }
                $(this).find('.btn-edit').click({'list': e.data.list, 'item': data, 'row': this}, e.data.settings.editClick);
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
    };
    
    ItemList.prototype.setItem = function (row, item) {
        this.dt.row(row).data(item).draw();
    };
    
    function renderColumn(info, data, errors) {
        var render = data;
        if (errors[info]) {
            render = "<i class='fa fa-exclamation-circle has-tooltip text-danger' data-title='" + errors[info] + "' data-placement='auto'></i> " + render;
        }
        return render;
    }
    
    $.fn[pluginName] = function (options)
    {
        var args = arguments;
        if (options === undefined || typeof options === 'object') {
            return this.each(function () {
                if(!$.data(this, 'plugin_' + pluginName)) {
                    $.data(this, 'plugin_' + pluginName, new ItemList(this, options));
                }
            });
        } else if (typeof options === 'string' && options[0] !== '_' && options !== 'init') {
            if ($.inArray(options, $.fn[pluginName].getters) != -1) {
                var instance = $.data(this[0], 'plugin_' + pluginName);
                return instance[options].apply(instance, Array.prototype.slice.call(args, 1));
            } else if ($.inArray(options, $.fn[pluginName].setters) != -1) {
                var instance = $.data(this[0], 'plugin_' + pluginName);
                return instance[options].apply(instance, Array.prototype.slice.call(args, 1));
            } else {
                return this.each(function() {
                    var instance = $.data(this, 'plugin_' + pluginName);

                    if (instance instanceof ItemList && $.fn[pluginName].methods[options] && typeof instance[$.fn[pluginName].methods[options]] === 'function') {
                        instance[$.fn[pluginName].methods[options]].apply(instance, Array.prototype.slice.call(args, 1));
                    }
                });
            }
        }
    }
    
    $.fn[pluginName].getters = [];
    $.fn[pluginName].setters = ['setItem'];
    
    $.fn[pluginName].methods = {};
    
    $.fn[pluginName].defaults = {
    };
}) (jQuery, window, document, List);