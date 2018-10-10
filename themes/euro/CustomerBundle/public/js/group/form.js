(function ($) {
    var pluginName = "form";
    
    var Form = function (elem, options) {
        this.el = elem;
        this.$el = $(elem);
        this.url = options.action;
        this.setting = $.extend(true, this._defaults(), options);
        this.gatewayId = 0;
        this.submited = false;
        
        this.init();
    };
    
    Form.prototype = {
        'init': function () {
            var form = this;
            this.$el.submit(function (e) {
                e.preventDefault();
                if (form.submited === false) {
                    form.submited = true;
                    form.submit(e);
                }
            });
            
            this.$el.on('click', this.setting.btnAddGateway, {'form': this}, this.addGateway);
            this.$el.on('click', this.setting.btnRemoveGateway, {'form': this}, this.removeGateway);
            
            for (var i in this.setting.gateways.data) {
                var gateway = this.setting.gateways.data[i];
                var prototype = form.setting.gateways.prototype;
                var replace = new RegExp(form.setting.gateways.name, 'g');
                prototype = prototype.replace(replace, form.gatewayId);
                var parsedHtml = $(prototype);
                var id = this.setting.gateways.conditions.id;
                id = id.replace(replace, form.gatewayId);
                $(parsedHtml).find('#' + id).val(gateway.conditions);
                form.gatewayId++;
                form.$el.find(form.setting.gateways.container).append(parsedHtml);
                form.$el.trigger('gateway:added', [gateway, parsedHtml]);
            }
        },
        'addGateway': function (e) {
            e.preventDefault();
            var form = e.data.form;
            var data = {'gateway': [],'condition': ''};
            form.setting.gateways.data.push(data);
            var prototype = form.setting.gateways.prototype;
            var replace = new RegExp(form.setting.gateways.name, 'g');
            prototype = prototype.replace(replace, form.gatewayId);
            var parsedHtml = $(prototype);
            form.gatewayId++;
            form.$el.find(form.setting.gateways.container).append(parsedHtml);
            form.$el.trigger('gateway:added', [data, parsedHtml]);
        },
        'removeGateway': function (e) {
            e.preventDefault();
            var form = e.data.form;
            var id = $(this).data('gateway');
            form.$el.find(id).remove();
        },
        'submit': function (e) {
            var form = this;
            var data = form.$el.serialize();
            $.ajax({
                'url': form.url,
                'context': form,
                'type': form.setting.method,
                'dataType': form.setting.ajax.expectedType,
                'success': form.setting.ajax.success,
                'complete': function (xhr, textStatus) {
                    this.submited = false;
                    this.setting.ajax.complete(xhr, textStatus);
                },
                'error': form.setting.ajax.error,
                'statusCode': form.setting.ajax.statusCode,
                'data': data,
                'beforeSend': form.setting.ajax.beforeSend
            });
        },
        'normalizeData': function (data) {
            var normalizedData = {};
            for (var i in data) {
                var name = data[i].name;
                var value = data[i].value;
                var fieldType = this.getFieldType(name);
                if (fieldType === 'single') {
                    normalizedData[name] = value;
                } else if (fieldType === 'multiple') {
                    if (typeof normalizedData[name] === 'undefined') {
                        normalizedData[name] = [];
                    }
                    normalizedData[name].push(value);
                }
            }
            
            return normalizedData;
        },
        '_defaults': function () {
            return {
                'method': 'POST',
                'ajax': this.ajax,
                'btnAddGateway': '.btn-add-gateway',
                'btnRemoveGateway': '.btn-remove-gateway',
                'gateways': {
                    'data': []
                }
            };
        },
        'ajax': {
            'expectedType': 'json',
            'success': function (data, textStatus, xhr) {},
            'complete': function (xhr, textStatus) {},
            'error': function (xhr, textStatus, errorThrown) {},
            'statusCode': {},
            'beforeSend': function (xhr, settings) {}
        },
        'fieldTypes': function () {
            return this.setting.dataTypes;
        },
        'getFieldType': function (fieldName) {
            return this.fieldTypes[fieldName];
        } 
    };
    
    $.fn[pluginName] = function (options) {
        var args = arguments;
        if(options === undefined || typeof options === 'object') {
            return this.each(function() {
                if(!$.data(this, 'plugin_' + pluginName)) {
                    $.data(this, 'plugin_' + pluginName, new Form(this, options));
                }
            });
        }
    }
}) (jQuery);