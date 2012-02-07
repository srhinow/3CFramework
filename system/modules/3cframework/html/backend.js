var DCMemoryAjaxRequest =
{
    toggleDCMemorySubpalette: function (el, id, field)
    {
        el.blur();
        var item = $(id);

        if (item)
        {
            if (!el.value)
            {
                el.value = 1;
                el.checked = 'checked';
                item.setStyle('display', 'block');
                new Request({
                    url: window.location.href, 
                    data: 'isAjax=1&action=toggleDCMemorySubpalette&id=' + id + '&field=' + field + '&state=1'
                    }).send();
            }
            else
            {
                el.value = '';
                el.checked = '';
                item.setStyle('display', 'none');
                new Request({
                    url: window.location.href, 
                    data: 'isAjax=1&action=toggleDCMemorySubpalette&id=' + id + '&field=' + field + '&state=0'
                    }).send();
            }

            return;
        }

        new Request.Mixed(
        {
            url: window.location.href,
            data: 'isAjax=1&action=toggleDCMemorySubpalette&id=' + id + '&field=' + field + '&load=1&state=1',
            onRequest: AjaxRequest.displayBox('Loading data …'),

            onComplete: function(txt, xml, js)
            {
                item = new Element('div');
                item.setProperty('id', id);
                item.set('html', txt);
                item.injectAfter($(el).getParent('div').getParent('div'));

                if (js)
                {
                    $exec(js);
                }

                el.value = 1;
                el.checked = 'checked';
                item.setStyle('display', 'block');

                AjaxRequest.hideBox();

                Backend.hideTreeBody();
                Backend.addInteractiveHelp();
                Backend.addColorPicker();

                // HOOK
                window.fireEvent('subpalette');
            }
        }).send();
    },
    
    /**
     * Toggle subpalettes (edit mode)
     * @param object
     * @param string
     * @param string
     */
    toggleSubpalette: function (el, id, field)
    {
        el.blur();
        var item = $(id);

        if (item)
        {
            if (!el.value)
            {
                el.value = 1;
                el.checked = 'checked';
                item.setStyle('display', 'block');
                new Request.Contao({
                    'field':el
                }).post({
                    'action':'DcMemToggleSubpalette', 
                    'id':id, 
                    'field':field, 
                    'state':1, 
                    'REQUEST_TOKEN':REQUEST_TOKEN
                });
            }
            else
            {
                el.value = '';
                el.checked = '';
                item.setStyle('display', 'none');
                new Request.Contao({
                    'field':el
                }).post({
                    'action':'DcMemToggleSubpalette', 
                    'id':id, 
                    'field':field, 
                    'state':0, 
                    'REQUEST_TOKEN':REQUEST_TOKEN
                });
            }

            return;
        }

        new Request.Mixed(
        {
            'field': el,
            onRequest: AjaxRequest.displayBox('Loading data …'),
            onSuccess: function(txt, js, json)
            {
                item = new Element('div');
                item.setProperty('id', id);
                item.set('html', txt);
                item.injectAfter($(el).getParent('div').getParent('div'));

                if (js)
                {
                    $exec(js);
                }

                el.value = 1;
                el.checked = 'checked';
                item.setStyle('display', 'block');

                AjaxRequest.hideBox();

                Backend.hideTreeBody();
                Backend.addInteractiveHelp();
                Backend.addColorPicker();

                // HOOK
                window.fireEvent('subpalette'); // Backwards compatibility
                window.fireEvent('ajax_change');
            }
        }).post({
            'action':'DcMemToggleSubpalette', 
            'id':id, 
            'field':field, 
            'load':1, 
            'state':1, 
            'REQUEST_TOKEN':REQUEST_TOKEN
        });
    }
};
