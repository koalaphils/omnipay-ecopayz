var List = function (elem, options) {
    var _defaults = this._defaults;
    
    this.id = generateId(this);
    this.el = elem;
    this.$el = $(elem);
    
    this.options = $.extend(true, _defaults, options);
    this.validateOptions();
    
    this.dt = null;
    this.draw = 0;
    this.$el.data('list', this);
    this.init();
    this.completed();
};

List.prototype = {
    'init': function () {
        this.table = this.$el.find('table');
        this._initTable();
    },
    'completed': function () {
        if (typeof this.options.completed != 'undefined') {
            this.options.completed();
        }
    }, 
    '_initTable': function () {
        var list = this;
        this.dt = this.table.dataTable($.extend(true, {
            'processing': true,
            'serverSide': true,
            'deferLoading': 1,
            'deferRender': true,
            'searching': true,
            'ordering': true,
            'lengthChange': true,
            'ajax': {
                'url': this._getOption('url.list'),
                'type': 'POST',
                'data': function (data) {
                    data.dataTable = 1;
                    data.route = 1;
                    return data;
                },
                'dataFilter': function (str) {
                    return str;
                },
                'dataSrc': function (data) {
                    return data.data;
                }
            },
            'columns': list.columns(),
            'columnDefs': this._getOption('columnDefs', [])
        }, this._getOption('dt'))).api();
        
        this.tableEvents();
        
        if (this._getOption('autoLoad', true) && this._getOption('dt.serverSide', true)) {
            this.loadTable();
        }
    },
    'columns': function () {
        var columns = this._getOption('columns', []);
        var addColumns = this._getOption('addColumns', []);
        for (var i in addColumns) {
            columns.push(addColumns[i]);
        }
        var editColumns = this._getOption('editColumns', {});
        for (var i in editColumns) {
            if (columns[i] === undefined) {
                for (var x in columns) {
                    if (columns[x].name !== undefined && columns[x].name == i) {
                        columns[x] = editColumns[i]
                        break;
                    }
                }
            } else {
                columns[i] = editColumns[i];
            }
        }
        return columns;
    },
    'tableEvents': function () {},
    'loadTable': function () {
        this.dt.draw();
    },
    '_getOption': function (key, def = null) {
        var data = this.options;
        var arr = key.split('.');

        for(var i = 0; i < arr.length; i++){
            data = data[arr[i]] != undefined ? data[arr[i]] : def;
        }

        return data;
    },
    'validateOptions': function () {
        if (typeof this.options.url == 'undefined') {
            throw new OptionException("Undefine option urls");
        } else if (typeof this.options.url.list == 'undefined') {
            throw new OptionsException('Undefine option url.list');
        }
        
        if (typeof this.options.columns == 'undefined') {
            throw new OptionException('Undefine option columns');
        }
    },
    '_defaults': {}
};

function OptionException(message) {
    if (typeof message == undefined) {
        message = "Option has error";
    }
    this.message = message;
    this.name = "OptionException";
}