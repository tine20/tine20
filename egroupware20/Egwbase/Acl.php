<?php
class Egwbase_Acl extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_acl';
    
    /**
     * the numeric id of the user the read the acl from the sql table
     *
     * @var int
     */
    protected $accountId;
    
    const READ = 1;
    const ADD = 2;
    const EDIT = 4;
    const DELETE = 8;
    const PERSONAL = 16;
    
    public function __construct($accountId = NULL)
    {
        if($accountId === NULL) {
            $currentAccount = Zend_Registry::get('currentAccount');
            
            $this->accountId = $currentAccount->account_id;
        } else {
            $this->accountId = $accountId;
        }
    }
    
    public function getGrants($app)
    {
        
    }
}
