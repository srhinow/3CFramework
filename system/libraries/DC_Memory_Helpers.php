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
 * Class DC_Memory_Helpers
 *
 * Provide helper methods for the DC_Memory
 * @copyright  Yanick Witschi 2011
 * @author     Yanick Witschi <yanick.witschi@certo-net.ch>
 * @package    DC_Memory
 */
class DC_Memory_Helpers extends DC_Memory
{
	/**
	 * DC_Memory object
	 * @var DC_Memory
	 */
	protected $objDC = null;
	
	/**
	 * Limit
	 * @var string
	 */
	public $strLimit = '';
	
	
	/**
	 * Initialize the object
	 * @param DC_Memory
	 */
	public function __construct($objDC)
	{
		parent::__construct($objDC->table);
		$this->objDC = $objDC;
	}
	
	
	/**
	 * Generates the panel div. You can pass whatever html content you want to (filter menu, limit, custom things etc.)
	 * @param string
	 * @return string
	 */
	public function generatePanel($strContent='')
	{
		// if we don't have any content, we don't need the panel, right?
		if(!strlen($strContent))
		{
			return '';
		}
		
		$strPanel = '<div class="tl_panel">
						<div class="tl_submit_panel tl_subpanel">
							<input type="image" name="filter" id="filter" src="' . TL_FILES_URL . 'system/themes/' . $this->getTheme() . '/images/reload.gif" class="tl_img_submit" title="' . $GLOBALS['TL_LANG']['MSC']['apply'] . '" alt="' . $GLOBALS['TL_LANG']['MSC']['apply'] . '">
						</div>
						%s
						<div class="clear"></div>
					</div>';
		
		$strPanel = sprintf($strPanel, $strContent);
		
		$strForm = '<form action="'.ampersand($this->Environment->request, true).'" class="tl_form" method="post">
						<div class="tl_formbody">
							<input type="hidden" name="FORM_SUBMIT" value="tl_filters">
							<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
							%s
						</div>
					</form>';
		
		return sprintf($strForm, $strPanel);
	}


	/**
	 * Return the limitMenu string (without any functionality!)
	 * @param int | total count of data rows
	 * @param int | results per page
	 * @return string
	 */
	public function generateLimitMenuString($intTotal, $intResultsPerPage=false)
	{
		if(!$intResultsPerPage)
		{
			$intResultsPerPage = $GLOBALS['TL_CONFIG']['resultsPerPage'];
		}
		
		// set the default limit
		$this->strLimit = '0,' . $intResultsPerPage;
		
		// Build options
		if($intTotal > 0)
		{
			$strOptions = '';
			$intNumberOfOptions = ceil($intTotal / $intResultsPerPage);

			// Build options
			for($i=0; $i<$intNumberOfOptions; $i++)
			{
				$strOptionValue = ($i * $intResultsPerPage) . ',' . $intResultsPerPage;
				$intUpperLimit = ($i * $intResultsPerPage + $intResultsPerPage);

				// upper limit cannot be greater than total
				if($intUpperLimit > $intTotal)
				{
					$intUpperLimit = $intTotal;
				}

				$strOptions .= '<option value="' . $strOptionValue . '"' . $this->optionSelected($this->strLimit, $strOptionValue) . '>' . ($i * $intResultsPerPage + 1) . ' - ' . $intUpperLimit . '</option>';
			}

			// show all mode
			$strOptions .= '<option value="all"' . $this->optionSelected($this->strLimit, null) . '>' . $GLOBALS['TL_LANG']['MSC']['filterAll'] . '</option>';
		}

		// Don't generate anything if the total is 0 or the number of options 1
		if(($intTotal == 0 || $intNumberOfOptions == 1))
		{
			return '';
		}

		$strFields = '<select name="tl_limit" class="tl_select' . (($session['filter'][$filter]['limit'] != 'all' && $intTotal > $intResultsPerPage) ? ' active' : '') . '" onchange="this.form.submit()"><option value="tl_limit">' . $GLOBALS['TL_LANG']['MSC']['filterRecords'] . '</option>' . $strOptions . '</select>';
		return sprintf('<div class="tl_limit tl_subpanel"><strong>%s</strong>: %s</div>', $GLOBALS['TL_LANG']['MSC']['showOnly'], $strFields);
	}


	/**
	 * Sets the limits
	 * If you call this first, the limits will be set to the session according to the DC_Table but you can always implement your version by accessing $this->strLimit directly
	 */
	public function setCurrentLimits()
	{
		$session = $this->Session->getData();
		$filterKey = $this->objDC->table . '_' . $this->objDC->id;
		
		// Set limit from user input
		if ($this->Input->post('FORM_SUBMIT') == 'tl_filters')
		{
			if ($this->Input->post('tl_limit') != 'tl_limit' || $this->Input->post('tl_limit') != 'all')
			{
				$session['filter'][$filterKey]['limit'] = $this->Input->post('tl_limit');
			}
			else
			{
				unset($session['filter'][$filterKey]['limit']);
			}

			$this->Session->setData($session);
			$this->reload();
		}
		// Set limit from session
		else
		{
			if(strlen($session['filter'][$filterKey]['limit']))
			{
				$this->strLimit = $session['filter'][$filterKey]['limit'];
			}
		}
	}


	/**
	 * Get the LIMIT for your SQL query ($this->Database->execute("SELECT id FROM table" . $objHelper->getLimitForSQL()))
	 * @return string
	 */
	public function getLimitForSQL()
	{
		$strLimit = '';
		
		if(strlen($objHelper->strLimit))
		{
			$strLimit = ' LIMIT ' . $objHelper->strLimit;
		}
		
		return $strLimit;
	}


	/**
	 * Generate global operations string
	 * @param boolean | show back link or not
	 * @param string | link being added to the current url for a new entry
	 * @param boolean | whether to show the messages or not
	 * @return string
	 */
	public function generateGlobalOperationsString($blnShowBackLink=false, $strNewEntryLink='act=create', $blnShowMessages=false)
	{
		$strBackButton = '';
		if($blnShowBackLink)
		{
			$strBackButton = '<a href="' . $this->getReferer(true, $this->objDC->ptable) . '" class="header_back" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['backBT']) . '" accesskey="b" onclick="Backend.getScrollOffset();">' . $GLOBALS['TL_LANG']['MSC']['backBT'] . '</a>';
		}
		
		$strNewEntryButton = '';
		if(strlen($strNewEntryLink))
		{
			$strNewEntryButton = '<a href="' . $this->addToUrl($strNewEntryLink). '" class="header_new" title="' . specialchars($GLOBALS['TL_LANG'][$this->objDC->table]['new'][1]) . '" accesskey="n" onclick="Backend.getScrollOffset();">' . $GLOBALS['TL_LANG'][$this->objDC->table]['new'][0] . '</a>';
		}
			
		return '<div id="tl_buttons">' . $strBackButton . $strNewEntryButton . $this->generateGlobalButtons() . '</div>' . (($blnShowMessages) ? $this->getMessages(true) : '');
	}
	
	
	/**
	 * Generate listView string
	 * @param array | data array
	 * $arrData = array
	 * (
	 * 		'group_1' => array
	 * 		(
	 * 			'label' => 'My group label',
	 * 			'data'	=> array
	 * 			(
	 * 				array
	 * 				(
	 * 					'id'		=> 12,
	 * 					'class'		=> 'list_icon',
	 * 					'style'		=> 'background-image:url(\'system/themes/default/images/user.gif\');',
	 * 					'label'		=> 'Helen Lewis',
	 * 					'buttons'	=> array
	 * 					(
	 * 						'edit' => array
	 * 						(
	 * 							'href'		=> 'contao/main.php?do=user&amp;act=edit&amp;id=12',
	 * 							'title'		=> 'Edit user ID 12',
	 * 							'icon'		=> 'system/themes/default/images/edit.gif',
	 * 							'icon_w'	=> '12',
	 * 							'icon_h'	=> '16',
	 * 							'alt'		=> 'Edit user'				
	 * 						),
	 * 						'copy' => array
	 * 						(
	 * 							'href'		=> 'contao/main.php?do=user&amp;act=copy&amp;id=12',
	 * 							'title'		=> 'Duplicate user ID 12',
	 * 							'icon'		=> 'system/themes/default/images/copy.gif',
	 * 							'icon_w'	=> '14',
	 * 							'icon_h'	=> '16',
	 * 							'alt'		=> 'Duplicate user'				
	 * 						)
	 * 					)
	 * 				),
	 * 				array
	 * 				(
	 * 					'id'	=> 64,
	 * 					'class'	=> 'list_icon',
	 * 					'style'	=> 'background-image:url(\'system/themes/default/images/user.gif\');',
	 * 					'label'	=> 'Kevin Jones',
	 * 					'buttons'	=> array
	 * 					(
	 * 						'edit' => array
	 * 						(
	 * 							'href'		=> 'contao/main.php?do=user&amp;act=edit&amp;id=64',
	 * 							'title'		=> 'Edit user ID 64',
	 * 							'icon'		=> 'system/themes/default/images/edit.gif',
	 * 							'icon_w'	=> '12',
	 * 							'icon_h'	=> '16',
	 * 							'alt'		=> 'Edit user'				
	 * 						),
	 * 						'copy' => array
	 * 						(
	 * 							'href'		=> 'contao/main.php?do=user&amp;act=copy&amp;id=64',
	 * 							'title'		=> 'Duplicate user ID 64',
	 * 							'icon'		=> 'system/themes/default/images/copy.gif',
	 * 							'icon_w'	=> '14',
	 * 							'icon_h'	=> '16',
	 * 							'alt'		=> 'Duplicate user'				
	 * 						)					
	 * 					)
	 * 				)
	 * 				
	 * 			)
	 * 		)
	 * );
	 */
	public function generateListViewString($arrData)
	{
		// no data array or empty
		if(!$arrData || count($arrData) == 0)
		{
			return '<p class="tl_empty">' . $GLOBALS['TL_LANG']['MSC']['noResult'] . '</p>';
		}

		$strReturn = '<div class="tl_listing_container list_view">
						<table class="tl_listing">
  							<tbody>';
		
		// loop every group
		foreach($arrData as $arrGroupData)
		{
			$strReturn .= '<tr><td colspan="2" class="tl_folder_tlist">' . $arrGroupData['label'] . '</td></tr>';

			// loop the data of the group
			foreach($arrGroupData['data'] as $arrEntryData)
			{
				$strReturn .= '<tr onmouseover="Theme.hoverRow(this, 1);" onmouseout="Theme.hoverRow(this, 0);">
									<td class="tl_file_list">';
									
				// left column for the entry
				$strClass = ((strlen($arrEntryData['class'])) ? ' class="' . $arrEntryData['class'] . '"' : '');
				$strStyle = ((strlen($arrEntryData['style'])) ? ' style="' . $arrEntryData['style'] . '"' : '');
				$strReturn .= '<div' . $strClass . $strStyle . '>' . $arrEntryData['label'] . '</div>';
				$strReturn .= '</td>';

				// buttons, right column
				if(is_array($arrEntryData['buttons']) && count($arrEntryData['buttons']))
				{
					$strReturn .= '<td class="tl_file_list tl_right_nowrap">';
					
					foreach($arrEntryData['buttons'] as $arrButton)
					{
						$strReturn .= '<a href="' . $arrButton['href'] . '" title="' . $arrButton['title'] . '"><img src="' . $arrButton['icon'] . '" width="' . $arrButton['icon_w'] . '" height="' . $arrButton['icon_h'] . '" alt="' . $arrButton['alt'] . '"></a> ';
					}
					
					$strReturn .= '</td>';
				}
				
				$strReturn .= '</tr>';
			}
		}

		$strReturn .= '</tbody></table></div>';
		return $strReturn;		
	}
}

?>