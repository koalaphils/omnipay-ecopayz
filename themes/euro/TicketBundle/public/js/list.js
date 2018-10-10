(function($){
    
    $.fn.list = function(options, params){
        var settings = options;
        params = params || [];
        return this.each(function(){
            var elem = $(this);
            settings = $.extend(true,_defaults(),options);
            init(elem, settings);
        });
    };
    
    function init(elem, settings) {
        initView(elem, settings);
    }
    
    function initView(elem, settings) {
        $(settings.viewList).on('click','.zendesk_view', function(e) {
            var view = $(this).data('view');
            var table = '#table_' + view.id;
            if(true !== $(this).data('initTable'))
                initTable(this, table, view, settings);
        });
        
        
        $.ajax({
            'url': settings.url.views,
            'type': 'GET',
            'success': function (data) {
                var operators = {
                    'is': ':',
                    'less_than': '<',
                    'greater_than': '>'
                };
                var view = null;
                $.each(data.views, function(i, e) {
                    var _view = this;
                    var attr = "class='zendesk_view'";
                    if(i == 0) {
                        attr = "class='zendesk_view active'";
                        view = this;
                    } 
                    $(settings.viewList).append('<li id="view_'+this.id+'" '+attr+'><a href="#'+settings.viewContainerPrefix + this.id+'" data-toggle="tab" >'+this.title+'</li>'); //left tab
                    var conditions = {'all': [], 'any': []};
                    $.each(this.conditions.all, function() {
                        conditions.all[conditions.all.length] = this.field + operators[this.operator] + this.value;
                    });
                    $('#view_' + this.id, settings.viewList).data('view',this);
                    $('#view_' + this.id, settings.viewList).data('conditions',conditions);
                    
                    var template  = settings.viewTemplate;
                    template = template.replace(/%id%/g, settings.viewContainerPrefix + this.id);
                    
                    template = template.replace(/%table%/g,'<table id="table_' + this.id + '"><thead><tr></tr></thead><tbody></tbody></table>');
                    
                    $(settings.viewContainer).append(template);
                    
                    $.each(settings.tableAttr,function(i, e) {
                        $('#table_' + _view.id).attr(i, this);
                    });
                    
                    if(i == 0) {
                        $('#' + settings.viewContainerPrefix + this.id).addClass('active');
                    }
                    
                    $.each(_view.execution.columns, function (){
                        $('#table_' + _view.id + ' thead tr').append('<th>'+this.title+'</th>'); //this is column header
                    });
                    
                    $('#table_' + _view.id + ' thead tr').append('<th>Action</th>');
                    
                    $("#view_" + this.id + ".active",settings.viewList).click();
                });
            }
        });
    }
    
    function initTable(elem,table, view, settings) {
        var columns = [];
        
        $.each(view.execution.columns, function (i, e) {
            columns[columns.length] = {
                'data': this.id,
                'defaultContent': ''
            };
        });
        
        columns[columns.length] = {
            'data': 'ticket.url',
            'defaultContent': '',
            'render': function(data, type, text) {
                return "<a href='#' action='edit' class='on-default edit-row'><i class='fa fa-pencil'></i></a> <a href='#' action='delete' class='on-default remove-row'><i class='fa fa-trash-o'></i></a>";
            }
        };
        
        var dataTable = $(table).dataTable({
            'ordering': false,
            'searching': false,
            'processing': true,
            'serverSide': true,
            'ajax': {
                'url': view.url.execute,
                'type': 'POST',
                'data': function(d){
                    d.datatable = 1;
                },
                'dataFilter': function(data) {
                    var records = [];
                    data = $.parseJSON(data);
                    $.each(data.rows, function(i,e){
                        var record = this;
                        $.each(view.execution.columns, function(ci, ce) {
                            var cdata = record[this.id];
                            if(settings.fields[this.id]) {
                                cdata = getData(this.id, record, settings.fields[this.id], data);
                            }
                            record[this.id] = cdata;
                        }); 
                        records[records.length] = record;
                    });
                    var json = {
                        'draw' : eval(data.draw),
                        'data' : records,
                        'recordsTotal' : data.count,
                        'recordsFiltered' : data.count
                    };
                    return JSON.stringify( json );
                }
            },
            'columns': columns,
            'rowCallback' : settings.rowCallback
        }).api();
        
        $(table).on('refresh',{"dataTable": dataTable}, function(e){
            e.data.dataTable.ajax.reload();
        });
        
        $(table).on('click', 'tr a', function(e) {
            settings.rowOnclick(e, dataTable);
        });
        
        $(elem).data('initTable', true);
        
        
    }
    
    function getData(field, data, definition, full) {
        field = definition.column || field;
        var _data = dot(data, field);
        if(definition.mapped) {
            if(definition.mappedOwn) var object = dot(data, definition.object);
            else var object = dot(full, definition.object);
            
            if(false === definition.relationField) {
                _data = dot(object, _data + '.' + definition.mappedField);
            }
            else {
                for (var index in object) {
                    if(dot(object[index], definition.relationField) == _data) {
                        _data = dot(object[index], definition.mappedField);
                        break;
                    }
                }
            }
        }
        
        if(definition.render) {
            _data = definition.render(_data, full);
        }
        
        return _data;
    }
    
    function dot(data, key) {
        var notation = key.split('.');
        for(var i = 0; i < notation.length; i++) {
            if(data[notation[i]]) data = data[notation[i]];
            else {
                data = undefined;
                break;
            }
        }
        return data;
    }
    
    function _defaults() {
        return {
            'fields': {
                'assigned': {
                    'render': function (data, full) {
                        return moment(data).calendar();
                    }
                },
                'assignee': {
                    'column': 'assignee_id',
                    'mapped': true,
                    'mappedOwn': false,
                    'object': 'users',
                    'relationField': 'id',
                    'mappedField': 'name'
                },
                'due_date': {
                    'render': function (data, full) {
                        return moment(data).calendar();
                    }
                },
                'group': {
                    'column': 'group_id',
                    'mapped': true,
                    'mappedOwn': false,
                    'object': 'groups',
                    'relationField': 'id',
                    'mappedField': 'name'
                },
                'updated': {
                    'render': function (data, full) {
                        return moment(data).calendar();
                    }
                },
                'updated_assignee': {
                    'render': function (data, full) {
                        return moment(data).calendar();
                    }
                },
                'updated_requester': {
                    'render': function (data, full) {
                        return moment(data).calendar();
                    }
                },
                'organization': {
                    'column': 'organization_id',
                    'mapped': true,
                    'mappedOwn': false,
                    'object': 'organizations',
                    'relationField': 'id',
                    'mappedField': 'name'
                },
                'created': {
                    'render': function (data, full) {
                        return moment(data).calendar();
                    }
                },
                'requester': {
                    'column': 'requester_id',
                    'mapped': true,
                    'mappedOwn': false,
                    'object': 'users',
                    'relationField': 'id',
                    'mappedField': 'name'
                },
                'submitter': {
                    'column': 'submitter_id',
                    'mapped': true,
                    'mappedOwn': false,
                    'object': 'users',
                    'relationField': 'id',
                    'mappedField': 'name'
                }
            }
        };
    }
    
})(jQuery);