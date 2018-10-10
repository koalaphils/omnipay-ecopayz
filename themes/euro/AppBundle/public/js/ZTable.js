var ZTables = {};

var ZTableGlobals = {};
var ZTableInputs = {};

class ZTable
{
    constructor(element, options)
    {
        var id = null;
        do {
            id = Math.random();
            id = id.toString(36).substr(2, 9);
            if (!(id in ZTables))  {
                ZTables[id] = this;
                break;
            }
        } while (true);

        this.$element = $(element);
        $(element).data('ztable', this);
        var settings = window.lodash.merge(this.deafultSettings(), options);

        this.id = function () {
            return id;
        };

        this.settings = function () {
            return settings;
        };
        this.initialized = false;

        this.featureTypes = this.types();
        this.features = {};
        this.featuresSymbol = {};
        this.drawTable = 0;

        var ztable = this;
        var dataOption = settings.ajax.data;
        settings.ajax.data = function (datatableData) {
            var newData = {};
            newData.filters = {
                'search': ztable.getSearch()
            };
            newData.length = datatableData.length;
            newData.limit = datatableData.length;
            newData.page = Math.ceil((datatableData.start)/(datatableData.length)) + 1;
            for (var name in ztable.features) {
                if (ztable.features[name].feature.addToFilter) {
                    window.lodash.set(newData.filters, name, ztable.features[name].getValue(ztable.features[name]));
                }
            }
            newData.order = {};
            for (var i in datatableData.order) {
                var order = datatableData.order[i];
                newData.order[i] =  {
                    'column': datatableData.columns[order.column].name,
                    'dir': order.dir
                };
            }

            newData = window.lodash.merge(newData, ztable.settings().additionalFilters(datatableData, newData));
            if (ztable.settings().filterParent === null) {
                return dataOption(newData, datatableData);
            } else {
                var replicatedData = {};
                replicatedData[ztable.settings().filterParent] = newData;

                return dataOption(newData, datatableData);
            }
        };

        if (this.settings().autoinit) {
            this.initialize();
        }
    }

    deafultSettings()
    {
        var ztable = this;
        return {
            'dom': "<'row m-b-10'<'col-md-12'<'pull-right'C>F>><'row form-inline'<'col-md-6 col-sm-6 col-xs-6'l><'col-md-6 col-sm-6 col-xs-6 text-right's>>t",
            'featuresDom': "",
            'autoinit': true,
            'autoloadTable': true,
            'template': {
                'searchContainer': "<div class='ztable_search_container'></div>",
                'searchInput': "<input type='text' class='ztable_search_input' name='search'  /><i class='form-control-feedback l-h-30 fa fa-search'></i>",
                'lengthContainer': "<div class='ztable_length_container'></div>",
                'lengthInput': "Show <select class='ztable_length_input'></select> Entries",
                'colvisContainer': '<div class="ztable_colvis_container"></div>',
                'colvisButton': "<select class='ztable_colvis_btn' multiple='multiple'></select>",
                'featureType': {
                }
            },
            'classes': {
                'searchContainer': "form-group form-group-sm has-feedback",
                'searchInput': "form-control",
                'lengthContainer': '',
                'lengthInput': 'col-md-1 selectpicker inline-block form-control',
                'colvisContainer': '',
                'colvisButton': ''
            },
            'attrs': {
                'lengthInput': { 'data-style': 'btn-white btn-sm' },
                'colvisButton': { },
                'searchInput': { }
            },
            'filterParent': null,
            'ajax': {
                'cache': false,
                'url': '',
                'type': 'POST',
                'data': function (data, datatableData) {
                    return data;
                },
                'dataFilter': function (str) {
                    var draw = this.draw;
                    var json = $.parseJSON( str );

                    return JSON.stringify({
                        'draw': draw,
                        'data': json.records,
                        'recordsFiltered': json.recordsFiltered,
                        'recordsTotal': json.recordsTotal
                    });
                }
            },
            'additionalFilters': function (datatableData, ztableData) {
                return {};
            },
            'colvis': {
                'buttonText': '<i class="fa fa-columns"></i>',
                'hidden': [],
                'exclude': []
            },
            'lengths': {
                'selection': ['10', '20', '50', '100'],
                'style': 'btn-white'
            },
            'search': '',
            'columnDefs': {},
            'order': [],
            'ordering': false,
            'dt': {},
            'initialized': function (ztable) {}
        };
    }

    initialize()
    {
        if (!this.initialized) {
            for (var featureName in this.settings().features) {
                var feature = this.settings().features[featureName];
                this.addFeature(featureName, feature);
            }
            this.renderDom();
            this.initializeTable();
            this.initSearch();
            this.initLength();
            this.initColvis();

            this.initialized = true;

            this.settings().initialized(this);
        }
    }

    initializeTable()
    {
        var ztable = this;
        var dataTableOptions = {
            'searching': false,
            'createdRow': this.settings().createdRow,
            'lengthChange': false,
            'ordering': this.settings().ordering,
            'processing': true,
            'serverSide': true,
            'deferLoading': 1,
            'dom':
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
            'renderer': 'bootstrap',
            'ajax': this.settings().ajax,
            'columns': this.settings().columns,
            'columnDefs': this.settings().columnDefs,
            'order': this.settings().order
        };
        dataTableOptions = $.extend(true, dataTableOptions, ztable.settings().dt);

        this.dataTable = $('table', this.$element).dataTable(dataTableOptions);

        if (ztable.settings().autoloadTable) {
            this.reloadTable();
        }
    }

    renderDom()
    {
        var dom = this.settings().dom;
        var domLength = this.settings().dom.length;
        var elem = this.$element;
        for (var i = 0; i < domLength; i++) {
            var char = this.settings().dom[i];
            if (char === '<') {
                if (dom[i+1] === '$') {
                    i++;
                    var tempElem = $('<span/>');
                } else if (dom[i+1] === '!') {
                    i++;
                    var tempElem = $('<i/>');
                } else if (dom[i+1] === '^') {
                    i++;
                    var tagName = '';
                    for (; i < domLength; i++) {
                        char = dom[i];
                        if (char === ' ') {
                            break;
                        }

                        tagName += char;
                    }
                    var tempElem = $('<' + tagName + '/>');
                } else {
                    var tempElem = $('<div/>');
                }
                if (elem !== null) {
                    $(elem).append(tempElem);
                }
                elem = tempElem;
                tempElem = null;
            }
            if (char === "'") {
                var quotedString = "";
                do {
                    i++;
                    char = this.settings().dom[i];
                    var loop = true;
                    if (char === "'") {
                        loop = false;
                        continue;
                    }
                    quotedString += char;
                } while (loop);

                $(elem).addClass(quotedString);
            }

            if (char === 's') {
                $(elem).append(this.renderSearch());
            }

            if (char === 'l') {
                $(elem).append(this.renderLength());
            }

            if (char === 't') {
                $(elem).append($('table', this.$element));
            }

            if (char === 'C') {
                $(elem).append(this.renderColvis());
            }

            if (char === 'F') {
                this.renderFeatures(elem);
            }

            if (char === ' ') {
                $(elem).append('&nbsp;');
            }

            if (typeof this.featuresSymbol[char] !== 'undefined') {
                var feature = this.features[this.featuresSymbol[char]];
                if (!feature.isRendered) {
                    feature.renderDom().each(function () {
                        $(elem).append(this);
                    });
                }
            }

            if (char === '>') {
                elem = $(elem).parent();
            }
        }
    }


    renderSearch()
    {
        var searchContainer = $(this.settings().template.searchContainer);
        searchContainer.append(this.settings().template.searchInput);

        searchContainer.addClass(this.settings().classes.searchContainer);
        $.each(this.settings().attrs.searchInput, function (i) {
            searchContainer.find('.ztable_search_input').attr(i, this);
        });
        searchContainer.find('.ztable_search_input').addClass(this.settings().classes.searchInput);
        searchContainer.find('.ztable_search_input').val(this.settings().search);


        return searchContainer;
    }

    renderLength()
    {
        var lengthContainer = $(this.settings().template.lengthContainer);
        lengthContainer.append(this.settings().template.lengthInput);

        lengthContainer.addClass(this.settings().classes.lengthContainer);
        lengthContainer.find('.ztable_length_input')
            .addClass(this.settings().classes.lengthInput)
        ;

        $.each(this.settings().attrs.lengthInput, function (i) {
            lengthContainer.find('.ztable_length_input').attr(i, this);
        });

        for (var i = 0; i < this.settings().lengths.selection.length; i++) {
            var length = this.settings().lengths.selection[i];
            lengthContainer.find('.ztable_length_input').append('<option value"'+ length +'">' + length + '</option>');
        }

        return lengthContainer;
    }

    renderColvis()
    {
        var colvisContainer = $(this.settings().template.colvisContainer);
        colvisContainer.append(this.settings().template.colvisButton);

        colvisContainer.addClass(this.settings().classes.colvisContainer);
        colvisContainer.find('.ztable_colvis_btn')
            .html(this.settings().colvis.buttonText)
            .addClass(this.settings().classes.colvisButton)
        ;

        $.each(this.settings().attrs.colvisButton, function (i) {
            colvisContainer.find('.ztable_colvis_btn').attr(i, this);
        });

        return colvisContainer;
    }

    initSearch()
    {
        var timer = null;
        var dataTable = this.dataTable;
        var zTable = this;
        $('.ztable_search_input', this.$element).keyup(function (e) {
            var input = this;
            clearTimeout(timer);
            var delayInMilliseconds = 500;
            dataTable.api().search($(this).val());
            timer = setTimeout(
                function() {
                    zTable.reloadTable();
                },
                delayInMilliseconds
            );
        });
    }

    initLength()
    {
        var ztable = this;
        $('.ztable_length_input', ztable.$element).change(function () {
            ztable.dataTable.api().page.len(ztable.getLength());
            ztable.reloadTable();
        });
    }

    initColvis()
    {
        var ztable = this;
        var showed = [];
        var hidden = [];

        if (typeof this.settings().colvis.hidden === 'function') {
            hidden = this.settings().colvis.hidden();
        } else {
            hidden = this.settings().colvis.hidden;
        }

        this.dataTable.api().columns().every(function () {
            if (ztable.settings().colvis.exclude.indexOf(this.settings()[0].aoColumns[this.index()].name) === -1) {
                var colText = $(this.header()).text();
                $('select.ztable_colvis_btn', ztable.$element).append('<option value="' + this.index() + '">' + colText + '</option>');
            }

            if (hidden.indexOf(this.index()) === -1) {
                showed.push(this.index());
            }
        });

        $('select.ztable_colvis_btn', ztable.$element).selectpicker({
            'size': false,
            'dropdownAlignRight': 'auto',
            'iconBase': 'fa',
            'tickIcon': 'fa-check',
            'title': '<i class="fa fa-columns"></i>',
            'selectedTextFormat': 'static',
            'style': 'btn-inverse btn-sm'
        });

        $('select.ztable_colvis_btn', ztable.$element).selectpicker('val', showed);

        for (var i in hidden) {
            if (!isNaN(hidden[i])) {
                this.dataTable.api().column(hidden[i]).visible(false);
            } else {
                var name = hidden[i] + ':name';
                this.dataTable.api().column(name).visible(false);
            }
        }

        $('select.ztable_colvis_btn', ztable.$element).on('changed.bs.select', function (e, i, n, o) {
            var val = $(e.target).find('option').get(i).value;
            ztable.dataTable.api().column(val).visible(n);
            $(ztable.$element).triggerHandler('ztable.change.column.visible', [this, ztable]);
        });
    }

    getLength()
    {
        return $('.ztable_length_input', this.$element).selectpicker('val');
    }

    getSearch()
    {
        return $('.ztable_search_input', this.$element).val();
    }

    addFeature(name, feature)
    {
        var type = this.featureTypes[feature.type];
        if (typeof feature.symbol !== 'undefined') {
            this.featuresSymbol[feature.symbol] = name;
        }

        this.features[name] = new FeatureType(name, type, feature, this);
    }

    allFeatureIsDone()
    {
        var done = true;
        for (var i in this.features) {
            if (!this.features[i].done) {
                done = false;
                break;
            }
        }

        return done;
    }

    getColumnVisibility()
    {
        var columnsVisibility = [];
        var dataTable = this.dataTable;
        dataTable.api().columns().every(function (i) {
            columnsVisibility[i] = this.visible();
        });

        return columnsVisibility;
    }

    renderFeatures(elem)
    {
        var dom = this.settings().featuresDom;
        var domLength = this.settings().featuresDom.length;
        for (var i = 0; i < domLength; i++) {
            var char = this.settings().featuresDom[i];
            if (char === '<') {
                if (dom[i+1] === '$') {
                    i++;
                    var tempElem = $('<span/>');
                } else if (dom[i+1] === '!') {
                    i++;
                    var tempElem = $('<i/>');
                } else if (dom[i+1] === '^') {
                    i++;
                    var tagName = '';
                    for (++i; i < domLength; i++) {
                        char = dom[i];
                        if (char === ' ') {
                            break;
                        }

                        tagName += char;
                    }
                    var tempElem = $('<' + tagName + '/>');
                } else {
                    var tempElem = $('<div/>');
                }
                if (elem !== null) {
                    $(elem).append(tempElem);
                }
                elem = tempElem;
                tempElem = null;
            } else if (char === "'") {
                var quotedString = "";
                do {
                    i++;
                    char = this.settings().featuresDom[i];
                    var loop = true;
                    if (char === "'") {
                        loop = false;
                        continue;
                    }
                    quotedString += char;
                } while (loop);

                $(elem).addClass(quotedString);
            } else if (char === '#') {
                var quotedString = "";
                do {
                    i++;
                    char = this.settings().featuresDom[i];
                    var loop = true;
                    if (char === "#") {
                        loop = false;
                        continue;
                    }
                    quotedString += char;
                } while (loop);
                $(elem).append(quotedString);
            } else if (char === ' ') {
                $(elem).append('&nbsp;');
            } else if (char === 'i') {
                $(elem).append(this.render());
            } else if ((dom === '$' && dom[i+1] === '>') || (dom === '!' && dom[i+1] === '>') || char === '>') {
                elem = $(elem).parent();
            } else if (typeof this.featuresSymbol[char] !== 'undefined') {
                var feature = this.features[this.featuresSymbol[char]];
                if (!feature.isRendered) {
                    feature.renderDom().each(function () {
                        $(elem).append(this);
                    });
                    feature.feature.rendered(feature);
                }
            } else if (char === '>') {
                elem = $(elem).parent();
            }
        }
    }

    getType(type)
    {
        if (typeof this.types()[type] !== 'undefined') {
            return this.types()[type];
        }

        return {};
    }

    types()
    {
        return {
            'text': {
                'defaults': {
                    'dom': "<'form-group form-group-sm m-r-10'l i>",
                    'getValue': function (feature) {
                        return $(feature.input).val();
                    },
                    'resetValue': function (feature) {
                        $(feature.input).val(feature.feature.value);
                    },
                },
                'init': function (ztable, feature, featureType) {
                    featureType.input = $('<input type="text" class="form-control" />');
                    $(featureType.input).val(featureType.feature.value);
                }
            },
            'date': {
                'defaults': {
                    'dom': "<'form-group form-group-sm m-r-10'l <'input-group'i<^span 'input-group-addon bg-custom b-0 text-white'<!'fa fa-calendar'!>>>>",
                    'resetValue': function (feature) {
                        $(feature.input).datepicker('update', '');
                    },
                },
                'init': function (ztable, feature, featureType) {
                    featureType.input = $('<input class="form-control" />');
                    $(featureType.input).val(featureType.feature.value);
                    $(featureType.input).datepicker();
                }
            },
            'button': {
                'defaults': {
                    'dom': 'i',
                    'class': 'btn btn-sm',
                    'addToFilter': false
                },
                'init': function (ztable, feature, featureType) {
                    featureType.input = $('<button></button>');
                    $(featureType.input).append(feature.label);
                }
            },
            'select': {
                'defaults': {
                    'dom': "<'form-group form-group-sm m-r-10'l i>",
                    'class': "form-control",
                    'getValue': function (feature) {
                        return $(feature.input).val();
                    },
                    'value': [],
                    'resetValue': function (feature) {
                        $(feature.input).val(feature.feature.value);
                    },
                },
                'init': function (ztable, feature, featureType) {
                    featureType.input = $('<select/>');
                    for (var value in feature.choices) {
                        var label = feature.choices[value];
                        var option = $('<option value="' + value + '">' + label + '</option>');

                        if (featureType.feature.value.indexOf(value) !== -1) {
                            option.attr('selected', 'selected');
                        }
                        $(featureType.input).append(option);
                    }
                }
            }
        }
    }

    reset()
    {
        this.waitDrawTable();
        for (var featureName in this.features) {
            this.features[featureName].resetValue();
        }
        this.reloadTable();
    }

    getFeature(name)
    {
        return this.features[name];
    }

    reloadTable()
    {
        var drawTable = this.drawTable;
        if (this.drawTable > 0) {
            this.drawTable -= 1;
        }
        if (drawTable > 0) {
            drawTable -= 1;
        }
        if (drawTable === 0 && this.allFeatureIsDone()) {
            if (this.isServerSide()) {
                this.dataTable.api().ajax.reload();
            } else {
                this.dataTable.api().draw();
            }
        }
    }

    waitDrawTable()
    {
        this.drawTable += 1;
    }

    forceReloadTable()
    {
        this.drawTable = 0;
        this.reloadTable();
    }

    isServerSide()
    {
        return this.dataTable.api().page.info().serverSide;
    }

    goToLastPageNumber()
    {
        this.dataTable.api().page( 'last' ).draw( 'page' );
    }
}

class FeatureType
{
    constructor(name, type, feature, ztable)
    {
        this.id = ztable.id() + '_' + name;
        this.name = name;
        this.type = type;
        this.feature = window.lodash.merge(this.defaults(), type.defaults);
        this.feature = window.lodash.merge(this.feature, feature);
        this.ztable = ztable;
        this.isRendered = false;
        this.input = null;
        this.done = false;
        this.initialize();
        this.feature.initialized(this);
    }

    initialize()
    {
        if (typeof this.feature.init === 'function') {
            this.feature.init(this.ztable, this.feature, this);
        } else {
            this.type.init(this.ztable, this.feature, this);
        }
        $(this.input).attr('name', this.name);
        for (var attr in this.feature.attrs) {
            $(this.input).attr(attr, this.feature.attrs[attr]);
        }

        $(this.input).addClass(this.feature.class);
        $(this.input).attr('id', this.id);

        if (!this.feature.waitByTable) {
            this.done = true;
        }
    }

    render()
    {
        this.isRendered = true;
        if (typeof this.feature.render === 'function') {
            return this.feature.render(this.ztable, this.feature, this);
        }

        if (typeof this.type.render === 'function') {
            return this.type.render(this.ztable, this.feature, this);
        }

        return this.input;
    }

    setToDone(reload)
    {
        this.done = true;
    }

    getName()
    {
        return this.name;
    }

    getValue()
    {
        if (typeof this.feature.getValue === 'function') {
            return this.feature.getValue(this);
        }

        return this.type.getValue(this);
    }

    defaults()
    {
        return {
            'dom': '<li>',
            'type': 'text',
            'label': null,
            'applyOnChanged': true,
            'getValue': function (feature) {
                return $(feature.input).val();
            },
            'attrs': {},
            'class': '',
            'initialized': function (feature) {
                if (feature.feature.applyOnChanged) {
                    $(feature.input).change(function () {
                        feature.ztable.waitDrawTable();
                        feature.ztable.reloadTable();
                    });
                }
            },
            'waitByTable': false,
            'resetValue': function(feature) {
                $(feature.input).val(feature.feature.value);
            },
            'value': '',
            'addToFilter': true,
            'rendered': function (feature) {}
        };
    }

    resetValue()
    {
        this.feature.resetValue(this);
    }

    renderDom()
    {
        var dom = this.feature.dom;
        var domLength = dom.length;
        var elem = $('<div />');
        for (var i = 0; i < domLength; i++) {
            var char = dom[i];
            if (char === '<') {
                if (dom[i+1] === '$') {
                    i++;
                    var tempElem = $('<span/>');
                } else if (dom[i+1] === '!') {
                    i++;
                    var tempElem = $('<i/>');
                } else if (dom[i+1] === '^') {
                    i++;
                    var tagName = '';
                    for (++i; i < domLength; i++) {
                        char = dom[i];
                        if (char === ' ') {
                            break;
                        }

                        tagName += char;
                    }
                    var tempElem = $('<' + tagName + '/>');
                } else {
                    var tempElem = $('<div/>');
                }
                if (elem !== null) {
                    $(elem).append(tempElem);
                }
                elem = tempElem;
                tempElem = null;
            } else if (char === "'") {
                var quotedString = "";
                do {
                    i++;
                    char = dom[i];
                    var loop = true;
                    if (char === "'") {
                        loop = false;
                        continue;
                    }
                    quotedString += char;
                } while (loop);

                $(elem).addClass(quotedString);
            } else if (char === '#') {
                var quotedString = "";
                do {
                    i++;
                    char = dom[i];
                    var loop = true;
                    if (char === "#") {
                        loop = false;
                        continue;
                    }
                    quotedString += char;
                } while (loop);
                $(elem).append(quotedString);
            } else if (char === ' ') {
                $(elem).append('&nbsp;');
            } else if (char === 'l') {
                $(elem).append('<label>' + this.feature.label + '</label>');
            } else if (char === 'i') {
                $(elem).append(this.render());
            } else if ((dom === '$' && dom[i+1] === '>') || (dom === '!' && dom[i+1] === '>') || char === '>') {
                elem = $(elem).parent();
            }
        }

        return $(elem).children();
    }
}