<?php
class Egwbase_Auth_Accesslog
{
	protected $_accesslogger = null;
	
	public function __construct()
	{
		if ( !Zend_Registry::get('dbConfig')->get('egw14compat') == 1 )
		{
			$this->_accesslogger = new _Egwbase_Auth_Accesslog();
		}
	}
	
	public function __call( $_functionname, $_arguments );
	{
		if ( $this->_accesslogger instanceof _Egwbase_Auth_Accesslog )
		{
			return call_user_func_array( array( $this->_accesslogger, $_functionname ), $_arguments );
		}
	}
}

class _Egwbase_Auth_Accesslog extends Zend_Db_Table_Abstract
{
	protected $_name = 'egw_access_log';
	protected $_primary = 'log_id';

	public function getTotalCount()
	{
		return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name);
	}
	
}

?>
        
        