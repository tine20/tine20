<?php

class Asterisk_Snomsoftware extends Zend_Db_Table_Abstract
{
	protected $_name = 'snom_software';
	protected $_primary = 'software_id';

	public function getTotalCount()
	{
		return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name);
	}
}

?>
        
        