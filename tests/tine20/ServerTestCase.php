<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Calendar_Backend_Sql
 * 
 * @package     Tests
 */
abstract class ServerTestCase extends PHPUnit_Framework_TestCase
{
    protected $preserveGlobalState = false;
    protected $runTestInSeparateProcess = true;
    
    /**
     * transaction id if test is wrapped in an transaction
     * 
     * @var string
     */
    protected $_transactionId = null;
    
    /**
     * @var Zend_Config
     */
    protected $_config;
    
    /**
     * set up tests
     */
    protected function setUp()
    {
        Zend_Session::$_unitTestEnabled = true;
        
        // get config
        $configData = @include('phpunitconfig.inc.php');
        if ($configData === false) {
            $configData = include('config.inc.php');
        }
        if ($configData === false) {
            die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
        }
        $this->_config = new Zend_Config($configData);
        
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }
    
    /**
     * tear down tests
     */
    protected function tearDown()
    {
        Zend_Session::$_unitTestEnabled = false;
        
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
    }
    
    /**
     * fetch test user credentials
     * 
     * @return array
     */
    public function getTestCredentials()
    {
        $config = $this->_config;
        $username = isset($config->login->username) ? $config->login->username : $config->username;
        $password = isset($config->login->password) ? $config->login->password : $config->password;
        
        return array(
            'username' => $username,
            'password' => $password
        );
    }
    
    /**
     * get account by name
     * 
     * @param  string  $name
     * @return Tinebase_Model_FullUser
     */
    public function getAccountByName($name)
    {
        return Tinebase_User::getInstance()->getFullUserByLoginName($name);
    }
    
    /**
     * 
     * @param  Tinebase_Model_User  $account
     * @param  string  $recordClass
     * @return Tinebase_Record_RecordSet
     */
    public function getPersonalContainer(Tinebase_Model_User $account, $recordClass)
    {
        return Tinebase_Container::getInstance()
            ->getPersonalContainer($account, $recordClass, $account, Tinebase_Model_Grants::GRANT_ADMIN);
    }
}
