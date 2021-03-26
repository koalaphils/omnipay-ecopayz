class ProductReport
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
                                    'search' :$('#' + $(this).attr('id')).parent().parent().parent().find('.ztable_search_input').val(),
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
                    'data': 'product_name',
                    'render': function (data, text, full) {
                        return '<a href="' + full.link + '" target="_blank">' + data + '</a>';
                    }
                },
                {
                    'data': 'num_sign_ups'
                },
                {
                    'data': 'num_new_accounts'
                },
                {
                    'data': 'num_signups_wo_deposit',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(0).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'total_register',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(0).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'num_active_accounts',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(0).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': '',
                    'render': function (data, text, full) {
                        return (new Decimal(full.total_register)).minus(full.num_active_accounts).toFixed(0).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'turnover',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'win_loss',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'gross_commission',
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
                "drawCallback": function( settings ) {
                    if (report.table.dataTable) {
                        report.table.dataTable.api().responsive.recalc();
                    }
                },
                'footerCallback': function (tfoot, data, start, end, display) {
                    var api = this.api();
                    var signUps = 0;
                    var newAccounts = 0;
                    var withoutDeposits = 0;
                    var registeredAcounts = 0;
                    var activeAccounts = 0;
                    var inactiveAccounts = 0;
                    var turnover = new Decimal(0);
                    var winLoss = new Decimal(0);
                    var gross = new Decimal(0);

                    for (var rowIndex in data) {
                        signUps += parseInt(data[rowIndex].num_sign_ups);
                        newAccounts += parseInt(data[rowIndex].num_new_accounts);
                        withoutDeposits += parseInt(data[rowIndex].num_signups_wo_deposit);
                        registeredAcounts += parseInt(data[rowIndex].total_register);
                        activeAccounts += parseInt(data[rowIndex].num_active_accounts);
                        inactiveAccounts += parseInt(parseInt(data[rowIndex].total_register) - parseInt(data[rowIndex].num_active_accounts));
                        turnover = turnover.plus(data[rowIndex].turnover);
                        winLoss = winLoss.plus(data[rowIndex].win_loss);
                        gross = gross.plus(data[rowIndex].gross_commission);
                    }

                    $(api.column(1).footer()).html(signUps);
                    $(api.column(2).footer()).html(newAccounts);
                    $(api.column(3).footer()).html(withoutDeposits);
                    $(api.column(4).footer()).html(registeredAcounts);
                    $(api.column(5).footer()).html(activeAccounts);
                    $(api.column(6).footer()).html(inactiveAccounts);
                    $(api.column(7).footer()).html(turnover.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,'));
                    $(api.column(8).footer()).html(winLoss.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,'));
                    $(api.column(9).footer()).html(gross.toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,'));
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