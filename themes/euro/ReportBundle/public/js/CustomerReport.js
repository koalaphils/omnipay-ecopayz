class CustomerReport
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
        $('table', this.elem).on('xhr.dt', function ( e, settings, json, xhr ) {
            if (typeof json.totalSummary !== 'undefined') {
                var totalSummary = json.totalSummary;
                var api = report.table.dataTable.api();

                $(api.column(1).footer()).html('<i class="fa fa-spinner">');
                $(api.column(2).footer()).html('<i class="fa fa-spinner">');
                $(api.column(3).footer()).html('<i class="fa fa-spinner">');
                $(api.column(4).footer()).html('<i class="fa fa-spinner">');
                $(api.column(5).footer()).html('<i class="fa fa-spinner">');
                $(api.column(6).footer()).html('<i class="fa fa-spinner">');
                $(api.column(7).footer()).html('<i class="fa fa-spinner">');
            }
        });
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
                            var customer_search = $(report.elem).find('.ztable_search_input').val();
                            var query = {
                                'filters': {'from': report.from, 'to': report.to, 'customer_search' : customer_search}
                            }
                            window.open(report.exportUrl + '?' + $.param(query), '_blank');
                        });
                    }
                }
            },
            'ajax': {
                'url': this.reportUrl,
                'type': 'GET',
                'beforeSend': function () {
                    $('.available-balance-date').text(moment(report.to).format('MMM D, YYYY'));
                },
                'data': function (data) {
                    var filters = {};
                    if (report.from !== '') {
                        filters.from = report.from;
                    }
                    if (report.to !== '') {
                        filters.to = report.to;
                    }

                    filters.customer_search = $(report.elem).find('.ztable_search_input').val();
                    data.filters = filters;

                    return data;
                },
                'dataFilter': function (str) {
                    var draw = this.draw;
                    var json = $.parseJSON( str );

                    return JSON.stringify({
                        'draw': draw,
                        'data': json.records,
                        'recordsFiltered': json.recordsFiltered,
                        'recordsTotal': json.recordsTotal,
                        'totalSummary': json.totalSummary
                    });
                }
            },
            'columns': [
                {
                    'data': 'customer_full_name',
                    'render': function (data, text, full) {
                        if (data != null) {
                            return '<a href="' + full.link + '" target="_blank">' + data + '</a>';
                        }

                        return '<a href="' + full.link + '" target="_blank">' + full.customer_fname + ' ' + full.customer_lname + '</a>';
                    }
                },
                {
                    'data': 'dwl.turnover',
                    'render': function (data, text, full) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'dwl.gross',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'dwl.win_loss',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'dwl.commission',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'dwl.amount',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'after.total',
                    'render': function (data, text, full) {
                        var currentBalance = 0;
                        if (typeof full.customer_current_balance != 'undefined') {
                            currentBalance = full.customer_current_balance;
                        }
                        var transactionsAmountAfterReportEndDate = 0;
                        if (data != undefined) {
                            transactionsAmountAfterReportEndDate = data;
                        }

                        return Decimal.sub(currentBalance, transactionsAmountAfterReportEndDate).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                },
                {
                    'data': 'customer_current_balance',
                    'render': function (data) {
                        return (new Decimal(data)).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
                    }
                }
            ],
            // 'autoloadTable': false,
            'autoinit': false,
            'dt': {
                "drawCallback": function( settings ) {
                    if (report.table.dataTable) {
                        report.table.dataTable.api().responsive.recalc();
                    }
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