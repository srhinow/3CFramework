<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
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
 * @copyright  Stefan Lindecke 2011
 * @copyright  MEN AT WORK 2012 
 * @author     Stefan Lindecke  <stefan@ktrion.de>
 * @package    3cframework
 * @license    GNU/LGPL 
 * @filesource
 */
class FrameworkHelper extends Backend
{
    /**
     * Ajax actions that do not require a data container object
     */
    public function executePreActions($strAction)
    {
        switch ($strAction)
        {
            // Toggle nodes of the file or page tree
            case 'toggleFiletreeMemory':
                $this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', $this->Input->post('id'));
                $this->strAjaxKey = str_replace('_' . $this->strAjaxId, '', $this->Input->post('id'));

                if ($this->Input->get('act') == 'editAll')
                {
                    $this->strAjaxKey = preg_replace('/(.*)_[0-9a-zA-Z]+$/i', '$1', $this->strAjaxKey);
                    $this->strAjaxName = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', $this->Input->post('name'));
                }

                $nodes                   = $this->Session->get($this->strAjaxKey);
                $nodes[$this->strAjaxId] = intval($this->Input->post('state'));
                $this->Session->set($this->strAjaxKey, $nodes);

                echo json_encode(array('token' => REQUEST_TOKEN));
                exit;
                break;

            // Load nodes of the file or page tree
            case 'loadFiletreeMemory':
                $this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', $this->Input->post('id'));
                $this->strAjaxKey = str_replace('_' . $this->strAjaxId, '', $this->Input->post('id'));

                if ($this->Input->get('act') == 'editAll')
                {
                    $this->strAjaxKey = preg_replace('/(.*)_[0-9a-zA-Z]+$/i', '$1', $this->strAjaxKey);
                    $this->strAjaxName = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', $this->Input->post('name'));
                }

                $nodes                   = $this->Session->get($this->strAjaxKey);
                $nodes[$this->strAjaxId] = intval($this->Input->post('state'));
                $this->Session->set($this->strAjaxKey, $nodes);
                break;
        }
    }

    /**
     * Ajax actions that do require a data container object
     * @param object
     */
    public function executePostActions($strAction, DataContainer $dc)
    {
        //header('Content-Type: text/html; charset=' . $GLOBALS['TL_CONFIG']['characterSet']);
        switch ($strAction)
        {
            // Load subpalette
            case "DcMemToggleSubpalette":
                if ($dc instanceof DC_Memory)
                {
                    if ($this->Input->get('act') == 'editAll')
                    {
                        $this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', $this->Input->post('id'));

                        if ($this->Input->post('load'))
                        {
                            echo json_encode(array(
                                'content' => $dc->editAll($this->strAjaxId, $this->Input->post('id')),
                                'token' => REQUEST_TOKEN
                            ));
                            exit;
                            break;
                        }
                    }
                    else
                    {
                        if ($this->Input->post('load'))
                        {
                            echo json_encode(array(
                                'content' => $dc->edit(false, $this->Input->post('id')),
                                'token' => REQUEST_TOKEN
                            ));
                            exit;
                            break;
                        }
                    }
                }
                break;

            // Load nodes of the file tree
            case 'loadFiletreeMemory':
                $arrData['strTable'] = $dc->table;
                $arrData['id']       = strlen($this->strAjaxName) ? $this->strAjaxName : $dc->id;
                $arrData['name']     = $this->Input->post('name');

                $objWidget = new $GLOBALS['BE_FFL']['fileTreeMemory']($arrData, $dc);

                // Load a particular node
                if ($this->Input->post('folder', true) != '')
                {
                    echo json_encode(array
                        (
                        'content' => $objWidget->generateAjax($this->Input->post('folder', true), $this->Input->post('field'), intval($this->Input->post('level'))),
                        'token' => REQUEST_TOKEN
                    ));
                    exit;
                    break;
                }

                // Reload the whole tree
                $this->import('BackendUser', 'User');
                $tree = '';

                // Set a custom path
                if (strlen($GLOBALS['TL_DCA'][$dc->table]['fields'][$this->Input->post('field')]['eval']['path']))
                {
                    $tree = $objWidget->generateAjax($GLOBALS['TL_DCA'][$dc->table]['fields'][$this->Input->post('field')]['eval']['path'], $this->Input->post('field'), intval($this->Input->post('level')));
                }

                // Start from root
                elseif ($this->User->isAdmin)
                {
                    $tree = $objWidget->generateAjax($GLOBALS['TL_CONFIG']['uploadPath'], $this->Input->post('field'), intval($this->Input->post('level')));
                }

                // Set filemounts
                else
                {
                    foreach ($this->eliminateNestedPaths($this->User->filemounts) as $node)
                    {
                        $tree .= $objWidget->generateAjax($node, $this->Input->post('field'), intval($this->Input->post('level')), true);
                    }
                }

                echo json_encode(array(
                    'content' => $tree,
                    'token' => REQUEST_TOKEN
                ));
                exit;
                break;
        }
    }

}

?>
