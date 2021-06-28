MediaLibrary = new (function ($) {
    var library;
    var template = {
        'main': '<div class="media-file"><div class="icon-container"></div></div>',
        'icon': '<i class="fa fa-file"></i>',
        'img': '<div><img src=""/></div>'
    }
    var imgExts = ['png', 'jpg', 'jpeg'];
    var searchTimeout;

    this.getType = function(ext) {
        if(imgExts.indexOf(ext) != -1) {
            return "image";
        }

        return "file";
    }

    this.init = function (options) {
        library = this;
        this.options = options;
        $(this.options.modal).on('shown.bs.modal', onShowModal);
        $(this.options.modal).on('click','.media-file', function(e) {
            var target = this;
            $(library.options.modal).find('.media-file.selected').each(function() {
                if(target != this) $(this).removeClass('selected');
            });
            $(target).toggleClass('selected');
            showDetails();
        });
        $(library.options.modal).find('.media-search').keyup(function(e) {
            clearTimeout(library.searchTimeout);
            library.searchTimeout = setTimeout(function(){ library.load() }, 2000);
        });
    }

    function showDetails() {
        var file = $(library.options.modal).find('.media-file.selected').data();
        if(file) {
            $(library.options.modal).find('.media-file-none').addClass('hide');
            $(library.options.modal).find('.media-file-selected').removeClass('hide');
            $(library.options.modal).find('.media-details .media-detail-title').html(file.filename);
            $(library.options.modal).find('.media-details .media-detail-size').html(file.size);
            $(library.options.modal).find('.media-details .media-detail-url a').attr('href',file.route.render);
            $(library.options.modal).find('.media-details .media-detail-modified').html(moment(file.lastModified).fromNow());

            if(imgExts.indexOf(file.ext) != -1) {
                $(library.options.modal).find('.media-selected-thumb').removeClass('hide');
                $(library.options.modal).find('.media-selected-thumb img').attr('src',file.route.render);
            } else {
                $(library.options.modal).find('.media-selected-thumb').addClass('hide');
                $(library.options.modal).find('.media-selected-thumb img').removeAttr('src');
            }

        } else {
            $(library.options.modal).find('.media-file-none').removeClass('hide');
            $(library.options.modal).find('.media-file-selected').addClass('hide');
        }
    }

    this.select = function(input, callback) {
        $(this.options.modal).modal('show');
        $(this.options.modal).find('.media-library-select-btn').click(function(e){
            var selected = $(library.options.modal).find('.media-file.selected').data();
            callback(selected, input);
        });
    }

    this.load = function() {
        $(library.options.modal).find('.media-list').html('');
        $.ajax({
            'url': library.options.url,
            'data': {'search': $(library.options.modal).find('.media-search').val()},
            'type': 'POST',
            'success': function(data) {
                var i = 0;
                $.each(data,function(i,e) {
                    var temp = $(template.main);
                    $(library.options.modal).find('.media-list').append(temp);
                    if(imgExts.indexOf(this.ext) != -1) {
                        temp.find('.icon-container').append(template.img);
                        temp.find('img').attr('src',this.route.render);
                    } else {
                        temp.find('.icon-container').append(template.icon);
                    }
                    temp.attr('id', 'media_file_' + i);
                    this.type = library.getType(this.ext);
                    temp.data(this);
                    i++;
                });
            }
        });
    }

    function onShowModal(e) {
        $(library.options.modal).find('.media-file-selected').addClass('hide');
        $(library.options.modal).find('.media-file-none').removeClass('hide');
        library.load();
    }

    this.close = function() {
        $(this.options.modal).modal('hide');
    }

    this.changeOption = function(option, value) {
        this.options[option] = value;
    }

})(jQuery);