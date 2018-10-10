(function($){
    
    var methods = {
        'refresh': function() {
            
        }
    };
    
    $.fn.documentList = function(options, params){
        var settings = options;
        params = params || [];
        return this.each(function(){
            var elem = $(this);
            settings = $.extend(true,_defaults(),options);
            init(elem, settings);
        });
    };
    
    function init(elem, settings) {
        initList(elem, settings);
        
        $(elem).on('refresh', {'elem': elem, 'settings': settings},function(event) {
            $(event.data.elem).find('.list').html('');
            initList(event.data.elem, event.data.settings);
        });
        
    }
    
    function initList(elem, settings) {
        $.ajax({
            'url': settings.list_url,
            'type': 'GET',
            'success': function(data) {
                var i = 0;
                $.each(data,function(i,e) {
                    
                    i++;
                });
            },
            'complete': function() {
                $('.ellipsis').dotdotdot({watch:true, wrap:'letter',fallbackToLetter:true});
            }
        });
    }
    
    function addFile(event, fileInfo) {
        var elem = event.data.elem;
        var settings = event.data.settings;
        var temp = $(settings.template.main);
        
        $(elem).find('.list').append(temp);
        $(elem).find('.list').append(temp);
        
        if(settings.imgExts.indexOf(this.ext) != -1) {
            temp.find('.icon-container').append(settings.template.img);
            temp.find('img').attr('src',fileInfo.route.render);
        } else {
            temp.find('.icon-container').append(settings.template.icon);
        }
        
        temp.find('.document-title').html('<i class="fa fa-file"></i>&nbsp;&nbsp;'+fileInfo.title);
        temp.attr('id', 'document_' + i);
        this.type = getType(fileInfo.ext, settings.imgExts);
        temp.data(fileInfo);
    }
    
    function getType(ext, imgExts) {
        if(imgExts.indexOf(ext) != -1) {
            return "image";
        }
        
        return "file";
    }
    
    function _defaults() {
        return {
            'template': {
                'main': '<div class="document-file"><div class="icon-container"></div><div class="document-info"><div class="document-title ellipsis"></div></div></div>',
                'icon': '<i class="fa fa-file"></i>',
                'img': '<div><img src=""/></div>'
            },
            'imgExts': ['png', 'jpg', 'jpeg']
        };
    };
})(jQuery);