<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

class myDC extends DataContainer
{

	public function setId($intId)
	{
		$this->intId = $intId;
	}
}

class libCatalogConnector extends Backend
{

	protected $arrKeysTypes = array();
	protected $arrTypesAvailable = array();
	
	protected $dcaName = '';


	public $Fields=array();

	public function __construct($strTableName,$strDCAName='',$bRecrreate=false)
	{
		parent::__construct();
	
		$this->import("Database");
		$this->import("Catalog");
		$this->import("CatalogExt");
		
		$arrTables = $this->Database->listTables();
				
		if (!in_array($strTableName,$arrTables))
		{
			$createTableStatement = "
			CREATE TABLE `%s` (
				`id` int(10) unsigned NOT NULL auto_increment,
				`pid` int(10) unsigned NOT NULL,
				`sorting` int(10) unsigned NOT NULL default '0',
				`tstamp` int(10) unsigned NOT NULL default '0',
				PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8";
			
			$statement = sprintf($createTableStatement, $strTableName);
			
			$this->Database->executeUncached($statement);
		}
		
		
		$this->loadDataContainer("tl_catalog_types");
		$this->loadDataContainer("tl_catalog_fields");
		
		$this->loadLanguageFile("backend");
		
		if ((isset($GLOBALS['TL_DCA']['tl_catalog_types']['fields'])) &&
			(is_array(($GLOBALS['TL_DCA']['tl_catalog_types']['fields']))))
		{
			foreach ($GLOBALS['TL_DCA']['tl_catalog_types']['fields'] as $sourceKeys=>$sourceFields)
			{
				$this->arrTypesAvailable[] = $sourceKeys;
			}
		}

		$this->arrTypesAvailable[] = 'id';
		$this->arrTypesAvailable[] = 'pid';
		$this->arrTypesAvailable[] = 'tstamp';

		
		if ($strDCAName=='')
		{
			$strDCAName = $strTableName;
		}

		$this->__set('tableName',$strTableName);
		$this->__set('tstamp',time());

		$this->Fields = (object) array();
		
		
		$this->Sync($strDCAName);

		if ($bRecrreate)
			$this->recreateFromDCA($strDCAName);

	}


	public function registerField($fieldName,$fieldValue=null)
	{
		$this->Fields->$fieldName = new CatalogConnectorFields($fieldValue);

	}


	public function __set($strKey, $varValue)
	{

		if ((in_array($strKey,$this->arrTypesAvailable)) &&
		($varValue))
		{
			$this->arrKeysTypes[$strKey]=$varValue;
				
			//echo $strKey.'-'.$varValue.'<br>';
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


	public function Sync($strDCAName)
	{
		if ($this->tableName==NULL)
		{
			echo "exit";
			return;
		}
		
		$objData = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE tableName=?")
						->limit(1)
						->executeUncached($this->tableName);
							
						
		if ($objData->numRows==0)
		{
			$objNewCat = $this->Database->prepare("INSERT INTO tl_catalog_types %s")
							->set($this->arrKeysTypes)
							->executeUncached();

			$this->loadDataContainer($this->tableName);
			$dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strDCAName]['config']['dataContainer'];
			require_once(sprintf('%s/system/drivers/%s.php', TL_ROOT, $dataContainer));

			$dc = new $dataContainer($strDCAName);	

			$cat = new Catalog();
			
			$cat->renameTable($this->tableName, $dc);
			

			$this->__set('id',$objNewCat->insertId);
		}
		
		
		$objData = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE tableName=?")
			->limit(1)
			->executeUncached($this->tableName);
		
		$arrData = $objData->fetchAllAssoc();

		foreach ($arrData[0] as $key=>$value)
		{
			if ($this->__get($key)=="")
			{
				$this->__set($key,$value);
			}
		}
		
		$this->loadDataContainer($this->tableName);
		$this->loadDataContainer($strDCAName);
		$sorting=0;
		

		
		$objColumns = $this->Database->prepare("SHOW COLUMNS FROM ".$this->tableName)->execute();
		$arrColumns=array();
		foreach ($objColumns->fetchAllAssoc() as $col)
		{
			$arrColumns[$col['Field']] = $col;
		}
		
		foreach ($this->Fields as $objKey=>$objField)
		{
			if (isset($this->Fields))
			{
				$arrItems =$this->Fields->$objKey->getAllVars();
				
				$arrItems['sorting'] = $sorting++;
					
				if ($arrItems)
				{
					$objUpdateFields = $this->Database->prepare("UPDATE tl_catalog_fields %s WHERE colName=?")
						->set($arrItems)
						->executeUncached($objKey);
						
				}
				
				
				$createColumnStatement = "ALTER TABLE %s ADD %s %s";
				$renameColumnStatement = "ALTER TABLE %s CHANGE COLUMN %s %s %s";
				
				$fieldType = $GLOBALS['TL_DCA'][$this->tableName]['fields'][$objKey]['inputType'];
				
				
				if (!$fieldType)
					$fieldType='text';
				
				
				$sqlDef = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType]['sqlDefColumn'];
				
				
				if (!$sqlDef)
					$sqlDef='text NULL';
				
				
				
				if ($this->Database->fieldExists($objKey, $this->tableName))
				{
					$statement = sprintf($renameColumnStatement, $this->tableName, $objKey, $objKey, $sqlDef);
					
						//$this->Database->execute($statement);	
					//	$arrLogInfo[]= sprintf("Feld [%s] angepasst",$objKey);
					
				}
				else
				{
					$statement = sprintf($createColumnStatement, $this->tableName, $objKey, $sqlDef);
					
					$this->Database->execute($statement);	
					$arrLogInfo[]= sprintf("Erzeuge Feld [%s]",$objKey);
				}
				
				
		
				
			}
		}

		foreach ($this->arrKeysTypes as $objKey=>$objField)
		{
			if ($objField)
			{
			
				$objUpdateFields = $this->Database->prepare("UPDATE tl_catalog_types SET ".$objKey."=? WHERE tableName=?")
					->executeUncached($objField,$this->tableName);
				
			}
		}
	}

	public function recreateFromDCA($strDCAName)
	{
		$this->import("Catalog");
		
		$this->loadDataContainer($strDCAName);
		$this->loadLanguageFile($strDCAName);

		$sqlParentTable = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=?")
			->executeUncached($this->__get('id'));
			
			
		$cat = new Catalog();
		$catalogDCA=$cat->getCatalogDca($this->id);
		
			
		$arrSQLAvailableColums=$sqlParentTable->fetchAllAssoc();
		$arrAvailableColums = array();
		
		foreach ($arrSQLAvailableColums as $value)
		{
			$arrAvailableColums[$value['colName']] = $value;
		}
				
				
		$arrSubPalettes = array();
		$lastField = '';
		$arrLegend = array();
		
		if (is_array($GLOBALS['TL_DCA'][$strDCAName]['subpalettes']))
		{
			foreach ($GLOBALS['TL_DCA'][$strDCAName]['subpalettes'] as $subKey=>$subValue)
			{
				
				$strSubPalettes = str_replace(";",",",$subValue);
				$arrSub = explode(",",$strSubPalettes);

				foreach ($arrSub as $sub)
				{
					$arrSubPalettes[$sub] = $subKey;
					
				}
				
			}
		}



		$strSubPalettes = str_replace(";",",",$GLOBALS['TL_DCA'][$strDCAName]['palettes']['default']);
		$arrSub = explode(",",$strSubPalettes);

		foreach ($arrSub as $sub)
		{
			if (strpos($lastField,"{")!==false)
			{
				$langKey = trim($lastField,'{}');
				
				
				$arrLegend[$sub] = strlen($GLOBALS['TL_LANG'][$strDCAName][$langKey]) ? $GLOBALS['TL_LANG'][$strDCAName][$langKey] : $langKey;
			}
			
			$lastField = $sub;
		}
			
		$sorting=1;
		
		foreach ($GLOBALS['TL_DCA'][$strDCAName]['fields'] as $key=>$field)
		{
			$arrLogInfo = array();	
			
			if (!in_array($key,array_keys($arrAvailableColums)))
			{
				$arrField = array(
					'pid' =>$this->__get('id'),
					'name'	=> ($field['label'][0] ? $field['label'][0] : $key),
					'sorting'	=> $sorting,
					'description'	=> ($field['label'][1] ? $field['label'][1] : $key),
					'colName'	=> $key,
					'type'	=> $field['inputType'],
					'tstamp' => time(),

				);
				

				if ($field['inputType']=='fileTree')
				{
					$arrField['type']='file';
				}

				if ($field['inputType']=='text')
				{
					$arrField['textHeight'] = 20;
					$arrField['type'] = 'text';
				}
				
				
				if ($field['inputType']=='textarea')
				{
					$arrField['type']='longtext';
					$arrField['textHeight'] = 0;
				}
				
				
				if ($field['inputType']=='radio')
				{
					$arrField['type']='select';
				}
				

				if ((array_key_exists('eval',$field)) &&
					(array_key_exists('rgxp',$field['eval'])))
				{			
				
					if ($field['eval']['rgxp']=='date')	
						$arrField['type']='date';
						
					
					if ($field['eval']['rgxp']=='datim')	
						$arrField['type']='date';							
						
					if ($field['eval']['rgxp']=='digit')	
						$arrField['type']='decimal';		
						
					if ($field['eval']['rgxp']=='url')	
						$arrField['type']='url';
				
				}
				if ($field['eval']['mandatory'])	
					$arrField['mandatory']='1';							
							
				if ($field['eval']['multiple'])	
					$arrField['multiple']='1';							
							
				if ($field['eval']['rte'])
				{	
					$arrField['rte']='1';							
					$arrField['rte_editor']=$field['eval']['rte'];							
				}
							
				if ($field['eval']['allowHtml'])	
				{
					$arrField['allowHtml']='1';							
				}		

				if (strpos($field['eval']['tl_class'],'w50')!==false)	
					$arrField['width50']='1';							
					
			
				if (((array_key_exists('eval',$field))) && (array_key_exists('catalog',$field['eval'])))
				{
					foreach ($field['eval']['catalog'] as $catKey=>$catValue)
					{
						$arrField[$catKey]=$catValue;
					}
				}
						
				if (($arrField['type']=="select") ||
					($arrField['type']=="tags"))
				{
					if (array_key_exists('foreignKey',$field))
					{					
						$foreignKey=explode('.',$field['foreignKey']);
						
						$itemTable = $foreignKey[0];
						$itemTableColumn = $foreignKey[1];
						
						
					}
					else
					{
						//add Data to tl_taxanomy

						$sqlSourceTaxonomy = $this->Database->prepare("SELECT id,name FROM tl_taxonomy WHERE alias=?")
												->limit(1)
												->executeUncached(standardize($this->tableName));
							
						$mySourceID = $sqlSourceTaxonomy->id;

						if ($sqlSourceTaxonomy->numRows==0)
						{
							
							$arrSourceTaxanomy = array(
								'name' =>$this->tableName,
								'alias' => standardize($this->tableName),
								'tstamp' => time(),
								'sorting' => 128
							);

							$sqlSourceTaxonomy = $this->Database->prepare("INSERT INTO tl_taxonomy %s")
							->set($arrSourceTaxanomy)
							->execute();

							$mySourceID = $sqlSourceTaxonomy->insertId;

						}

						$sqlTaxonomy = $this->Database->prepare("SELECT id,name FROM tl_taxonomy WHERE name=?")
							->limit(1)
							->executeUncached($key);
							
						$myID = $sqlTaxonomy->id;
						if ($sqlTaxonomy->numRows==0)
						{
							$arrNewTax = array(
								'name' => $key,
								'alias' => standardize($key),
								'pid'	=> $mySourceID,
								'tstamp' => time(),
							);

							$sqlTaxonomy = $this->Database->prepare("INSERT INTO tl_taxonomy %s")
							->set($arrNewTax)
							->execute();

							$myID = $sqlTaxonomy->insertId;
							
							
						}
						
						$itemTable = 'tl_taxonomy';
						$itemTableColumn = 'name';

						if (array_key_exists('options_callback',$field))
						{
							$callback = $field['options_callback'];
							$this->import($callback[0]);

							$objCallback = new $callback[0]();

							$field['options'] = $objCallback->$callback[1]();
						}
							

						$checkIDs = array();
							
							
						foreach ($field['options'] as $optKey=>$optValue)
						{
							if ($optValue)
							{
								$arrNewTax = array(
										'name' => $optValue,
										'alias'	=> (is_numeric($optKey) ? standardize($optValue) : standardize($optKey)),
										'pid'	=> $myID,
										'tstamp' => time(),
									
								);

								$sqlNewTaxonomy = $this->Database->prepare("INSERT INTO tl_taxonomy %s")
								->set($arrNewTax)
								->execute();

								$checkIDs[] = $sqlNewTaxonomy->insertId;
							}
						}

						
						$arrField['limitItems']='1';
						$arrField['items']=serialize($checkIDs);
					}
					
					
					$arrField['itemTable']=$itemTable;
					$arrField['itemTableValueCol']=$itemTableColumn;
					$arrField['itemSortCol']=$itemTableColumn;
					
					$arrField['childrenSelMode'] = 'items';
					
					
				
				}


				if (array_key_exists($key,$arrLegend))
				{
					list($strLegend,$strHide) = explode(':',$arrLegend[$key]);
					
					$arrField['insertBreak'] = '1';
					$arrField['legendTitle'] = $strLegend;
					
					$arrField['legendHide'] = strlen($strHide) ? '1' : '';
				}
					
				
				if (array_key_exists($key,$arrSubPalettes))
				{
					$arrField['parentCheckbox']=$arrSubPalettes[$key];
				}

				$this->registerField($key,$field);

				$arrField['tstamp']=time();


				$sorting++;
				
				foreach ($arrField as $key=>$value)
				{
					if (!$value)
						unset($arrField[$key]);
				}
				
				$objNewField = $this->Database->prepare("INSERT INTO tl_catalog_fields %s")
					->set($arrField)
					->executeUncached();

			}
			else
			{
				// check consistency

				if ($catalogDCA['fields'][$key] != $field)
				{
					$catDCA = $catalogDCA['fields'][$key];
					$fieldDCA = $field;
					
					$arrUnsets = array('default','reference','search','filter','sorting','save_callback','load_callback','options');
					
					foreach ($arrUnsets as $set)
					{
						unset($catDCA[$set]);
						unset($fieldDCA[$set]);
					}
					
					$arrDiffed = $this->arrayRecursiveDiff($catDCA,$fieldDCA);
					
					if (array_key_exists('label',$arrDiffed))
					{
							
						$objUpdateFields = $this->Database->prepare("UPDATE tl_catalog_fields SET name=?, description=? WHERE colname=? AND pid=?")
							->executeUncached($fieldDCA['label'][0],$fieldDCA['label'][1],$key,$this->__get('id'));
						
							
						$arrLogInfo[]= sprintf("Sprache fuer Feld  [%s] angepasst",$key);
						
						unset($arrDiffed['label']);
					}
					
					
					
					
					if (array_key_exists('eval',$arrDiffed))
					{
							
						if ($catDCA['eval']['catalog']['type']!=$fieldDCA['eval']['catalog']['type'])
						{
							
							if (!array_key_exists('eval',$fieldDCA))
							{
								unset($arrDiffed['eval']);
								
							}
							else
								{
								
								
								if (array_key_exists('mandatory',$fieldDCA['eval']))
								{
									if ($catDCA['eval']['mandatory']!=$fieldDCA['eval']['mandatory'])
									{
										// update mandatory field
										
										$objUpdateFields = $this->Database->prepare("UPDATE tl_catalog_fields SET mandatory=?,sortBy='name_asc' WHERE colname=? AND pid=?")
											->executeUncached(($fieldDCA['eval']['mandatory']) ? '1' : '',$key,$this->__get('id'));
							
										$arrLogInfo[]= sprintf("Datentyp 'mandatory' fuer Feld  [%s] angepasst",$key);
							
										unset($arrDiffed['eval']['mandatory']);
									}
								}
								else
								{
									// update mandatory field
										
									unset($arrDiffed['eval']['mandatory']);
								}

								
								
								if (array_key_exists('unique',$fieldDCA['eval']))
								{
									if ($catDCA['eval']['unique']!=$fieldDCA['eval']['unique'])
									{
										// update unique field
										
										$objUpdateFields = $this->Database->prepare("UPDATE tl_catalog_fields SET uniqueItem=?,sortBy='name_asc' WHERE colname=? AND pid=?")
											->executeUncached(($fieldDCA['eval']['unique']) ? '1' : '',$key,$this->__get('id'));
							
										$arrLogInfo[]= sprintf("Datentyp 'unique' fuer Feld  [%s] angepasst",$key);
							
										unset($arrDiffed['eval']['unique']);
									}
								}
								else
								{
									// a really unused field
										
									unset($arrDiffed['eval']['unique']);
								}
											
								
								if (array_key_exists('multiple',$fieldDCA['eval']))
								{
									if ($catDCA['eval']['catalog']['multiple']!=$fieldDCA['eval']['multiple'])
									{
										// update multiple field
										
										$objUpdateFields = $this->Database->prepare("UPDATE tl_catalog_fields SET multiple=?,sortBy='name_asc' WHERE colname=? AND pid=?")
											->executeUncached(($fieldDCA['eval']['multiple']) ? '1' : '',$key,$this->__get('id'));
										
										$arrLogInfo[]= sprintf("Datentyp 'multiple' fuer Feld  [%s] angepasst",$key);
							
										unset($arrDiffed['eval']['multiple']);
									}
								}
								else
								{
									// a really unused field
										
									unset($arrDiffed['eval']['multiple']);
								}
											
								if ($catDCA['eval']['inputType']=='fileTree')
								{
									if ($catDCA['eval']['fieldType']!=$fieldDCA['eval']['fieldType'])
									{
										if ($fieldDCA['eval']['fieldType']=='checkbox')
										{
											// update fileTree field
											
											$objUpdateFields = $this->Database->prepare("UPDATE tl_catalog_fields SET multiple=1,sortBy='name_asc' WHERE colname=? AND pid=?")
												->executeUncached($key,$this->__get('id'));
											
											$arrLogInfo[]= sprintf("Datentyp 'fileTree-multicheck' fuer Feld  [%s] angepasst",$key);
								
											unset($arrDiffed['eval']['fieldType']);
										}
										
										if ($fieldDCA['eval']['fieldType']=='radio')
										{
											// update fileTree field
											
											$objUpdateFields = $this->Database->prepare("UPDATE tl_catalog_fields SET multiple='',sortBy='name_asc' WHERE colname=? AND pid=?")
												->executeUncached($key,$this->__get('id'));
											
											$arrLogInfo[]= sprintf("Datentyp 'fileTree-singlecheck' fuer Feld  [%s] angepasst",$key);
								
											unset($arrDiffed['eval']['fieldType']);
										}
									}
								}
								
								
								unset($arrDiffed['eval']['catalog']);
								unset($arrDiffed['eval']['style']);
								unset($arrDiffed['eval']['allowHtml']);
								unset($arrDiffed['eval']['datepicker']);
								
									
								if (count($arrDiffed['eval'])==0)
								{
									unset($arrDiffed['eval']);
								
								}
							}
						}
					}
					
					if (array_key_exists('inputType',$arrDiffed))
					{
						$arrLogInfo[]= sprintf("Datentyp fuer Feld  [%s] angepasst",$key);
						
						/*
						echo "<hr>inputTYPE ".$key."<br>";
						print_a($arrDiffed);
						
						*/
						unset($arrDiffed['inputType']);
					}
					
					if (count($arrDiffed)>0)
					{
						/*
					
						echo "<hr>".$key."<br>";
						
						print_a($arrDiffed);
						print_a($catDCA);
						print_a($fieldDCA);
						
						*/
					}
					
					//TODO SL : es fehlen die Taxonomien
					
				}

				$this->registerField($key,$field);
			}

				$arrOutputLog = array();
				foreach ($arrLogInfo as $value)
				{
					$arrOutputLog[] = '<li>'.$value.'</li>';
				}
				//$_SESSION['TL_INFO'][] = implode(' - ',$arrLogInfo);
		}


	}
	
	function arrayRecursiveDiff($aArray1, $aArray2) 
	{
		$aReturn = array();

		foreach ($aArray1 as $mKey => $mValue) 
		{
			if (array_key_exists($mKey, $aArray2)) 
			{
				if ((is_array($mValue)) && (array_key_exists($mKey, $aArray2)) && (is_array($aArray2[$mKey]))) 
				{
					$aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
        
					if (count($aRecursiveDiff)) 
					{ 
						$aReturn[$mKey] = $aRecursiveDiff; 
					}
				} 
				else 
				{
					if ($mValue != $aArray2[$mKey]) 
					{
						$aReturn[$mKey] = $mValue;
					}
				}
			}	 
			else 
			{
				$aReturn[$mKey] = $mValue;
			}
		}
		return $aReturn;
	} 


	
	public function generateCode($alias,$arrHide=array())
	{
		$arrOutput=array();
		
		$arrHideCols = array('tstamp','id','__tableName','__colName','__colAlias',$this->__colName);
		//$arrHideCols = array_merge($arrHideCols,$arrHide);
		
		
		$arrOutput[] = '';
		$arrLang[] = '';
		
		
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
		
	/*	
		$arrOutput[] = '<hr>';
		
		$objLangs = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=? ORDER by sorting")->execute($this->id);
		
		while ($objLangs->next())
		{
			$arrOutput[] = sprintf('$GLOBALS[\'TL_LANG\'][\'%s\'][\'%s\'] = array(\'%s\',\'%s\');',$this->tableName,$objLangs->colName,$objLangs->name,$objLangs->description);
		}
		*/
		return implode("<br>",$arrOutput);
	}
}



class CatalogConnectorFields
{
	protected $arrKeysTypes = array();
	protected $arrTypesAvailable = array();
	protected $arrValue = array();


	public function __construct($arrValue)
	{

		$this->arrTypesAvailable=array();

		if ((isset($GLOBALS['TL_DCA']['tl_catalog_fields']['fields'])) &&
		(is_array(($GLOBALS['TL_DCA']['tl_catalog_fields']['fields']))))
		{
			foreach ($GLOBALS['TL_DCA']['tl_catalog_fields']['fields'] as $sourceKeys=>$sourceFields)
			{
				$this->arrTypesAvailable[] = $sourceKeys;
			}
		}
		$this->arrValue = $arrValue;
		
	}


	public function __set($strKey, $varValue)
	{

		if (in_array($strKey,$this->arrTypesAvailable))
		{
			$this->arrKeysTypes[$strKey]=$varValue;
		}
	}


	public function __get($strKey)
	{

		if (in_array($strKey,$this->arrTypesAvailable))
		{
			return $this->arrKeysTypes[$strKey];
		}
	}

	public function getAllVars()
	{
		return $this->arrKeysTypes;
	}


}
?>