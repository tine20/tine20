<?php
/**
 * Tine 2.0
 * 
 * @package     Dialer
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * asterisk backend for the Dialer application
 * 
 * @package     Dialer
 */
class Dialer_Backend_Asterisk
{
    /**
     * Enter description here...
     *
     * @var Asterisk_Ajam_Connection
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
        $this->_ajam = new Asterisk_Ajam_Connection($_url);
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
     * @var Dialer_Backend_Asterisk
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Dialer_Backend_Asterisk
     */
    public static function getInstance($_url, $_username, $_password) 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Dialer_Backend_Asterisk($_url, $_username, $_password);
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
     * @param int $_accountId the id of the account to get the prefered extension for
     * @return array
     */
    public function getPreferedExtension($_accountId)
    {
        $accountId = Tinebase_User_Model_User::convertAccountIdToInt($_accountId);
        
        $extensionsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'dialer_extensions'));
        
        $select  = $extensionsTable->select()
            ->where('account_id = ?', $accountId);

        $row = $extensionsTable->fetchRow($select);
        
        if($row === NULL) {
            throw new Exception('no prefered extension found');
        }
        
        return $row->toArray();
    }
}