<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');



class libContaoConnector extends Backend
{

	protected $arrKeysTypes = array();
	protected $arrTypesAvailable = array();

	public function __construct($tablename,$colName,$colAlias)
	{
		parent::__construct();

		if ((isset($GLOBALS['TL_DCA'][$tablename]['fields'])) &&
		(is_array(($GLOBALS['TL_DCA'][$tablename]['fields']))))
		{
			foreach ($GLOBALS['TL_DCA'][$tablename]['fields'] as $sourceKeys=>$sourceFields)
			{
				$this->arrTypesAvailable[] = $sourceKeys;
			}
		}

		$this->arrTypesAvailable[] = 'id';
		$this->arrTypesAvailable[] = 'pid';
		$this->arrTypesAvailable[] = 'tstamp';
		
		$this->arrTypesAvailable[] = '__tableName';
		$this->arrTypesAvailable[] = '__colName';
		$this->arrTypesAvailable[] = '__colAlias';

		
		$this->__set('__tableName',$tablename);
		$this->__set('__colName',$colName);
		$this->__set('__colAlias',$colAlias);
		$this->__set('tstamp',time());

		$this->Sync();

	}


	public function __set($strKey, $varValue)
	{

		if (in_array($strKey,$this->arrTypesAvailable))
		{
			if (is_array($varValue))
				$varValue = serialize($varValue);
		
			$this->arrKeysTypes[$strKey]=$varValue;
				
			//echo $strKey.'-'.$varValue.'<br>';
		}
		else
		{
		//	throw new Exception(sprintf("column name [%s] is unknown",$strKey));
			
			switch ($strKey)
			{
				default:
					break;
			}
		}
	}


	public function __get($strKey)
	{

		if (in_array($strKey,$this->arrTypesAvailable))
		{
			return $this->arrKeysTypes[$strKey];
		}
		else
		{
			switch ($strKey)
			{
				default:

					break;
			}
		}
	}

	
	public function Dump()
	{
	
		return $this->arrKeysTypes;
	}

	public function Sync()
	{
		if ($this->__tableName==NULL)
		{
			throw new Exception("tableName is empty");
			return;
		}
		

		$arrCols = $this->Database->listFields($this->__tableName);
		
		foreach ($arrCols as $col)
		{
			if (!in_array($col['name'],$this->arrTypesAvailable))
			{
				$this->arrTypesAvailable[]=$col['name'];
			}
		}
		
		$objData = $this->Database->prepare("SELECT * FROM ".$this->__tableName." WHERE ".$this->__colName."=?")
		->limit(1)
		->executeUncached($this->__colAlias);
			
		
		if ($objData->numRows==0)
		{
			$arrSet = $this->arrKeysTypes;
			
			unset($arrSet['__tableName']);
			unset($arrSet['__colName']);
			unset($arrSet['__colAlias']);
			
			$arrSet[$this->__colName] = $this->__colAlias;
			
			$objNewCat = $this->Database->prepare("INSERT INTO ".$this->__tableName." %s")
			->set($arrSet)
			->executeUncached();
			
			$this->id = $objNewCat->insertId;
		}
		else
		{
			$arrData = $objData->fetchAllAssoc();

			foreach ($arrData[0] as $key=>$value)
			{
				if (($this->__get($key)=="") &&
					($value))
				{
					$this->__set($key,$value);
					
				}
			}
		}

		foreach ($this->arrKeysTypes as $objKey=>$objField)
		{
			if (($objField) &&
				(strpos($objKey,'__')!==0))
			{
	
				$objUpdateFields = $this->Database->prepare("UPDATE ".$this->__tableName." SET ".$objKey."=? WHERE id=?")
					->execute($objField,$this->id);
				
	
			}
		}
	}

	
	public function generateCode($alias,$arrHide=array())
	{
		$arrOutput=array();
		
		$arrHideCols = array('tstamp','id','__tableName','__colName','__colAlias',$this->__colName);
		//$arrHideCols = array_merge($arrHideCols,$arrHide);
		
		
		$arrOutput[] = '';
		//$arrOutput[] = sprintf('$%s = new ContaoConnector("%s","%s","%s");',$alias,$this->__tableName,$this->__colName,$this->__colAlias);
		
		
		foreach ($this->arrKeysTypes as $key=>$value)
		{
			
			if (($value) &&
				(!in_array($key,$arrHideCols)))
			{
			
				
				if (!is_numeric($value))
				{
					$value="'".$value."'";
				}
				
			
				if (array_key_exists($key,$arrHide))
				{
					if (!$arrHide[$key])
					{
						//only key, remove from code
						continue;
					}
					else
					{
					
						$value = $arrHide[$key];
					}
				}
				
				$arrOutput[] = sprintf('$%s->%s = %s;',$alias,$key,$value);
				
			}
		}
		
		$arrOutput[] = '';
		$arrOutput[] = sprintf('$%s->Sync();',$alias);
		
		
		return implode("<br>",$arrOutput);
		
	}

}


?>
