// Document Form
$(function() {

    $('#documentList').on('fileAfterRender', function(event, file) {
        $(file.view).find('.ellipsis').dotdotdot({watch:true, wrap:'letter',fallbackToLetter:true});
    });

    $('#documentList').on('fileClick', function(event, file) {
        $('.document-selected-info-value[data-info="description"]').editable('destroy');
        $('.document-selected-title a').editable('destroy');

        $('.document-details-container .document-file-none').addClass('hide');
        $('.document-details-container .document-file-selected ').removeClass('hide');

        $('.document-selected-title a').html(file.getInfo('title'));
        if(file.getType() == 'image') {
            $('.document-selected-thumb .icon').addClass('hide');
            $('.document-selected-thumb .img').removeClass('hide');
            $('.document-selected-thumb img').attr('src', file.getInfo('route').render);
            $('.document-selected-thumb').removeClass('icon-thumb');
        } else {
            $('.document-selected-thumb .icon').removeClass('hide');
            $('.document-selected-thumb .img').addClass('hide');
            $('.document-selected-thumb .icon i').attr('class', file.getIcon().icon);
            $('.document-selected-thumb .icon i').css(file.getIcon().style);
            $('.document-selected-thumb').addClass('icon-thumb');
        }
        $('.document-selected-info-value').each(function(i) {
            var info = $(this).data('info');
            $(this).html(file.getInfo(info));
        });

        $('.document-selected-info-value[data-info="description"]').editable({
            'mode': 'inline',
            'pk': file.index,
            'name': 'description',
            'url': url.document_save,
            'success': function(response, newValue) {
                var infos = {};
                infos[response.info] = response.value;
                $('#documentList').mediaLibrary('setFileInfo', response.index, infos);
                $($('#documentList').mediaLibrary('getFile', response.index).view).find('.ellipsis').dotdotdot({watch:true, wrap:'letter',fallbackToLetter:true});
            }
        });

        $('.document-selected-title a').editable({
            'mode': 'inline',
            'pk': file.index,
            'name': 'title',
            'url': url.document_save,
            'success': function(response, newValue) {
                var infos = {};
                infos[response.info] = response.value;
                var _file = $('#documentList').mediaLibrary('getFile', response.index);
                $(_file.view).find('.ellipsis').trigger('destroy');
                $('#documentList').mediaLibrary('setFileInfo', response.index, infos);

                $(_file.view).find('.ellipsis').dotdotdot({watch:true, wrap:'letter',fallbackToLetter:true});
               // $($('#documentList').mediaLibrary('getFile', response.index).view).find('.ellipsis').dotdotdot({watch:true, wrap:'letter',fallbackToLetter:true});
            }
        });
    });

    $('#documentList').mediaLibrary({
        'url': {
            'list': url.document_list
        },
        'file': {
            'autoRender': true
        },
        'autoRender': false,
        'icons': {
            'pptx': {'icon': 'fa fa-file-powerpoint-o', 'style': {'color': 'orange'}},
            'docx': {'icon': 'fa fa-file-word-o', 'style': {'color': 'blue'}},
            'csv': {'icon': 'fa fa-file-excel-o', 'style': {'color': 'green'}}
        },
        'types': {
            'docx': 'Word Document',
            'pptx': 'PowerPoint Document',
            'png': 'Image (PNG)',
            'csv': 'CSV File'
        }
    });

    Dropzone.options.fileUpload = {
        'autoProcessQueue': true,
        'addRemoveLinks': true,
        'init': function() {
            var fileUploadZone = this;
        },
        'success': function(e, data) {
            $('#documentList').mediaLibrary('addFile', data.file, data.index, true);
        }
    };
});

function loadgallery() {
    $('#documentList').mediaLibrary('refresh');
}
 var paymentOptionList = null;
$(function() {
    paymentOptionList = new PaymentOptionList('.paymentOptions .list .payment-list', {
        'template': paymentOptionsTemplate,
        'paymentOptions': paymentOptions,
        'items': customerPaymentOptions,
        'itemClass': 'col-md-4',
        'events': {
            'itemUnselected': function (list, item) {
            },
            'itemSelected': function (list, item) {
                $('#customerPaymentOptionModal form').data('payment-option', item);
                $('#customerPaymentOptionModal').modal('show');
            }
        }
    });

    $('#btn-add-payment').click(function() {
        paymentOptionList.removeSelected();
    });
 });