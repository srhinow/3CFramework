<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

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