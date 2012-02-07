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

    public function toggleSubpalettes($strAction, $dc)
    {
        if ($strAction == "DcMemToggleSubpalette")
        {
            if ($dc instanceof DC_Memory)
            {
                if ($this->Input->get('act') == 'editAll')
                {
                    $this->strAjaxId = preg_replace('/.*_([0-9a-zA-Z]+)$/i', '$1', $this->Input->post('id'));

                    if ($this->Input->post('load'))
                    {
                        echo json_encode(array
                            (
                            'content' => $dc->editAll($this->strAjaxId, $this->Input->post('id')),
                            'token'   => REQUEST_TOKEN
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
                            'token'   => REQUEST_TOKEN
                        ));
                        exit;
                        break;
                    }
                }
            }
        }
    }

    public function myExecutePostActions($strAction, DataContainer $dc)
    {
        if ($strAction == 'toggleDCMemorySubpalette')
        {
            if ($dc instanceof DC_Memory)
            {
                $dc->setData($this->Input->post('field'), (intval($this->Input->post('state') == 1) ? 1 : ''));

                if ($this->Input->post('load'))
                {
                    echo $dc->edit(false, $this->Input->post('id'));
                }
            }
        }
    }

}

?>
