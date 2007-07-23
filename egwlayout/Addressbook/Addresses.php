<?php

class Addressbook_Addresses extends Zend_Db_Table_Abstract
{
	protected $_name = 'egw_addressbook';
	protected $_primary = 'contact_id';

	public function getTotalCount()
	{
		return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name);
	}
}

?>
        
        