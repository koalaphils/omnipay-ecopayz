class GatewayReport
{
    constructor(elem, currency, reportUrl, exportUrl) {
        this.currency = currency;
        this.reportUrl = reportUrl;
        this.exportUrl = exportUrl;
        this.elem = elem;
        this.from = '';
        this.to = '';
        this.init();
    }

    init()
    {
        var report = this;

        this.table = new ZTable(this.elem, {
            'dom': "<'row m-b-10'<'col-md-12'<'pull-right'C>F>><'row form-inline'<'col-md-6 col-sm-6 col-xs-6'><'col-md-6 col-sm-6 col-xs-6 text-right's>>t",
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
                            var query = {
                                'filters': {
                                    'from': report.from,
                                    'to': report.to,
                                    'search' : $(report.elem).find('.ztable_search_input').val(),
                                }
                            }
                            window.open(report.exportUrl + '?' + $.param(query), '_blank');
                        });
                    }
                }
            },
            'ajax': {
                'url': this.reportUrl,
                'type': 'GET',
                'dataFilter': function (str) {
                    return str;
                },
                'data': function (data) {
                    var filters = {};
                    if (report.from !== '') {
                        filters.from = report.from;
                    }
                    if (report.to !== '') {
                        filters.to = report.to;
                    }

                    filters.search = $(report.elem).find('.ztable_search_input').val();
                    data.filters = filters;

                    return data;
                },
                'dataSrc': ''
            },
            'columns': [
                {
                    'data': 'gateway_name',
                    'render': function (data, text, full) {
                        return '<a href="' + full.link + '" target="_blank">' + data + '</a>';
                    }
                },
                {
                    'data': 'num_deposits'
                },
                {
                    'data': 'num_withdraws'
                },
                {
                    'data': 'sum_deposits',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'sum_withdraws',
                    'render': function (data) {
                        return (new Decimal(data)).times(-1).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'sum_company_fees',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'sum_customer_fees',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                }
            ],
            // 'autoloadTable': false,
            'autoinit': false,
            'dt': {
                'processing': true,
                'serverSide': true,
                'paginate': false,
                'paging': false,
                'bPaginate': false,
                'bInfo': false,
                'footerCallback': function (tfoot, data, start, end, display) {
                    var api = this.api();
                    var totalDeposit = api.column(1).data().reduce(function (total, row) {
                        return parseInt(total) + parseInt(row);
                    }, 0);
                    var totalWithdraw = api.column(2).data().reduce(function (total, row) {
                        return parseInt(total) + parseInt(row);
                    }, 0);
                    var sumDeposit = api.column(3).data().reduce(function (total, row) {
                        return (new Decimal(total)).plus(row);
                    }, 0);
                    var sumWithdraw = api.column(4).data().reduce(function (total, row) {
                        return (new Decimal(total)).plus(row);
                    }, 0);
                    var sumCompanyFee = api.column(5).data().reduce(function (total, row) {
                        return (new Decimal(total)).plus(row);
                    }, 0);
                    var sumCustomerFee = api.column(6).data().reduce(function (total, row) {
                        return (new Decimal(total)).plus(row);
                    }, 0);

                    $(api.column(1).footer()).html(totalDeposit);
                    $(api.column(2).footer()).html(totalWithdraw);
                    $(api.column(3).footer()).html(sumDeposit.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,'));
                    $(api.column(4).footer()).html(Decimal.mul(sumWithdraw, -1).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,'));
                    $(api.column(5).footer()).html(sumCompanyFee.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,'));
                    $(api.column(6).footer()).html(sumCustomerFee.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,'));
                }
            }
        });
    }

    filter(from, to)
    {
        this.from = from;
        this.to = to;

        this.table.initialize();
        this.table.dataTable.api().ajax.reload();
    }
}