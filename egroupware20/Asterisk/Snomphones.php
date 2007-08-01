<?php

class Asterisk_Snomphones extends Zend_Db_Table_Abstract
{
	protected $_name = 'snom_phones';
	protected $_primary = 'phone_id';

	public function getTotalCount()
	{
		return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name);
	}
	
	protected function _setupMetadata()
	{
		$this->_cols = array('phone_id', 'macaddress', 'description', 'phoneswversion', 'lastmodify', 'class_id', 'phonemodel');
	}
}

?>
        
        