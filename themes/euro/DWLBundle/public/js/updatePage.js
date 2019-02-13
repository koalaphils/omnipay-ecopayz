$(function () {
    var  totalNotExsistUsername = 0;
    var totalErrorWithUsername = 0;
    var totalRecord = 0;

    function renderColumn(info, data, errors) {
        var render = data;
        if (errors[info]) {
            render = "<i class='fa fa-exclamation-circle has-tooltip text-danger' data-title='" + errors[info] + "' data-placement='auto'></i> " + render;
        }
        return render;
    }

    $('#itemList table').on('xhr.dt', function (e, settings, data, xhr) {
        try {
            totalRecord = data.total.record;
            totalNotExsistUsername = 0;
            totalErrorWithUsername = 0;
            for (var index in data.items) {
                if (typeof data.items[index].errors.username !== 'undefined') {
                    ++totalNotExsistUsername;
                } else if (Object.keys(data.items[index].errors).length > 0) {
                    ++totalErrorWithUsername;
                }
            }
            $('.ztable_length_input').trigger('change');
        } catch (err) {}
    });

    var list = new ZTable('#itemList',{
        'ajax': {
            'url': url.list,
            'dataFilter': function (str) {
                return str;
            },
            'dataSrc': function (data) {
                var items = data.items;

                items.sort(function(a, b) {
                    if (Array.isArray(a.errors) && !Array.isArray(b.errors)) {
                        return 1;
                    }

                    if (Array.isArray(b.errors) && !Array.isArray(a.errors)) {
                        return -1;
                    }

                    return 0;
                });

                return data.items;
            },
            'error': function (xhr, options, message) {
                if ((typeof options === 'string') && options === 'parsererror') {
                    this.globalAjaxError = false;
                    list.dataTable.api().ajax.reload();
                }
            }
        },
        'autoinit': false,
        'featuresDom': "b",
        'features': {
            'btnExportCsv': {
                'type': 'button',
                'label': 'Export to CSV',
                'symbol': 'b',
                'attrs': {
                    'type': 'button',
                    'class': 'btn btn-sm btn-inverse'
                },
                'initialized': function (feature) {
                },
                'rendered': function (feature) {
                    $(feature.input).click(function () {
                        window.location = url.exportDWL;
                    });
                }
            }
        },
        'columns': [
            {
                'data': 'username',
                'defaultContent': '',
                'className': 'uname',
                'name': 'username',
                'render': function (data, type, full) {
                    return renderColumn('username', data, full.errors);
                }
            },
            {
                'data': 'turnover',
                'name': 'turnover',
                'defaultContent': '',
                'render': function (data, type, full) {
                    return renderColumn('turnover', (new Decimal(data)).toFixed(2), full.errors);
                }
            },
            {
                'data': 'gross',
                'name': 'gross',
                'defaultContent': '',
                'render': function (data, type, full) {
                    return renderColumn('gross', (new Decimal(data)).toFixed(2), full.errors);
                }
            },
            {
                'data': 'winLoss',
                'name': 'winloss',
                'defaultContent': '',
                'render': function (data, type, full) {
                    return renderColumn('winLoss', (new Decimal(data)).toFixed(2), full.errors);
                }
            },
            {
                'data': 'commission',
                'name': 'commission',
                'defaultContent': '',
                'render': function (data, type, full) {
                    return renderColumn('commission', (new Decimal(data)).toFixed(2), full.errors);
                }
            },
            {
                'data': 'amount',
                'name': 'amount',
                'defaultContent': '',
                'render': function (data, type, full) {
                    if (typeof full.brokerage !== 'undefined'
                        && typeof full.brokerage.winLoss !== 'undefined'
                        && full.brokerage.winLoss !== null
                    ) {
                        return renderColumn('amount', (new Decimal(full.brokerage.winLoss)).toFixed(2), full.errors);
                    }

                    return renderColumn('amount', (new Decimal(data)).toFixed(2), full.errors);
                }
            },
            {
                'data': '',
                'defaultContent': 0,
                'name': 'balance',
                'render': function (data, type, full) {
                    if (full.id !== null) {
                        if (typeof full.customer !== 'undefined' && typeof full.customer.balance !== 'undefined' && full.customer.balance !== null) {
                            return (new Decimal(full.customer.balance)).toFixed(2)
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
        'columnDefs': [
            { "searchable": false, "targets": [1,2,3,4,5,6,7] }
        ],
        'dt': {
            'serverSide': false,
            'processing': true,
            'ordering': true,
            'searching': true,
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

                $(api.column(1).footer()).html(turnover.toFixed(2));
                $(api.column(2).footer()).html(gross.toFixed(2));
                $(api.column(3).footer()).html(winLoss.toFixed(2));
                $(api.column(4).footer()).html(commission.toFixed(2));
                $(api.column(5).footer()).html(amount.toFixed(2));
            }
        },
        'initialized': function (ztable) {
            ztable.checkBalanceAjax = null;

            $('#itemList table').on('draw.dt', {'list': ztable}, function(e, s) {
                var ids = [];
                var rows = $(e.currentTarget).find('tbody tr');
                rows.each(function (i, val) {
                    var data = e.data.list.dataTable.api().row(this).data();
                    if (typeof data !== 'undefined' && typeof data.id !== 'undefined' && data.id !== null) {
                        if (!(typeof data.customer !== 'undefined' && typeof data.customer.balance !== 'undefined' && data.customer.balance !== null)) {
                            ids.push(data.id);
                        }
                        $(this).find('.btn-edit').click({'list': e.data.list, 'item': data, 'row': this}, function (e) {
                            $('#dwlItemModal').data('list', e.data.list);
                            $('#dwlItemModal').data('item', e.data.item);
                            $('#dwlItemModal').data('row', e.data.row);
                            $('#dwlItemModal').modal('show');
                            e.preventDefault();
                        });
                    }
                });
                if (ids.length > 0) {
                    if (e.data.list.checkBalanceAjax !== null) {
                        e.data.list.checkBalanceAjax.abort();
                        e.data.list.checkBalanceAjax = null;
                    }
                    e.data.list.checkBalanceAjax = $.ajax({
                        'url': url.cproducts,
                        'type': "POST",
                        'dataType': 'json',
                        'data': {'ids': ids},
                        'rows': rows,
                        'dataTable': e.data.list.dataTable.api(),
                        'success': function (cproducts) {
                            var _cproducts = {};
                            var xhr = this;
                            $.each(cproducts, function () {
                                _cproducts[this.id] = this;
                            });
                            this.rows.each(function () {
                                var d = xhr.dataTable.row(this).data();
                                if (typeof _cproducts[d.id] != 'undefined' ) {
                                   //d.cbalance = _cproducts[d.id].balance;
                                    d.customer = {
                                        'balance': _cproducts[d.id].balance
                                    };
                                    xhr.dataTable.row(this).data(d);
                                }
                            });
                            xhr.dataTable.draw(false);
                        }
                    });
                }
            });

            $('#btnSubmit').click(function() {
                var hasSubmited = false;
                for (var i in versions) {
                    if (versions[i] == 6) {
                        hasSubmited = true;
                        break;
                    }
                }
                if (totalNotExsistUsername > 0 && totalErrorWithUsername === 0) {
                    confirm2("There are username that are not exists."
                        + " Do you realy want to submit? Upon Submiting, username with error will be ignored.", "Confirm", {
                            'confirmButtonText': 'Yes',
                            'cancelButtonText': 'No'
                        },
                        function (isConfirm) {
                            if (isConfirm) {
                                window.location = url.submitDWL + '?submit=force';
                            }
                        }
                    );
                } else if (totalErrorWithUsername > 0) {
                    alert2("There are still error in records that username was exists.", "Unable to submit", {
                        'type': 'error',
                        'confirmButtonClass': 'btn-danger btn-md',
                    });
                } else if (hasSubmited) {
                    confirm2("There are exisiting version that was submited."
                        + " Do you realy want to continue?", "Confirm", {
                            'confirmButtonText': 'Yes',
                            'cancelButtonText': 'No'
                        },
                        function (isConfirm) {
                            if (isConfirm) {
                                window.location = url.submitDWL;
                            }
                        }
                    );
                } else {
                    window.location = url.submitDWL;
                }
            });
        }
    });
    $('#itemList').data('ztable', list);

});