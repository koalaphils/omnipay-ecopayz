(function($, window, document, undefined){

    var pluginName = "mediaLibrary";

    var Library = function(elem, options) {
        this.listAjax = null;
        this.el = elem;
        this.$el = $(elem);
        this.options = $.extend(true, $.fn[pluginName].defaults, options);

        this.files = [];

        this.selectedFile = null;

        this.init();
    };

    Library.prototype = {
        init: function() {
            this.$el.addClass('mediaLibrary');
            this.$el.html('<div class="list"></div>');

            this._loadList();

        },
        destroy: function() {
            this.$el.removeData();
        },
        render: function() {
            this.$el.trigger('beforeRender', this);
            for(var i = 0; i < this.files.length; i++) {
                this.files[i].render();
            }
            this.$el.trigger('afterRender', this);
        },
        clearList: function() {
            this.files = [];
            this.$el.find('.list').html('');
        },
        refreshList: function() {
            this.clearList();
            this._loadList();
            this.render();
        },
        _loadList: function() {
            var library = this;

            if (this.listAjax !== null) {
                this.listAjax.abort();
                this.listAjax = null;
            }

            this.listAjax = $.ajax({
                'cache': false,
                url: this.options.url.list,
                headers: this.options.headers,
                type: 'GET',
                success: function(data) {
                    $.each(data, function(i,e) {
                        library.addFile(this, i, library.options.file.autoRender);
                    });
                    if(library.options.autoRender) {
                        library.render();
                    }
                }
            });
        },
        addFile: function(info, index, render = true) {
            var index = index;
            var file = new File(this, index, info, this.options.file);
            this.files[index] = file;

            if(render) {
                file.render();
            }
        },
        setFileInfo: function(index, infos) {
            this.files[index].setInfos(infos);
        },
        getFile: function(index) {
            return this.files[index];
        },
        getSelectedFile: function() {
            return this.selectedFile;
        }
    };

    var File = function (library, index, file, options) {
        this.library = library;
        this.index = index;
        this.fileInfo = file;
        this.options = options;

        this.init();
    }

    File.prototype = {
        init: function() {
            this.view = this._loadTemplate();
            this.initEvent();
        },
        initEvent: function() {
            var file = this;
            $(this.view).on('click', {"file": file}, function(event) {
                if(event.data.file.library.selectedFile != null)
                    $(event.data.file.library.selectedFile.view).removeClass('selected');
                event.data.file.library.selectedFile = event.data.file;
                $(event.data.file.view).addClass('selected');
                event.data.file.library.$el.trigger('fileClick', event.data.file);
            });
        },
        render: function() {
            this.library.$el.trigger('fileBeforeRender', this);
            this.library.$el.find('.list').append(this.view);
            this.library.$el.trigger('fileAfterRender', this);
        },
        setInfos: function(infos) {
            var file = this;
            $.each(infos, function(i,e) {
                file.setInfo(i, this);
            });
        },
        setInfo: function(info, value) {
            this.fileInfo[info] = value;
            if(info == 'title') value = '<i class="fa fa-file"></i>&nbsp;&nbsp;' + value;
            $(this.view).find('[data-info="'+ info +'"]').html(value);
        },
        getType: function() {
            if(this.library.options.imgExts.indexOf(this.fileInfo.ext.toLowerCase()) != -1) {
                return "image";
            }
            return "file";
        },
        getFileType: function() {
            if(this.library.options.types[this.fileInfo.ext.toLowerCase()]) {
                return this.library.options.types[this.fileInfo.ext.toLowerCase()];
            }

            if(this.getType() == 'image'){
                return 'Image';
            }

            return 'Unknown File';
        },
        getInfo: function(info) {
            if(info == 'type') {
                return this.getFileType();
            } else if(info == 'title') {
                return this.fileInfo['title'] != '' ? this.fileInfo['title'] : this.fileInfo.file;
            }

            return this.fileInfo[info];
        },
        getIcon: function() {
            if(this.library.options.icons[this.fileInfo.ext.toLowerCase()]) {
                return this.library.options.icons[this.fileInfo.ext.toLowerCase()];
            }

            if(this.getType() == 'image'){
                return {'icon': 'fa fa-file-image-o', 'style': {'color': '#f05050'}};
            }

            return {'icon': 'fa fa-file', 'style': {'color': '#777777'}};
        },
        destroy: function() {
            delete this.library.files[this.index];
            $(this.view).remove();
        },
        _loadTemplate: function() {
            var temp = $(this.options.template.main);

            if(this.getType() == 'image') {
                temp.find('.icon-container').append(this.options.template.img);
                temp.find('img').attr('src',this.fileInfo.route.render);
            } else {
                temp.find('.icon-container').append(this.options.template.icon);
                temp.find('.icon-container .icon').addClass(this.getIcon().icon);
                temp.find('.icon-container .icon').css(this.getIcon().style);
            }

            temp.find('.media-title').html(this.fileInfo.title);
            temp.attr('id', 'media_' + this.index);

            return temp;
        }
    };

    $.fn[pluginName] = function (options) {
        var args = arguments;
        if(options === undefined || typeof options === 'object') {
            return this.each(function() {
                if(!$.data(this, 'plugin_' + pluginName)) {
                    $.data(this, 'plugin_' + pluginName, new Library(this, options));
                }
            });
        } else if(typeof options === 'string' && options[0] !== '_' && options !== 'init') {
            if ($.inArray(options, $.fn[pluginName].getters) != -1) {
                var instance = $.data(this[0], 'plugin_' + pluginName);
                return instance[options].apply(instance, Array.prototype.slice.call(args, 1));
            } else if ($.inArray(options, $.fn[pluginName].setters) != -1) {
                var instance = $.data(this[0], 'plugin_' + pluginName);
                return instance[options].apply(instance, Array.prototype.slice.call(args, 1));
            } else {
                return this.each(function() {
                    var instance = $.data(this, 'plugin_' + pluginName);

                    if (instance instanceof Library && $.fn[pluginName].methods[options] && typeof instance[$.fn[pluginName].methods[options]] === 'function') {
                        instance[$.fn[pluginName].methods[options]].apply(instance, Array.prototype.slice.call(args, 1));
                    }
                });
            }
        }
    }

    $.fn[pluginName].getters = ['getFile', 'getSelectedFile'];
    $.fn[pluginName].setters = ['setFileInfo'];

    $.fn[pluginName].methods = {
        'refresh': 'refreshList',
        'addFile': 'addFile'
    };

    $.fn[pluginName].defaults = {
        'file': {
            'template': {
                'main': '<div class="media-file"><div class="icon-container"></div><div class="media-info"><div class="media-title ellipsis" data-info="title"></div></div></div>',
                'icon': '<i class="icon"></i>',
                'img': '<div><img src=""/></div>'
            },
            autoRender: true
        },
        'headers': {},
        'imgExts': ['png', 'jpg', 'jpeg'],
        'autoRender': true,
        'icons': {},
        'types': {}
    };



})(jQuery, window, document);
