$(function() {
    $.fn.markdown.defaults.buttons[0][1] = {
        name: 'groupLink',
        data: [{
          name: 'cmdUrl',
          title: 'URL/Link',
          hotkey: 'Ctrl+L',
          icon: { glyph: 'glyphicon glyphicon-link', fa: 'fa fa-link', 'fa-3': 'icon-link' },
          callback: function(e){
            // Give [] surround the selection and prepend the link
            var chunk, cursor, selected = e.getSelection(), content = e.getContent(), link;

            if (selected.length === 0) {
              // Give extra word
              chunk = e.__localize('text');
            } else {
              chunk = selected.text;
            }

            // transform selection and set the cursor into chunked text
            e.replaceSelection('['+chunk+'](http://)');
            cursor = selected.start+1;

            // Set the cursor
            e.setSelection(cursor,cursor+chunk.length);
          }
        },{
          name: 'cmdImage',
          title: 'Image',
          hotkey: 'Ctrl+G',
          icon: { glyph: 'glyphicon glyphicon-picture', fa: 'fa fa-picture-o', 'fa-3': 'icon-picture' },
          callback: function(e){
            // Give ![] surround the selection and prepend the image link
            var chunk, cursor, selected = e.getSelection(), content = e.getContent(), link;

            if (selected.length === 0) {
              // Give extra word
              chunk = e.__localize('text');
            } else {
              chunk = selected.text;
            }
            e.replaceSelection('!['+chunk+'](http:// "'+e.__localize('title')+'")');
            cursor = selected.start+2;

            // Set the next tab
            e.setNextTab(e.__localize('title'));

            // Set the cursor
            e.setSelection(cursor,cursor+chunk.length);
          }
        }]
      };
});