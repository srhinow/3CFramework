/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2011
 * @copyright  MEN AT WORK 2011
 * @package    Backend
 * @license    LGPL
 * @filesource
 */


/**
 * Class AjaxRequestMemory
 *
 * Provide methods to handle Ajax requests.
 * @copyright  Leo Feyer 2005-2011
 * @copyright  MEN AT WORK 2011
 * @package    Backend
 */
var AjaxRequestMemory =
{

    /**
	 * Toggle the file tree (input field)
	 * @param object
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param integer
	 * @return boolean
	 */
    toggleFiletree: function (el, id, folder, field, name, level)
    {
        el.blur();
        var item = $(id);
        var image = $(el).getFirst();

        if (item)
        {
            if (item.getStyle('display') == 'none')
            {
                item.setStyle('display', 'inline');
                image.src = image.src.replace('folPlus.gif', 'folMinus.gif');
                $(el).title = CONTAO_COLLAPSE;
                new Request.Contao({
                    'field':el
                }).post({
                    'action':'toggleFiletreeMemory', 
                    'id':id, 
                    'state':1, 
                    'REQUEST_TOKEN':REQUEST_TOKEN
                });
            }
            else
            {
                item.setStyle('display', 'none');
                image.src = image.src.replace('folMinus.gif', 'folPlus.gif');
                $(el).title = CONTAO_EXPAND;
                new Request.Contao({
                    'field':el
                }).post({
                    'action':'toggleFiletreeMemory', 
                    'id':id, 
                    'state':0, 
                    'REQUEST_TOKEN':REQUEST_TOKEN
                });
            }

            return false;
        }

        new Request.Contao(
        {
            'field': el,
            onRequest: AjaxRequest.displayBox('Loading data …'),
            onSuccess: function(txt, json)
            {
                var ul = new Element('ul');

                ul.addClass('level_' + level);
                ul.set('html', txt);

                item = new Element('li');

                item.addClass('parent');
                item.setProperty('id', id);
                item.setStyle('display', 'inline');

                ul.injectInside(item);
                item.injectAfter($(el).getParent('li'));

                $(el).title = CONTAO_COLLAPSE;
                image.src = image.src.replace('folPlus.gif', 'folMinus.gif');
                AjaxRequest.hideBox();

                // HOOK
                window.fireEvent('ajax_change');
            }
        }).post({
            'action':'loadFiletreeMemory', 
            'id':id, 
            'level':level, 
            'folder':folder, 
            'field':field, 
            'name':name, 
            'state':1, 
            'REQUEST_TOKEN':REQUEST_TOKEN
        });

        return false;
    },
	
	
    /**
	 * Reload all file trees (input field)
	 */
    reloadFiletrees: function ()
    {
        $$('.filetree').each(function(el)
        {
            var name = el.id;
            var field = name.replace(/_[0-9]+$/, '');

            new Request.Contao(
            {
                onRequest: AjaxRequest.displayBox('Loading data …'),
                onSuccess: function(txt, json)
                {
                    // Preserve the "reset selection" entry
                    var ul = $(el.id + '_parent').getFirst('ul');
                    var li = ul.getLast('li');
                    ul.set('html', txt);
                    li.inject(ul);
                    AjaxRequest.hideBox();

                    // HOOK
                    window.fireEvent('ajax_change');
                }
            }).post({
                'action':'loadFiletree', 
                'field':field, 
                'name':name, 
                'REQUEST_TOKEN':REQUEST_TOKEN
            });
        });
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