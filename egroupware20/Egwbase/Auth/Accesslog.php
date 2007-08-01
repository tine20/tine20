<?php

class Egwbase_Auth_Accesslog extends Zend_Db_Table_Abstract
{
	protected $_name = 'egw_access_log';
	protected $_primary = 'log_id';

	public function getTotalCount()
	{
		return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name);
	}
}

?>
        
        