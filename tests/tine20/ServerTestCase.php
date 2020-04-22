<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * abstract Test class for server tests
 * 
 * @package     Tests
 *
 * @todo we should extend a generic TestCase class to prevent code duplication
 *       NOTE: currently it's not possible to just extend TestCase (because of TestHelper include)
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
     * usernames to be deleted (in sync backend)
     *
     * @var array
     */
    protected $_usernamesToDelete = array();

    /**
     * @var Zend_Config
     */
    protected $_config;

    protected $_oldDenyList;
    protected $_oldSupportNTLMV2;
    protected $_oldEncryptionKey;
    
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

        $this->_oldDenyList = Tinebase_Config::getInstance()->get(Tinebase_Config::DENY_WEBDAV_CLIENT_LIST);
        $this->_oldSupportNTLMV2 = Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_SUPPORT_NTLMV2};
        $this->_oldEncryptionKey = Tinebase_Config::getInstance()->{Tinebase_Config::PASSWORD_NTLMV2_ENCRYPTION_KEY};
    }
    
    /**
     * tear down tests
     */
    protected function tearDown()
    {
        if (in_array(Tinebase_User::getConfiguredBackend(), array(Tinebase_User::LDAP, Tinebase_User::ACTIVEDIRECTORY))) {
            $this->_deleteUsers();
        }

        Zend_Session::$_unitTestEnabled = false;
        
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        }

        Tinebase_Config::getInstance()->set(Tinebase_Config::DENY_WEBDAV_CLIENT_LIST, $this->_oldDenyList);
        Tinebase_Config::getInstance()->set(Tinebase_Config::PASSWORD_SUPPORT_NTLMV2, $this->_oldSupportNTLMV2);
        Tinebase_Config::getInstance()->set(Tinebase_Config::PASSWORD_NTLMV2_ENCRYPTION_KEY, $this->_oldEncryptionKey);
    }
    
    /**
     * Performs custom preparations on the process isolation template.
     *
     * @param Text_Template $template
     * @since Method available since Release 3.4.0
     */
    protected function prepareTemplate(Text_Template $template)
    {
        // needed to get bootstrap file included in separate process again
        $template->setVar(array(
            'globals' => sprintf("\$GLOBALS['__PHPUNIT_BOOTSTRAP'] = '%s/bootstrap.php';", __DIR__)
        ));
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

    /**
     * test needs transaction
     */
    protected function _testNeedsTransaction()
    {
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->_transactionId = null;
        }
    }

    /**
     * delete users
     */
    protected function _deleteUsers()
    {
        foreach ($this->_usernamesToDelete as $username) {
            try {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Trying to delete user: ' . $username);

                Tinebase_User::getInstance()->deleteUser(Tinebase_User::getInstance()->getUserByLoginName($username));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Error while deleting user: ' . $e->getMessage());
            }
        }
    }
}
