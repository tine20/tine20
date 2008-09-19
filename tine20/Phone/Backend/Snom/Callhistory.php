<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Asterisk.php 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 *
 */

/**
 * call history backend for the Phone application
 * 
 * @package     Phone
 * @subpackage  Snom
 * 
 */
class Phone_Backend_Snom_Callhistory extends Tinebase_Abstract_SqlTableBackend
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        $this->_tableName = SQL_TABLE_PREFIX . 'phone_callhistory';
        $this->_modelName = 'Phone_Model_Call';
        $this->_db = Zend_Registry::get('dbAdapter');
        $this->_table = new Tinebase_Db_Table(array('name' => $this->_tableName));
    }    
    
    /**
     * start phone call and save in history
     *
     * @param Phone_Model_Call $_call
     * @return Phone_Model_Call
     */
    public function startCall(Phone_Model_Call $_call) {
        if ( empty($_call->id) ) {
            $newId = $_call->generateUID();
            $_call->setId($newId);
        }
        
        $_call->start = Zend_Date::now()->getIso();
        
        $call = $this->create($_call);
        
        return $call;
    }
}
