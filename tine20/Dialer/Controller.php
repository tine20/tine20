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
 * controller class for the Dialer application
 * 
 * @package     Dialer
 */
class Dialer_Controller
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Dialer_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Dialer_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Dialer_Controller;
        }
        
        return self::$_instance;
    }
    
    public function dialNumber($_number)
    {
        if(isset(Zend_Registry::get('configFile')->asterisk)) {
            $url = Zend_Registry::get('configFile')->asterisk->managerurl;
            $username = Zend_Registry::get('configFile')->asterisk->managerusername;
            $password = Zend_Registry::get('configFile')->asterisk->managerpassword;
        } else {
            throw new Exception('AJAM settings not found in config.ini');
        }
        
        $extension = $this->getPreferedExtension(Zend_Registry::get('currentAccount'));
        
        $ajam = new Asterisk_Ajam_Connection($url);
        $ajam->login($username, $password);
        $ajam->originate($extension['device'], $extension['context'], $_number, 1, $extension['callerid']);
        $ajam->logout();
    }
    
    protected function getPreferedExtension($_accountId)
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);
        
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