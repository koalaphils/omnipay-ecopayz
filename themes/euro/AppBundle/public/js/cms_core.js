String.prototype.getHashCode = (function(id) {
    return function() {
        if (!this.hashCode) {
            this.hashCode = '<hash|#' + (id++) + '>';
        }
        return this.hashCode;
    }
}(0));

String.prototype.ellipsis = function(n) {
    return this.substr(0, n-1) + (this.length > n ? '&hellip;' :'');
};

var objs = {};

/**
 * Genearte id for object
 * @param {Object} obj
 * @returns {Number}
 */
var generateId = function (obj) {
    var id = null;
    do {
        id = Math.random();
        id = id.toString(36).substr(2, 9);
        if (!(id in objs))  {
            objs[id] = obj;
            break;
        }
    } while (true);

    return id;
}

function objectExtend(obj){
    obj.array_get = function(key, def = ''){
        var data = this;
        var arr = key.split('.');

        for(var i = 0; i < arr.length; i++){
            data = data[arr[i]] != undefined ? data[arr[i]] : def;
        }

        return data;
    };
};

function notification(title, message, type, position) {
    title = title || '';
    message = message || '';
    type = type || 'custom';
    position = position || 'top center';
    $.Notification.notify(type, position, title, message);
}

window.alert2 = function(message, title, options, action) {
    var _defaults = {
        'type': 'info'
    };
    message = message || "";
    title = title || "Alert";
    options = options || {};
    action = action || function(isConfirm) {};
    options = $.extend(true, _defaults, options);
    options = $.extend(true,options,{'title': title, 'text': message});
    swal(options,action);
};

window.confirm2 = function(message, title, options, action) {
    var _defaults = {
        'type': 'warning',
        'showCancelButton': true,
        'closeOnConfirm': false,
        'showLoaderOnConfirm': true,
        'confirmButtonClass': 'btn-warning btn-md',
        'cancelButtonClass': 'btn-inverse btn-md'
    };
    message = message || "";
    title = title || "Confirm";
    options = options || {};
    action = action || function(isConfirm) {};
    options = $.extend(true, _defaults, options);
    options = $.extend(true,options,{'title': title, 'text': message});
    swal(options,action);
};

$(function() {
    $(document).bind('ajaxComplete',function(event, xhr, settings) {
        if($(settings).data('global-ajax-complete') === false) return;
        if(settings.globalAjaxComplete === false) return;

        if(xhr.responseJSON && xhr.responseJSON.__notifications) {
            $.each(xhr.responseJSON.__notifications, function(i, e) {
                var title = this.title || '';
                var message = this.message || '';
                var type = this.type || 'custom';
                var position = this.position || 'top center';

                notification(title, message, type, position);
            });
        }
    });

    $(document).bind('ajaxError', function(event, xhr, settings, thrownError) {
        if(xhr.readyState == 0 && xhr.status == 0 && (xhr.statusText == 'abort' || xhr.statusText == 'error')) {
            return;
        }

        if($(settings).data('global-ajax-error') === false) return;
        if(settings.globalAjaxError === false) return;

        var message = "Something went wrong!!! Pls. contact the support.";
        var title = xhr.status + ": " + thrownError;

        if($(settings).data('global-error-message') !== false) {
            message = $(settings).data('global-error-message') || message;
        } else if(xhr.responseJSON.__errors){
            message = xhr.responseJSON.__error.message || message;
        }

        if($(settings).data('global-error-title') !== false) {
            title = $(settings).data('global-error-title')  || title;
        } else if(xhr.responseJSON.__errors){
            title = xhr.responseJSON.__error.title || title;
        }

        if(xhr.getResponseHeader('X-Debug-Token') != undefined) {
            confirm2(message, title, {
                'type': 'error',
                'showCancelButton': true,
                'confirmButtonText': "Show Profiler",
                'showLoaderOnConfirm': false
            }, function(isConfirm) {
                if(isConfirm)
                    window.open(xhr.getResponseHeader('X-Debug-Token-Link'), "_blank");
            });
        } else alert2(message, title);
    });
});

window.saveUserPreferences = function (preferences)
{
    $.ajax({
        'url': Global.links.user.preferences_save,
        'type': 'POST',
        'data': {'preferences': preferences}
    });
}

function isScriptExists(url) {
    var scripts = document.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
        if (scripts[i].src == url) {
            return true;
        }
    }

    return false;
}

function generateScript(url) {
    var body = document.getElementsByTagName('body')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;

    return script;
}

function formHasChanged(form)
{
    return $(form).data('initialSate') != $(form).serialize();
}