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

class tplCompound
{
	protected $arrCompounds = null;
	protected $arrInnerCompounds = null;
	protected $arrInnerButtonsCompounds = null;
	protected $intId;
	protected $template;
	protected $arrData;
	
	public function __construct($intId,$strTpl = 'compound_empty',$arrData  = array())
	{
		$this->arrCompounds = array();
		$this->arrInnerCompounds = array();
		$this->arrInnerButtonsCompounds = array();
		
		$this->intId = $intId;
		$this->template = $strTpl;
		
		if (is_array($arrData))
			$this->arrData = $arrData;
		else
		{
			$this->arrData = array('value'=>$arrData);
		}
	}	

	
	public function addInner(tplCompound $comp)
	{
		$this->arrInnerCompounds[] = $comp;
	}
	
	public function addInnerButtons(tplCompound $comp)
	{
		$this->arrInnerButtonsCompounds[] = $comp;
	}

	public function add(tplCompound $comp)
	{
		$this->arrCompounds[] = $comp;
	}
	
	public function parse($indent=0)
	{
		$strReturn = '';
		$strInnerReturn = '';
		$strInnerButtonReturn = '';
		
		if (count($this->arrInnerCompounds))
		{
			foreach ($this->arrInnerCompounds as $cmp)
			{
				$strInnerReturn .= $cmp->parse($indent+1);
			}
		}
		
		if (count($this->arrInnerButtonsCompounds))
		{
			foreach ($this->arrInnerButtonsCompounds as $cmp)
			{
				$strInnerButtonReturn .= $cmp->parse($indent+1);
			}
		}
		
		$cmpTemplate = new BackendTemplate($this->template);
		
		foreach ($this->arrData as $k=>$v)
		{
			$cmpTemplate->$k = $v;
		}
		
		$cmpTemplate->_innerData = $strInnerReturn;
		$cmpTemplate->_innerButtonData = $strInnerButtonReturn;
		$cmpTemplate->_intClass = $this->intId;
		
		$strReturn = NEWLINE.str_repeat(" ", $indent).'<!-- '.$this->intId.' -->'.NEWLINE;
		
		$arrData = explode(NEWLINE,$cmpTemplate->parse(++$indent));
		
		foreach ($arrData as $strData)
		{
			if ($strData)
				$strReturn.=str_repeat(" ", $indent).$strData.NEWLINE;	
		
		}
		
		if (count($this->arrCompounds))
		{
			foreach ($this->arrCompounds as $cmp)
			{
				$strReturn .= $cmp->parse($indent+1);
			}
		}
		
		return $strReturn;
	}
	
	
	
	public function addData($key,$value)
	{
		$this->arrData[$key] = $value;
	}
	
	
	
}

?>