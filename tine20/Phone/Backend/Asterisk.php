<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * asterisk backend for the Phone application
 * 
 * @package     Phone
 */
class Phone_Backend_Asterisk
{
    /**
     * Enter description here...
     *
     * @var Voipmanager_Ajam_Connection
     */
    protected $_ajam;
    
    protected $_username;
    
    protected $_password;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct($_url, $_username, $_password) 
    {
        $this->_ajam = new Voipmanager_Ajam_Connection($_url);
        $this->_username = $_username;
        $this->_password = $_password;
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Phone_Backend_Asterisk
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Backend_Asterisk
     */
    public static function getInstance($_url, $_username, $_password) 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Phone_Backend_Asterisk($_url, $_username, $_password);
        }
        
        return self::$_instance;
    }
    
    /**
     * initiate new call
     *
     * http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+Originate
     * 
     * @param string $_channel
     * @param string $_context
     * @param string $_exten
     * @param int $_priority
     * @param string $_callerId
     */
    public function dialNumber($_channel, $_context, $_exten, $_priority, $_callerId="Ajam Service")
    {
        $this->_ajam->login($this->_username, $this->_password);
        $this->_ajam->originate($_channel, $_context, $_exten, $_priority, $_callerId);
        $this->_ajam->logout();
    }
    
    /**
     * get prefered extension of this account
     *
     * @param   int $_accountId the id of the account to get the prefered extension for
     * @return  array
     * @throws  Phone_Exception_NotFound
     */
    public function getPreferedExtension($_accountId)
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        $extensionsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'phone_extensions'));
        
        $select  = $extensionsTable->select()
            ->where('account_id = ?', $accountId);

        $row = $extensionsTable->fetchRow($select);
        
        if($row === NULL) {
            throw new Phone_Exception_NotFound('No prefered extension found.');
        }
        
        return $row->toArray();
    }
}