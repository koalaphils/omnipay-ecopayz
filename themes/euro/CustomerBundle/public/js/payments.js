class PaymentOptionList
{
    constructor(elem, options)
    {
        this.elem = elem;
        this.$elem = $(elem);
        this.options = $.extend(true, this.defaults, options);

        this.items = {};
        this.template = new XslTemplate(options.template, false);
        this.selectedItem = null;
        this._initItems();
        this._initEvents();

        this.render();
    }

    _defaults()
    {
        return {
            'events': {
                'itemUnselected': function (list, item) {},
                'itemSelected': function (list, item) {}
            }
        };
    }

    _initItems()
    {
        for (var i in this.options.items) {
            var item = this.options.items[i];
            this.items[item.id] = new PaymentOptionItem(item, this.options.paymentOptions[item.type], item.id, this.template, {'class': this.options.itemClass});
        }
    }

    _initEvents()
    {
        for (var i in this.items) {
            var item = this.items[i];
            $(item.view).on('click', {"list": this, "item": item}, function (event) {
                if($(event.data.item.view).hasClass('selected')) {
                    var item = event.data.list.selectedItem;
                    event.data.list.removeSelected();
                    event.data.list.options.events.itemUnselected(event.data.list, item);
                } else {
                    event.data.list.setSelected(event.data.item);
                    event.data.list.options.events.itemSelected(event.data.list, event.data.list.selectedItem);
                }
            });
        }
    }

    setItem(item, index)
    {
        if(typeof this.items[item.id] == 'undefined') {
            this.items[item.id] = new PaymentOptionItem(item, this.options.paymentOptions[item.type], item.id, this.template, {'class': this.options.itemClass});
            this.renderItem(this.items[item.id]);
        } else {
            this.items[item.id].update(item, this.options.paymentOptions[item.type], item.id, this.template);
        }
    }

    setSelected(selected) {
        this.removeSelected();
        this.selectedItem = selected;
        this.selectedItem.view.trigger('select');
    }

    removeSelected() {
        if(this.selectedItem != null) {
            this.selectedItem.view.trigger('unselect');
        }
        this.selectedItem = null;
    }

    renderItem(item)
    {
        this.$elem.append(item.view);
    }

    render()
    {
        for (var i in this.items) {
            var item = this.items[i];
            this.$elem.append(item.view);
        }
    }
}

class PaymentOptionItem
{
    constructor(item, type, index, template, options)
    {
        this.id = item.id;
        this.index = index;
        this.item = item;
        this.options = options;
        this.type = type;
        this.xml = json2Xml(this.item, 'item');
        this._loadView(template);
        this._initEvents();
    }

    update(item, type, index, template)
    {
        this.id = item.id;
        this.index = index;
        this.item = item;
        this.type = type;
        this.xml = json2Xml(this.item, 'item');

        this.refresh(template)
    }

    refresh(template)
    {
        var temp = $('<div></div>').append(template.apply(this.xml)).html();
        $(this.view).html(temp);
    }

    _loadView(template)
    {
        var temp = $('<div></div>').append(template.apply(this.xml)).html();
        this.view = $('<div class="' + this.options.class + '"></div>').html(temp);
    }

    _initEvents()
    {
        this.view.on('unselect', function(event) {
            $(this).removeClass('selected');
        });

        this.view.on('select', function(event) {
            $(this).addClass('selected');
        });
    }

    getFieldValue(field) {
        return this.item.fields[field];
    }
}