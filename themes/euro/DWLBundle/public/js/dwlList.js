(function ($, window, document, undefined) {
    
    var pluginName = "DWL";
    
    var List = function (elem, options)
    {
        this.id = genId();
        this.el = elem;
        this.$el = $(elem);
        this.options = $.extend(true, $.fn[pluginName].defaults, options);
        this.dt = null;
        this.draw = 0;
        this.init();
    };
    
    List.prototype = {
        'init': function () {
            this._processTemplate();
            this._initTable();
        },
        _initTable: function () {
            var list = this;
            var table = this.$el.find(this.options.table);
            this.dt = table.dataTable({
                'searching': false,
                'lengthChange': false,
                'ordering': false,
                'processing': false,
                'serverSide': true,
                'deferLoading': 0,
                'deferRender': true,
                'ajax': {
                    'url': this.options.url.list,
                    'type': 'POST',
                    'data': function (s) {
                        var ns = {
                            'draw': s.draw,
                            'column': [
                                s.columns[0],
                                s.columns[1],
                                s.columns[2],
                                s.columns[3],
                                s.columns[4],
                                s.columns[5]
                            ],
                            'route': 1
                        };
                        ns.column[4].data = 'dwl.details';
                        list.draw = s.draw;
                        if (typeof list.tpl.P.val() !== "undefined" && list.tpl.P.val() !== null && list.tpl.P.val() !== '') {
                            ns.product = list.tpl.P.val();
                        }
                        if (typeof list.tpl.C.val() !== "undefined" && list.tpl.C.val() !== null && list.tpl.C.val() !== '') {
                            ns.currency = list.tpl.C.val();
                        }
                        if (typeof list.tpl.F.val() !== "undefined" && list.tpl.F.val() !== null && list.tpl.F.val() !== '') {
                            ns.from = list.tpl.F.val();
                        }
                        if (typeof list.tpl.T.val() !== "undefined" && list.tpl.T.val() !== null && list.tpl.T.val() !== '') {
                            ns.to = list.tpl.T.val();
                        }
                        return ns;
                    },
                    'dataFilter': function (str) {
                        var json = $.parseJSON( str );
                        return JSON.stringify({
                            'data': json.data,
                            'recordsFiltered': json.filtered,
                            'recordsTotal': json.total
                        });
                    }
                },
                'columns': [
                    {'name': 'id', 'data': 'dwl.id', 'defaultContent': ''},
                    {
                        'data': 'dwl.date',
                        'name': 'date',
                        'render': function (data) {
                            if (data !== null) { 
                                return moment(data.date).format(Global.dateFormat);
                            }
                            return '';
                        }
                    },
                    {'data': 'dwl.product.name', 'name': 'productName', 'defaultContent': ''},
                    {'data': 'dwl.currency.code', 'name': 'currencyCode', 'defaultContent': ''},
                    {'data': 'dwl.details.total.record', 'name': 'totalRecord', 'defaultContent': ''},
                    {
                        'data': 'dwl.status',
                        'name': 'status',
                        'render': function (data) {
                            if (data !== null) {
                                return list._trans('status.' + data, {});
                            }
                        }
                    },
                    {
                        'data': 'route',
                        'name': 'actions',
                        'render': function (data) {
                            if (data !== null) {
                                return '<button type="button" class="btn btn-primary btn-sm dropdown-toggle waves-effect">View</button> <button type="button" class="btn btn-warning btn-sm dropdown-toggle waves-effect">Export</button>';
                            }
                        }
                    }
                ]
            }).api();
            this._generateColView();
        },
        _generateColView: function () {
            var list = this;
            for (var i in this.options.colVis.hidden) {
                var name = this.options.colVis.hidden[i] + ':name';
                this.dt.column(name).visible(false);
            }
            this.dt.columns().every(function () {
                if (list.options.colVis.exclude.indexOf(this.settings()[0].aoColumns[this.index()].name) === -1) {
                    var colText = $(this.header()).text();
                    list.tpl.s.append('<option value="' + this.index() + '">' + colText + '</option>');
                }
            });
            
            
            this.tpl.s.selectpicker('selectAll');
            this.tpl.s.on('changed.bs.select', function (e, i, n, o) {
                var val = $(e.target).find('option').get(i).value;
                list.dt.column(val).visible(n);
            });
        },
        _processTemplate: function () {
            var tpl = template();
            $.each(tpl, function (i, e) {
                tpl[i] = $(tpl[i]);
            });
            this.tpl = tpl;
            
            this.$el.prepend(this.options.processor.filter(this));
        },
        _trans: function (name, params) {
            var t = this.options.trans[this.options.lang];
            objectExtend(t);
            var text = t.array_get(name);
            return text;
        },
        'destroy': function() {
            
        }
    };
    
    $.fn[pluginName] = function (options)
    {
        var args = arguments;
        if (options === undefined || typeof options === 'object') {
            return this.each(function () {
                if(!$.data(this, 'plugin_' + pluginName)) {
                    $.data(this, 'plugin_' + pluginName, new List(this, options));
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

                    if (instance instanceof List && $.fn[pluginName].methods[options] && typeof instance[$.fn[pluginName].methods[options]] === 'function') {
                        instance[$.fn[pluginName].methods[options]].apply(instance, Array.prototype.slice.call(args, 1));
                    }
                });
            }
        }
    }
    
    function template(id)
    {
        var tpl = {
            'm': '<div class="dwlf row"></div>',
            'p': '<label class="dwlf_pl"></label>',
            'P': '<select class="dwlf_pi"></select>',
            'c': '<label class="dwlf_cl"></label>',
            'C': '<select class="dwlf_ci"></select>',
            'f': '<label class="dwlf_fl"></label>',
            'F': '<input type="text" class="dwlf_fi"/>',
            't': '<label class="dwlf_tl"></label>',
            'T': '<input type="text" class="dwlf_ti"/>',
            's': '<select class="dwl_sb" multiple="multiple"></select>',
            'S': '<ul class="dwl_sb_cols"></ul>',
            'Q': '<button class="dwl_fil"></button>'
        };
        return tpl;
    }
    
    function genId()
    {
        var id = null;
        do {
            id = Math.random();
            id = id.toString(36).substr(2, 9);
            
            if ($.fn[pluginName]._ids.indexOf(id) === -1) {
                $.fn[pluginName]._ids.push(id);
                break;
            }
        } while (true);
        return id;
    }
    
    function filterProcessor(list)
    {   
        function generateControl(l, i, idPrefix) {
            var g = $('<div class="col-md-2 form-group"></div>');
            g/*.append(l)*/.append(i);
            l.attr('for', idPrefix + list.id);
            i
                .addClass('form-control')
                .addClass('m-l-10')
                .attr('id', idPrefix + list.id).attr('placeholder', l.html());
            g.addClass('m-l-10');
            return g;
        };
        
        function initFilters(l) {
            l.tpl.P.select2({
                'placeholder': l._trans('selectProduct'),
                'ajax': {
                    'url': l.options.url.products,
                    'type': 'POST',
                    'cache': true,
                    'data': function(params) {
                        var page = params.page || 1;
                        var length = 10;
                        var start = (page - 1) * length;
                        return {
                            'idColumn': 'id',
                            'select2': 1,
                            'search': params.term,
                            'length': length,
                            'start': start
                        };
                    },
                    'processResults': function(data, page) {
                        return {
                            'results': data.items,
                            'pagination': {
                                'more': (page * 10) < data.recordsFiltered
                            }
                        };
                    }
                },
                'minimumInputLength': 0
            });
            l.tpl.C.select2({
                'placeholder': l._trans('selectCurrency'),
                'ajax': {
                    'url': l.options.url.currencies,
                    'type': 'POST',
                    'cache': true,
                    'data': function(params) {
                        var page = params.page || 1;
                        var length = 10;
                        var start = (page - 1) * length;
                        return {
                            'idColumn': 'id',
                            'select2': 1,
                            'search': params.term,
                            'length': length,
                            'start': start
                        };
                    },
                    'processResults': function(data, page) {
                        return {
                            'results': data.items,
                            'pagination': {
                                'more': (page * 10) < data.recordsFiltered
                            }
                        };
                    }
                },
                'minimumInputLength': 0
            });
            
            l.tpl.FT.datepicker({
                'toggleActive': true
            });
            
            l.tpl.Q.click({'list': l}, function (e) {
                e.data.list.dt.draw();
            });
        }
        
        var tpl = list.tpl;
        tpl.p.html(list.options.trans[list.options.lang].selectProduct);
        tpl.c.html(list.options.trans[list.options.lang].selectCurrency);
        tpl.f.html(list.options.trans[list.options.lang].from);
        tpl.t.html(list.options.trans[list.options.lang].to);
        tpl.s
            .attr('data-dropdown-align-right', true)
            .attr('data-title', '<i class="fa fa-columns"></i>')
            .attr('data-selected-text-format', 'static')
            .html(list._trans('columns'));
        tpl.Q
            .attr('type', 'button')
            .addClass('btn btn-success')
            .html(list._trans('filter'))
        ;
        // tpl.s.selectpicker();
        
        tpl.m.addClass('form');
        tpl.m.append(generateControl(tpl.p, tpl.P, 'dwlf_pi_'));
        tpl.m.append(generateControl(tpl.c, tpl.C, 'dwlf_ci_'));
        // daterange
        tpl.FT = $('<div class="input-daterange input-group"></div>');
        tpl.F.addClass('form-control').attr('id', 'dwlf_fi_'+list.id).attr('placeholder', tpl.f.text());
        tpl.T.addClass('form-control').attr('id', 'dwlf_ti_'+list.id).attr('placeholder', tpl.t.text());
        tpl.FT.append(tpl.F);
        tpl.FT.append('<span class="input-group-addon bg-custom b-0 text-white">to</span>');
        tpl.FT.append(tpl.T);
        tpl.FTC = $('<div class="col-md-4"></div>').append(tpl.FT);
        tpl.m.append(tpl.FTC);
        tpl.m.append($('<div class="col-md-2" id="dwlf_btn_cont_' + list.id + '"></div>').append(tpl.Q));
        
        // tpl.m.append(generateControl(tpl.f, tpl.F, 'dwlf_fi'));
        // tpl.m.append(generateControl(tpl.t, tpl.T, 'dwlf_ti'));
        tpl.m.append(tpl.s);
        
        initFilters(list);
        
        return tpl.m;
    }
    
    $.fn[pluginName]._ids = [];
    $.fn[pluginName].getters = [];
    $.fn[pluginName].setters = [];
    
    $.fn[pluginName].methods = {};
    
    $.fn[pluginName].defaults = {
        'table': 'table',
        'processor': {
            'filter': filterProcessor
        },
        'lang': 'en',
        'trans': {
            'en': {
                'selectProduct': "Select Product",
                'selectCurrency': "Select Currency",
                'from': "From",
                'to': "To",
                'columns': '<i class="fa fa-columns"></i>',
                'filter': "Filter",
                'status': {
                    1: 'Uploaded',
                    2: 'Processing',
                    3: 'Processed',
                    4: 'Submited',
                    5: 'Processing',
                    6: 'Completed'
                }
            }
        }
    };
}) (jQuery, window, document);