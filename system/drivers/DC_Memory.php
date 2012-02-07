<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

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
 * @author     Leo Feyer <http://www.contao.org>
 * @package    Backend
 * @license    LGPL
 * @filesource
 */

/**
 * Class DC_Memory
 *
 * Provide methods to manage datacontainers with customized loading and storage functionalities
 * @copyright  Stefan Lindecke 2011
 * @copyright  MEN AT WORK 2012
 * @author     Stefan Lindecke  <stefan@ktrion.de>
 * @author     Yanick Witschi   <yanick.witschi@certo-net.ch>
 * @author     MEN AT WORK      <cms@men-at-work.de>
 * @package    DC_Memory
 */
class DC_Memory extends DataContainer implements listable, editable
{

    /**
     * Array containing the buttons
     * @var array
     */
    protected $arrSubmitButtons = array();

    /**
     * The compound template object
     * @var tplCompound
     */
    protected $objDCACompound = null;

    /**
     * Array containing the field data
     * @var array
     */
    protected $arrData = array();

    /**
     * Language config object
     * @var object
     */
    protected $objLanguageConfig = null;

    /**
     * Language we're currently editing
     * @var string
     */
    protected $strLanguage = '';

    /**
     * Language editing mode (either "delete" or "edit")
     * @var string
     */
    protected $strLanguageEditMode = '';

    /**
     * Initialize the object
     * @param string | the database table
     */
    public function __construct($strTable)
    {
        parent::__construct();
        $this->intId = $this->Input->get('id');

        // Check whether the table is defined
        if (!strlen($strTable) || !count($GLOBALS['TL_DCA'][$strTable]))
        {
            $this->log('Could not load data container configuration for "' . $strTable . '"', 'DC_Memory __construct()', TL_ERROR);
            trigger_error('Could not load data container configuration', E_USER_ERROR);
        }

        // Build object from global configuration array
        $this->strTable = $strTable;

        // check for language data (has to be before the onload_callback call)
        $this->checkForLanguageData();

        // Set default buttons, you can always add more buttons using the addButton() method
        $this->addButton('save', array
            (
            'id'              => 'save',
            'formkey'         => 'save',
            'class'           => '',
            'accesskey'       => 's',
            'value'           => specialchars($GLOBALS['TL_LANG']['MSC']['save']),
            'button_callback' => array('DC_Memory', 'saveButtonCallback')
        ));

        $this->addButton('saveNclose', array
            (
            'id'              => 'save',
            'formkey'         => 'saveNclose',
            'class'           => '',
            'accesskey'       => 'c',
            'value'           => specialchars($GLOBALS['TL_LANG']['MSC']['saveNclose']),
            'button_callback' => array('DC_Memory', 'saveNcloseButtonCallback')
        ));

        // set the compound template
        $strDCATemplate = 'tpl_dca_view';
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['compoundTemplate'])
        {
            $strDCATemplate = $GLOBALS['TL_DCA'][$this->strTable]['config']['compoundTemplate'];
        }

        $this->objDCACompound = new tplCompound('tpl_cmp_' . $this->strTable . '_' . $this->intId, $strDCATemplate);

        $this->activeRecord = new stdClass;

        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_pre_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_pre_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $this->$callback[0]->$callback[1]($this);
                }
            }
        }
        // Call onload_callback (e.g. to check permissions)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onload_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $this->$callback[0]->$callback[1]($this);
                }
            }
        }
    }

    /**
     * Add your own buttons to the form.
     * @param string | button ID - e.g. "saveNclose"
     * @param array | see __construct() for the default buttons so you know what parameters ar possible
     * @see self::__construct()
     */
    public function addButton($strButtonId, $arrData)
    {
        if (isset($this->arrSubmitButtons[$strButtonId]))
        {
            throw new Exception('The button with ID "' . $strButtonId . '" already exists!');
        }

        $this->arrSubmitButtons[$strButtonId] = $arrData;
    }

    /**
     * Remove buttons (you can also remove the default ones)
     * @param string | button id
     */
    public function removeButton($strButtonId)
    {
        unset($this->arrSubmitButtons[$strButtonId]);
    }

    /**
     * Add language switch possibilities
     * @param object
     */
    public function enableLanguageSwitch($objConfig)
    {
        $this->objLanguageConfig = $objConfig;
        $this->objLanguageConfig->enable = true;
    }

    /**
     * Set or overwrite a certain value
     * @param string
     * @param mixed
     */
    public function setData($strKey, $varValue)
    {
        $this->arrData[$strKey] = $varValue;
    }

    /**
     * Get a certain value
     * @return mixed
     */
    public function getData($strKey)
    {
        return $this->arrData[$strKey];
    }

    /**
     * Get the data as an array
     * @return array
     */
    public function getDataArray()
    {
        return $this->arrData;
    }

    /**
     * Set the data from an array
     * @param array
     */
    public function setDataArray($arrData)
    {
        if (is_array($arrData))
        {
            foreach ($arrData as $k => $v)
            {
                $this->setData($k, $v);
            }
        }
        else
        {
            $this->arrData = array();
        }
    }

    /**
     * Get the language we're currently editing
     * @return string
     */
    public function getLanguage()
    {
        return $this->strLanguage;
    }

    /**
     * Get the language editing mode (can either be "edit" or "delete")
     * @return string
     */
    public function getLanguageEditMode()
    {
        return $this->strLanguageEditMode;
    }

    /**
     * Checks for language data and sets it to the session
     */
    protected function checkForLanguageData()
    {
        // get the language (e.g. "dcMemoryLanguage_mytable_2")
        $strLanguage = $this->Session->get('dcMemoryLanguage_' . $this->strTable . '_' . $this->intId);
        if ($strLanguage)
        {
            $this->strLanguage = $strLanguage;
        }

        // get the edit mode (e.g. "dcMemoryLanguageEditMode_mytable_2")
        $strEditMode = $this->Session->get('dcMemoryLanguageEditMode_' . $this->strTable . '_' . $this->intId);
        if ($strEditMode)
        {
            $this->strLanguageEditMode = $strEditMode;
        }

        // set the data to the session if the user changes the language or wants to delete one
        if ($this->Input->post('FORM_SUBMIT') == 'tl_language')
        {
            $this->Session->set('dcMemoryLanguage_' . $this->strTable . '_' . $this->intId, $this->Input->post('language'));

            if ($this->Input->post('editLanguage'))
            {
                $this->Session->set('dcMemoryLanguageEditMode_' . $this->strTable . '_' . $this->intId, 'edit');
            }
            else
            {
                $this->Session->set('dcMemoryLanguageEditMode_' . $this->strTable . '_' . $this->intId, 'delete');
            }

            $this->reload();
        }
    }

    /**
     * Automatically switch to edit mode
     * @return string
     */
    public function create()
    {
        return $this->edit();
    }

    /**
     * Automatically switch to edit mode
     * @return string
     */
    public function cut()
    {
        return $this->edit();
    }

    /**
     * Automatically switch to edit mode
     * @return string
     */
    public function copy()
    {
        return $this->edit();
    }

    /**
     * Automatically switch to edit mode
     * @return string
     */
    public function move()
    {
        return $this->edit();
    }

    /**
     * Autogenerate a form
     * @return string
     */
    public function edit($intID = false, $ajaxId = false)
    {
        $return = '';

        if ($this->Input->post('isAjax'))
        {
            $ajaxId = func_get_arg(1);
        }

        if ($ajaxId != false)
        {
            $strToggleField = $this->Input->post("field");
            if (!key_exists($strToggleField, $GLOBALS['TL_DCA'][$this->table]['subpalettes']))
            {
                return "<div>No subpalett found for $strToggleField</div>";
            }

            $objDCAForm = new tplCompound('subpalettes', 'subpalettes');
            
            // Build an array from boxes and rows
            $this->strPalette = $GLOBALS['TL_DCA'][$this->table]['subpalettes'][$strToggleField];
            $boxes   = trimsplit(';', $this->strPalette);
            $legends = array();

            if (count($boxes))
            {
                foreach ($boxes as $k => $v)
                {
                    $eCount    = 1;
                    $boxes[$k] = trimsplit(',', $v);

                    foreach ($boxes[$k] as $kk => $vv)
                    {
                        if (preg_match('/^\[.*\]$/i', $vv))
                        {
                            ++$eCount;
                            continue;
                        }

                        if (preg_match('/^\{.*\}$/i', $vv))
                        {
                            $legends[$k] = substr($vv, 1, -1);
                            unset($boxes[$k][$kk]);
                        }
                        elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]['exclude'] || !is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]))
                        {
                            unset($boxes[$k][$kk]);
                        }

                        // unset fields that are not translatable for the current language
                        if ($this->objLanguageConfig->enable)
                        {
                            $translatableFor = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]['translatableFor'];

                            // if translatableFor is not set we unset it for every language
                            if (!isset($translatableFor))
                            {
                                unset($boxes[$k][$kk]);
                                continue;
                            }

                            // if the language is "fallback" or the field should be shown for all languages, we don't unset anything at all
                            if ($this->getLanguage() == 'fallback' || $translatableFor[0] == '*')
                            {
                                continue;
                            }

                            // we check if the field is not editable for the current language
                            if (!in_array($this->getLanguage(), $translatableFor))
                            {
                                unset($boxes[$k][$kk]);
                            }
                        }
                    }

                    // Unset a box if it does not contain any fields
                    if (count($boxes[$k]) < $eCount)
                    {
                        unset($boxes[$k]);
                    }
                }

                $class = 'tl_tbox block';
                $fs    = $this->Session->get('fieldset_states');

                // Render boxes
                foreach ($boxes as $k => $v)
                {
                    $strAjax  = '';
                    $blnAjax  = false;
                    $legend   = false;
                    $template = 'subpalettes';

                    if (isset($legends[$k]))
                    {
                        list($key, $cls, $templateLegend) = explode(':', $legends[$k]);
                        $legend = true;

                        if ($templateLegend)
                        {
                            $template = $templateLegend;
                        }
                    }

                    if (isset($fs[$this->strTable][$key]))
                    {
                        $class .= ($fs[$this->strTable][$key] ? '' : ' collapsed');
                    }
                    else
                    {
                        $class .= (($cls && $legend) ? ' ' . $cls : '');
                    }

                    $cmpFieldset = new tplCompound($class . '_' . $key, $template,
                                    array(
                                        'id'             => 'pal_' . $key,
                                        'class'          => $class . ($legend ? '' : ' nolegend'),
                                        'legend_title'   => isset($GLOBALS['TL_LANG'][$this->strTable][$key]) ? $GLOBALS['TL_LANG'][$this->strTable][$key] : $key,
                                        'legend_onclick' => "AjaxRequest.toggleFieldset(this, '" . $key . "', '" . $this->strTable . "')"
                                    )
                    );

                    // Build rows of the current box
                    foreach ($v as $kk => $vv)
                    {
                        if ($vv == '[EOF]')
                        {
                            if ($this->Input->post('isAjax') && $blnAjax)
                            {
                                return $strAjax . '<input type="hidden" name="FORM_FIELDS[]" value="' . specialchars($this->strPalette) . '" />';
                            }

                            $blnAjax = false;
                            $return .= "\n" . '</div>';

                            continue;
                        }

                        if (preg_match('/^\[.*\]$/i', $vv))
                        {
                            $thisId  = 'sub_' . substr($vv, 1, -1);
                            $blnAjax = ($this->Input->post('isAjax') && $ajaxId == $thisId) ? true : false;
                            $return .= "\n" . '<div id="' . $thisId . '">';

                            continue;
                        }

                        $this->strField = $vv;
                        $this->strInputName = $vv;
                        $this->varValue = $this->getData($this->strField);

                        // Call load_callback
                        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback']))
                        {
                            foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
                            {
                                if (is_array($callback))
                                {
                                    $this->import($callback[0]);
                                    $this->varValue = $this->$callback[0]->$callback[1]($this->varValue, $this);
                                }
                            }

                            // remove request by AndreasI
                            //$this->objActiveRecord->{$this->strField} = $this->varValue;
                        }

                        // Build row
                        if ($blnAjax)
                        {
                            $strAjax .= $this->row();
                        }
                        else
                        {
                            $fieldContainer = new tplCompound('fieldContainer', 'compound_div');
                            $fieldContainer->addInner(new tplCompound("field_" . $this->strField, "compound_static", $this->row()));

                            if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['addSubmit'])
                            {
                                $fieldContainer->addInner(new tplCompound("submit_" . $this->strField, "compound_submitbutton",
                                                array(
                                                    'id'      => "submit_" . $this->strField,
                                                    'formkey' => "submit_" . $this->strField,
                                                    'class'   => $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['button_class'],
                                                    'value'   => ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][2]) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][2] : $this->strField)));
                            }
                            $cmpFieldset->addInner($fieldContainer);
                        }
                    }


                    $objDCAForm->addInner($cmpFieldset);
                }
            }
            
            return $objDCAForm->parse();
            
        }

        $objDCAForm = new tplCompound('editForm', 'compound_form');

        // remove request by AndreasI
        //$this->objActiveRecord = $objRow;
        // create the initial version (you can use the official createInitialVersion() in your callback but we want to be as flexible as possible)
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'])
        {
            // Trigger the createInitialVersion_callback
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_versioning_createInitial_callback']))
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_versioning_createInitial_callback'] as $callback)
                {
                    if (is_array($callback))
                    {
                        $this->import($callback[0]);
                        $this->$callback[0]->$callback[1]($this);
                    }
                }
            }
        }

        // provide possibility to switch versions, you have to implement this manually so there only is the callback
        // we can't be consistent to the core's onrestore_callback because we don't have data from the database here
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] && $this->Input->post('FORM_SUBMIT') == 'tl_version' && strlen($this->Input->post('version')))
        {
            // Trigger the onrestore_callback
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_versioning_restore_callback']))
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_versioning_restore_callback'] as $callback)
                {
                    if (is_array($callback))
                    {
                        $this->import($callback[0]);
                        $this->$callback[0]->$callback[1]($this, $this->Input->post('version'));
                    }
                }
            }
        }

        // Build an array from boxes and rows
        $this->strPalette = $this->getPalette();
        $boxes   = trimsplit(';', $this->strPalette);
        $legends = array();

        if (count($boxes))
        {
            foreach ($boxes as $k => $v)
            {
                $eCount    = 1;
                $boxes[$k] = trimsplit(',', $v);

                foreach ($boxes[$k] as $kk => $vv)
                {
                    if (preg_match('/^\[.*\]$/i', $vv))
                    {
                        ++$eCount;
                        continue;
                    }

                    if (preg_match('/^\{.*\}$/i', $vv))
                    {
                        $legends[$k] = substr($vv, 1, -1);
                        unset($boxes[$k][$kk]);
                    }
                    elseif ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]['exclude'] || !is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]))
                    {
                        unset($boxes[$k][$kk]);
                    }

                    // unset fields that are not translatable for the current language
                    if ($this->objLanguageConfig->enable)
                    {
                        $translatableFor = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$vv]['translatableFor'];

                        // if translatableFor is not set we unset it for every language
                        if (!isset($translatableFor))
                        {
                            unset($boxes[$k][$kk]);
                            continue;
                        }

                        // if the language is "fallback" or the field should be shown for all languages, we don't unset anything at all
                        if ($this->getLanguage() == 'fallback' || $translatableFor[0] == '*')
                        {
                            continue;
                        }

                        // we check if the field is not editable for the current language
                        if (!in_array($this->getLanguage(), $translatableFor))
                        {
                            unset($boxes[$k][$kk]);
                        }
                    }
                }

                // Unset a box if it does not contain any fields
                if (count($boxes[$k]) < $eCount)
                {
                    unset($boxes[$k]);
                }
            }

            $class = 'tl_tbox block';
            $fs    = $this->Session->get('fieldset_states');

            // Render boxes
            foreach ($boxes as $k => $v)
            {
                $strAjax  = '';
                $blnAjax  = false;
                $legend   = false;
                $template = 'compound_fieldsetlegend';

                if (isset($legends[$k]))
                {
                    list($key, $cls, $templateLegend) = explode(':', $legends[$k]);
                    $legend = true;

                    if ($templateLegend)
                    {
                        $template = $templateLegend;
                    }
                }

                if (isset($fs[$this->strTable][$key]))
                {
                    $class .= ($fs[$this->strTable][$key] ? '' : ' collapsed');
                }
                else
                {
                    $class .= (($cls && $legend) ? ' ' . $cls : '');
                }


                $cmpFieldset = new tplCompound($class . '_' . $key, $template,
                                array(
                                    'id'             => 'pal_' . $key,
                                    'class'          => $class . ($legend ? '' : ' nolegend'),
                                    'legend_title'   => isset($GLOBALS['TL_LANG'][$this->strTable][$key]) ? $GLOBALS['TL_LANG'][$this->strTable][$key] : $key,
                                    'legend_onclick' => "AjaxRequest.toggleFieldset(this, '" . $key . "', '" . $this->strTable . "')"
                                )
                );

                // Build rows of the current box
                foreach ($v as $kk => $vv)
                {
                    if ($vv == '[EOF]')
                    {
                        if ($this->Input->post('isAjax') && $blnAjax)
                        {
                            return $strAjax . '<input type="hidden" name="FORM_FIELDS[]" value="' . specialchars($this->strPalette) . '" />';
                        }

                        $blnAjax = false;
                        $return .= "\n" . '</div>';

                        continue;
                    }

                    if (preg_match('/^\[.*\]$/i', $vv))
                    {
                        $thisId  = 'sub_' . substr($vv, 1, -1);
                        $blnAjax = ($this->Input->post('isAjax') && $ajaxId == $thisId) ? true : false;
                        $return .= "\n" . '<div id="' . $thisId . '">';

                        continue;
                    }

                    $this->strField = $vv;
                    $this->strInputName = $vv;
                    $this->varValue = $this->getData($this->strField);

                    // Call load_callback
                    if (is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback']))
                    {
                        foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['load_callback'] as $callback)
                        {
                            if (is_array($callback))
                            {
                                $this->import($callback[0]);
                                $this->varValue = $this->$callback[0]->$callback[1]($this->varValue, $this);
                            }
                        }

                        // remove request by AndreasI
                        //$this->objActiveRecord->{$this->strField} = $this->varValue;
                    }

                    // Build row
                    if ($blnAjax)
                    {
                        $strAjax .= $this->row();
                    }
                    else
                    {
                        $fieldContainer = new tplCompound('fieldContainer', 'compound_div');
                        $fieldContainer->addInner(new tplCompound("field_" . $this->strField, "compound_static", $this->row()));

                        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['addSubmit'])
                        {
                            $fieldContainer->addInner(new tplCompound("submit_" . $this->strField, "compound_submitbutton",
                                            array(
                                                'id'      => "submit_" . $this->strField,
                                                'formkey' => "submit_" . $this->strField,
                                                'class'   => $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['button_class'],
                                                'value'   => ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][2]) ? $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][2] : $this->strField)));
                        }
                        $cmpFieldset->addInner($fieldContainer);
                    }
                }


                $objDCAForm->addInner($cmpFieldset);
            }
        }

        // add version AND/OR language switch
        if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] || $this->objLanguageConfig->enable)
        {
            $objVersionPanel = new tplCompound('tl_version_panel', 'compound_div', array('class' => 'tl_version_panel'));

            if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'])
            {
                // version data
                $strVersionData = '';
                $arrVersions    = array();

                // Trigger the listversions_callback
                if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_versioning_list_callback']))
                {
                    foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_versioning_list_callback'] as $callback)
                    {
                        if (is_array($callback))
                        {
                            $this->import($callback[0]);
                            $arrVersions = $this->$callback[0]->$callback[1]($this, $arrVersions);
                        }
                    }
                }

                if (is_array($arrVersions) && count($arrVersions))
                {
                    foreach ($arrVersions as $arrData)
                    {
                        $strVersionData .= '<option value="' . $arrData['value'] . '"' . (($arrData['selected']) ? ' selected="selected"' : '') . '>' . $arrData['label'] . '</option>';
                    }
                }

                // only add it if we have some data
                if (strlen($strVersionData))
                {
                    $strVersionSelectContainer = '<select name="version" class="tl_select">%s</select>';
                    $strVersionSelectContainer .= '<input type="submit" name="showVersion" id="showVersion" class="tl_submit" value="' . $GLOBALS['TL_LANG']['MSC']['restore'] . '">';

                    $objVersionForm = new tplCompound('versionForm', 'compound_form');
                    $objVersionForm->addData('action', ampersand($this->Environment->request, true));

                    // only float right if languages are also enabled
                    if ($this->objLanguageConfig->enable)
                    {
                        $objVersionForm->addData('id', 'tl_version" style="float:right;');
                    }
                    else
                    {
                        $objVersionForm->addData('id', 'tl_version');
                    }

                    $objVersionForm->addData('method', 'post');
                    $objVersionForm->addData('FORM_SUBMIT', 'tl_version');

                    $objVersionData = new tplCompound('versionData', 'compound_static', sprintf($strVersionSelectContainer, $strVersionData));
                    $objVersionForm->addInner($objVersionData);
                    $objVersionPanel->addInner($objVersionForm);
                }
            }

            // now the languages
            if ($this->objLanguageConfig->enable)
            {
                $objLanguagesForm = new tplCompound('languagesForm', 'compound_form');
                $objLanguagesForm->addData('action', ampersand($this->Environment->request, true));

                // only float left and give it the id "tl_language" if versioning is also enabled
                if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'])
                {
                    $objLanguagesForm->addData('id', 'tl_language" style="float:left;margin-left:20px;');
                }
                else
                {
                    $objLanguagesForm->addData('id', 'tl_version');
                }

                $objLanguagesForm->addData('method', 'post');
                $objLanguagesForm->addData('FORM_SUBMIT', 'tl_language');

                $strLanguagesSelectContainer = '<select name="language" class="tl_select">%s</select>';
                $strLanguagesSelectContainer .= '<input type="submit" name="editLanguage" class="tl_submit" value="' . $GLOBALS['TL_LANG']['MSC']['editSelected'] . '">';
                $strLanguagesSelectContainer .= '<input type="submit" name="deleteLanguage" class="tl_submit" value="' . $GLOBALS['TL_LANG']['MSC']['deleteSelected'] . '" onclick="return confirm(\'' . sprintf($GLOBALS['TL_LANG']['MSC']['deleteConfirm'], $this->intId) . '\')">';

                $strLanguageData = '';

                // add fallback language
                if ($this->objLanguageConfig->enableFallback)
                {
                    $strLanguageData .= '<option value="fallback">Fallback</option>';
                }

                if (is_array($this->objLanguageConfig->arrLanguages) && count($this->objLanguageConfig->arrLanguages))
                {
                    $arrLangues = $this->getLanguages();

                    foreach ($this->objLanguageConfig->arrLanguages as $strLangKey)
                    {
                        $strLanguageData .= '<option value="' . $strLangKey . '"' . (($this->getLanguage() == $strLangKey) ? ' selected="selected"' : '') . '>' . $arrLangues[$strLangKey] . '</option>';
                    }
                }

                $objLanguagesData = new tplCompound('languagesData', 'compound_static', sprintf($strLanguagesSelectContainer, $strLanguageData));
                $objLanguagesForm->addInner($objLanguagesData);
                $objVersionPanel->addInner($objLanguagesForm);
            }

            // if versioning and language containers are both enabled, we need to clear the floating
            if ($GLOBALS['TL_DCA'][$this->strTable]['config']['enableVersioning'] && $this->objLanguageConfig->enable)
            {
                $objVersionPanel->addInner(new tplCompound('clear_div', 'compound_static', '<div class="clear"></div>'));
            }

            // add the panel to the dcaform
            $this->objDCACompound->addInner($objVersionPanel);
        }

        $returnLinkContainer = new tplCompound('tl_buttons', 'compound_div', array('id' => 'tl_buttons'));

        $returnLinkContainer->addInner(
                new tplCompound('header_back', 'compound_ahref',
                        array(
                            'href'      => $this->getReferer(true),
                            'class'     => 'header_back',
                            'title'     => specialchars($GLOBALS['TL_LANG']['MSC']['backBT']),
                            'accesskey' => 'b',
                            'onclick'   => "Backend.getScrollOffset();",
                            'value'     => $GLOBALS['TL_LANG']['MSC']['backBT']
                        )
                )
        );
        $this->objDCACompound->addInner($returnLinkContainer);

        $dcaHeadline = new tplCompound('sub_headline', 'compound_headline', 2);
        $dcaHeadline->addInner(
                new tplCompound('sub_headline', 'compound_static', $GLOBALS['TL_LANG'][$this->strTable]['edit']
        ));

        $this->objDCACompound->addInner($dcaHeadline);

        if ($GLOBALS['TL_DCA'][$this->strTable]['label']['name'])
        {
            $labelContainer = new tplCompound('label_name', 'compound_div');

            $labelContainer->addInner(
                    new tplCompound('description', 'compound_static', $GLOBALS['TL_DCA'][$this->strTable]['label']['name']
                    )
            );
            $this->objDCACompound->addInner($labelContainer);
        }

        if ($GLOBALS['TL_DCA'][$this->strTable]['label']['description'])
        {
            $labelContainer = new tplCompound('label_description', 'compound_div');

            $labelContainer->addInner(
                    new tplCompound('description', 'compound_static', $GLOBALS['TL_DCA'][$this->strTable]['label']['description']
                    )
            );
            $this->objDCACompound->addInner($labelContainer);
        }


        $this->objDCACompound->addInner(
                new tplCompound('messages', 'compound_static', $this->getMessages()
                )
        );

        $objDCAForm->addData('action', ampersand($this->Environment->request, true));
        $objDCAForm->addData('id', $this->strTable);
        $objDCAForm->addData('class', '');
        $objDCAForm->addData('method', 'post');
        $objDCAForm->addData('onSubmit', implode(' ', $this->onsubmit));
        $objDCAForm->addData('divClass', 'tl_formbody_edit');
        $objDCAForm->addData('FORM_SUBMIT', specialchars($this->strTable));
        $objDCAForm->addData('FORM_FIELDS', specialchars($this->strPalette));

        if ($this->noReload)
        {
            $objDCAForm->addData('tl_error', $GLOBALS['TL_LANG']['ERR']['general']);
        }

        // Add some buttons and end the form if there are any
        if (is_array($this->arrSubmitButtons) && count($this->arrSubmitButtons))
        {
            if (!$GLOBALS['TL_DCA'][$this->strTable]['config']['disableSubmit'])
            {
                $objButtonContainer = new tplCompound('tl_formbody_submit', 'compound_div');
                $objSubmitButtons   = new tplCompound('tl_submit_container', 'compound_div');

                foreach ($this->arrSubmitButtons as $strButtonKey => $arrButtonData)
                {
                    $objButton = new tplCompound($strButtonKey, "compound_submitbutton", $arrButtonData);
                    $objSubmitButtons->addInner($objButton);
                }

                $objButtonContainer->addInner($objSubmitButtons);
                $objDCAForm->addInnerButtons($objButtonContainer);
            }
        }

        $this->objDCACompound->addInner($objDCAForm);
        // Reload the page to prevent _POST variables from being sent twice
        if ($this->Input->post('FORM_SUBMIT') == $this->strTable && !$this->noReload)
        {
            // Call onsubmit_callback
            if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback']))
            {
                foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] as $callback)
                {
                    $this->import($callback[0]);
                    $this->$callback[0]->$callback[1]($this);
                }
            }

            // call the the submit button callbacks so custom logic can be implemented (only if we have buttons)
            if (is_array($this->arrSubmitButtons) && count($this->arrSubmitButtons))
            {
                foreach ($this->arrSubmitButtons as $strButtonKey => $arrButtonData)
                {
                    if ($this->Input->post($arrButtonData['formkey']))
                    {
                        // button has no callback
                        if (!isset($arrButtonData['button_callback']))
                        {
                            break;
                        }

                        $strClass  = $arrButtonData['button_callback'][0];
                        $strMethod = $arrButtonData['button_callback'][1];

                        // DC_Memory internal callbacks (don't want to put them external just because the constructor needs a param)
                        if ($strClass == 'DC_Memory')
                        {
                            switch ($strMethod)
                            {
                                case 'saveButtonCallback':
                                    $this->saveButtonCallback();
                                    break;
                                case 'saveNcloseButtonCallback':
                                    $this->saveNcloseButtonCallback();
                                    break;
                            }
                        }
                        else
                        {
                            $this->import($strClass);
                            $this->$strClass->$strMethod($this);
                        }
                    }
                }
            }

            // Reload if no button really wanted to do anything special
            $this->reload();
        }

        // Set the focus if there is an error
        if ($this->noReload)
        {
            $strReload = '

<script type="text/javascript">
<!--//--><![CDATA[//><!--
window.addEvent(\'domready\', function()
{
    Backend.vScrollTo(($(\'' . $this->strTable . '\').getElement(\'label.error\').getPosition().y - 20));
});
//--><!]]>
</script>';

            $this->objDCACompound->addInner(new tplCompound('reload', 'compound_static', $strReload));
        }

        $strReturn = $this->objDCACompound->parse();
        $strReturn = str_replace("AjaxRequest.toggleSubpalette", "DCMemoryAjaxRequest.toggleSubpalette", $strReturn);

        return $strReturn;
    }

    /**
     * Button callback "save"
     */
    protected function saveButtonCallback()
    {
        $this->reload();
    }

    /**
     * Button callback "saveNclose"
     */
    protected function saveNcloseButtonCallback()
    {
        $_SESSION['TL_INFO']    = '';
        $_SESSION['TL_ERROR']   = '';
        $_SESSION['TL_CONFIRM'] = '';

        setcookie('BE_PAGE_OFFSET', 0, 0, '/');
        $this->redirect($this->getReferer());
    }

    protected function row()
    {
        // Call onsubmit_callback
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_pre_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_pre_callback'] as $callback)
            {
                $this->import($callback[0]);
                $this->$callback[0]->$callback[1]($this);
            }
        }


        return parent::row();
    }

    /**
     * Save the current value
     * @param mixed
     * @throws Exception
     */
    protected function save($varValue)
    {
        if ($this->Input->post('FORM_SUBMIT') != $this->strTable)
        {
            return;
        }

        $arrData = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField];

        // Make sure that checkbox values are boolean
        if ($arrData['inputType'] == 'checkbox' && !$arrData['eval']['multiple'])
        {
            $varValue = $varValue ? true : false;
        }

        // Convert date formats into timestamps
        if (strlen($varValue) && in_array($arrData['eval']['rgxp'], array('date', 'time', 'datim')))
        {
            $objDate  = new Date($varValue, $GLOBALS['TL_CONFIG'][$arrData['eval']['rgxp'] . 'Format']);
            $varValue = $objDate->tstamp;
        }

        // Handle entities
        if ($arrData['inputType'] == 'text' || $arrData['inputType'] == 'textarea')
        {
            $varValue = deserialize($varValue);

            if (!is_array($varValue))
            {
                $varValue = $this->restoreBasicEntities($varValue);
            }
            else
            {
                foreach ($varValue as $k => $v)
                {
                    $varValue[$k] = $this->restoreBasicEntities($v);
                }

                $varValue = serialize($varValue);
            }
        }

        // Call save_callback
        if (is_array($arrData['save_callback']))
        {
            foreach ($arrData['save_callback'] as $callback)
            {
                $this->import($callback[0]);
                $varValue = $this->$callback[0]->$callback[1]($varValue, $this);
            }
        }

        // Save the value if there was no error
        if ((strlen($varValue) || !$arrData['eval']['doNotSaveEmpty']) && $this->varValue != $varValue)
        {
            $deserialize = deserialize($varValue);

            $this->setData($this->strField, $deserialize);


            if (is_object($this->activeRecord))
            {
                $this->activeRecord->{$this->strField} = $this->varValue;
            }
        }
    }

    /**
     * Return the name of the current palette
     * @return string
     */
    public function getPalette()
    {
        $palette    = 'default';
        $strPalette = $GLOBALS['TL_DCA'][$this->strTable]['palettes'][$palette];

        // Check whether there are selector fields
        if (count($GLOBALS['TL_DCA'][$this->strTable]['palettes']['__selector__']))
        {
            $sValues = array();
            $subpalettes = array();

            foreach ($GLOBALS['TL_DCA'][$this->strTable]['palettes']['__selector__'] as $name)
            {
                $trigger = $this->getData($name);

                // Overwrite the trigger if the page is not reloaded
                if ($this->Input->post('FORM_SUBMIT') == $this->strTable)
                {
                    $key = ($this->Input->get('act') == 'editAll') ? $name . '_' . $this->intId : $name;

                    if (!$GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['eval']['submitOnChange'])
                    {
                        $trigger = $this->Input->post($key);
                    }
                }

                if ($trigger != '')
                {
                    if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['inputType'] == 'checkbox' && !$GLOBALS['TL_DCA'][$this->strTable]['fields'][$name]['eval']['multiple'])
                    {
                        $sValues[] = $name;

                        // Look for a subpalette
                        if (strlen($GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$name]))
                        {
                            $subpalettes[$name] = $GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$name];
                        }
                    }
                    else
                    {
                        $sValues[] = $trigger;
                        $key       = $name . '_' . $trigger;

                        // Look for a subpalette
                        if (strlen($GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$key]))
                        {
                            $subpalettes[$name] = $GLOBALS['TL_DCA'][$this->strTable]['subpalettes'][$key];
                        }
                    }
                }
            }

            // Build possible palette names from the selector values
            if (!count($sValues))
            {
                $names = array('default');
            }
            elseif (count($sValues) > 1)
            {
                $names = $this->combiner($sValues);
            }
            else
            {
                $names = array($sValues[0]);
            }

            // Get an existing palette
            foreach ($names as $paletteName)
            {
                if (strlen($GLOBALS['TL_DCA'][$this->strTable]['palettes'][$paletteName]))
                {
                    $palette    = $paletteName;
                    $strPalette = $GLOBALS['TL_DCA'][$this->strTable]['palettes'][$paletteName];

                    break;
                }
            }

            // Include subpalettes
            foreach ($subpalettes as $k => $v)
            {
                $strPalette = preg_replace('/\b' . preg_quote($k, '/') . '\b/i', $k . ',[' . $k . '],' . $v . ',[EOF]', $strPalette);
            }
        }

        return $strPalette;
    }

    /**
     * Implement showAll mode. Use the callback to format the list view.
     * @TODO: Provide a DC_Memory_Helpers class with helper methods to generate useful panels (limit, filter, search etc.) and other stuff
     * @return string
     */
    public function showAll()
    {
        $strReturn = '';

        // Trigger the showAll_callback
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_showAll_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_showAll_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $strReturn = $this->$callback[0]->$callback[1]($this, $strReturn);
                }
            }
        }

        return $strReturn;
    }

    /**
     * Implement delete mode. Use the callback.
     * @TODO: Provide a DC_Memory_Helpers class with helper methods to generate useful panels (limit, filter, search etc.) and other stuff
     * @return string
     */
    public function delete()
    {
        $strReturn = '';

        // Trigger the showAll_callback
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_delete_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_delete_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $strReturn = $this->$callback[0]->$callback[1]($this, $strReturn);
                }
            }
        }

        return $strReturn;
    }

    /**
     * Implement show mode. Use the callback.
     * @TODO: Provide a DC_Memory_Helpers class with helper methods to generate useful panels (limit, filter, search etc.) and other stuff
     * @return string
     */
    public function show()
    {
        $strReturn = '';

        // Trigger the showAll_callback
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_show_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_show_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $strReturn = $this->$callback[0]->$callback[1]($this, $strReturn);
                }
            }
        }

        return $strReturn;
    }

    /**
     * Implement undo mode. Use the callback.
     * @TODO: Provide a DC_Memory_Helpers class with helper methods to generate useful panels (limit, filter, search etc.) and other stuff
     * @return string
     */
    public function undo()
    {
        $strReturn = '';

        // Trigger the showAll_callback
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_undo_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['dcMemory_undo_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $strReturn = $this->$callback[0]->$callback[1]($this, $strReturn);
                }
            }
        }

        return $strReturn;
    }

}

?>