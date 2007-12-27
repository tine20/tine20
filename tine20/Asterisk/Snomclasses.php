<?php

class Asterisk_Snomclasses extends Zend_Db_Table_Abstract
{
	protected $_name = 'snom_classes';
	protected $_primary = 'class_id';

	public function getTotalCount()
	{
		return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name);
	}
}

?>
        
        