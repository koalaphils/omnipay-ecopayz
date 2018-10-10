(function ($) {
    var pluginName = "form";
    
    var Form = function (elem, options) {
        this.el = elem;
        this.$el = $(elem);
        this.url = options.action;
        this.setting = $.extend(true, this._defaults(), options);
        this.submited = false;
        
        this.init();
    };
    
    Form.prototype = {
        'init': function () {
            var form = this;
            form.$el.submit(function (e) {
                e.preventDefault();
            });
            this.$el.on('form:submit', function (e, btn) {
                if (form.submited === false) {
                    form.submited = true;
                    form.submit(e, btn);
                }
            });
        },
        'submit': function (e, btn) {
            var form = this;
            var data = form.$el.serialize() + "&" + $(btn).attr('name') + $(btn).attr('value');
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
                'ajax': this.ajax
            };
        },
        'ajax': {
            'expectedType': 'json',
            'success': function (data, textStatus, xhr) {},
            'complete': function (xhr, textStatus) {},
            'error': function (xhr, textStatus, errorThrown) {},
            'statusCode': {},
            'beforeSend': function (xhr, settings) {}
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
    };
}) (jQuery);