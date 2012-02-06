<?php 

define('NEWLINE','
');

// rewrite JS AJAX Calls for toggleSubpalette
$GLOBALS['TL_HOOKS']['executePostActions'][] = array('DCMemoryAjax', 'myExecutePostActions');
$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/3cframework/html/backend.js';


$GLOBALS['BE_FFL']['statictext'] = 'StaticText';

class DCMemoryAjax extends Backend
{
	
	public function myExecutePostActions($strAction, DataContainer $dc)
	{
			
		if ($strAction == 'toggleDCMemorySubpalette')
		{
			if ($dc instanceof DC_Memory)
			{
				$dc->setData($this->Input->post('field'),(intval($this->Input->post('state') == 1) ? 1 : ''));

				if ($this->Input->post('load'))
				{
					echo $dc->edit(false, $this->Input->post('id'));
				}
			}
		}
	}
}
?>