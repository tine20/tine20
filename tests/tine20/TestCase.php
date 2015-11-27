<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Abstract test class
 * 
 * @package     Tests
 */
abstract class TestCase extends PHPUnit_Framework_TestCase
{
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
     * groups (ID) to be deleted (in sync backend)
     * 
     * @var array
     */
    protected $_groupIdsToDelete = array();
    
    /**
     * remove group members, too when deleting groups
     * 
     * @var boolean
     */
    protected $_removeGroupMembers = true;
    
    /**
     * invalidate roles cache
     * 
     * @var boolean
     */
    protected $_invalidateRolesCache = false;
    
    /**
     * test personas
     * 
     * @var array
     */
    protected $_personas = array();
    
    /**
     * unit in test
     *
     * @var Object
     */
    protected $_uit = null;
    
    /**
     * the test user
     *
     * @var Tinebase_Model_FullUser
     */
    protected $_originalTestUser;
    
    /**
     * the mailer
     * 
     * @var Zend_Mail_Transport_Abstract
     */
    protected static $_mailer = null;

    /**
     * db lock ids to be released
     *
     * @var array
     */
    protected $_releaseDBLockIds = array();

    /**
     * set up tests
     */
    protected function setUp()
    {
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(false);
        
        $this->_personas = Zend_Registry::get('personas');
        
        $this->_originalTestUser = Tinebase_Core::getUser();
    }
    
    /**
     * tear down tests
     */
    protected function tearDown()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
            $this->_deleteUsers();
            $this->_deleteGroups();
        }
        if ($this->_transactionId) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Rolling back test transaction');
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);

        if ($this->_originalTestUser instanceof Tinebase_Model_User) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        }
        
        if ($this->_invalidateRolesCache) {
            Tinebase_Acl_Roles::getInstance()->resetClassCache();
        }
        Tinebase_Cache_PerRequest::getInstance()->resetCache();

        $this->_releaseDBLocks();
    }

    /**
     * release db locks
     */
    protected function _releaseDBLocks()
    {
        foreach ($this->_releaseDBLockIds as $lockId) {
            Tinebase_Lock::releaseDBSessionLock($lockId);
        }

        $this->_releaseDBLockIds = array();
    }

    /**
     * tear down after test class
     */
    public static function tearDownAfterClass()
    {
        Tinebase_Core::getDbProfiling();
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
     * get tag
     *
     * @param string $tagType
     * @param string $tagName
     * @param array $contexts
     * @return Tinebase_Model_Tag
     */
    protected function _getTag($tagType = Tinebase_Model_Tag::TYPE_SHARED, $tagName = NULL, $contexts = NULL)
    {
        if ($tagName) {
            try {
                $tag = Tinebase_Tags::getInstance()->getTagByName($tagName);
                return $tag;
            } catch (Tinebase_Exception_NotFound $tenf) {
            }
        } else {
            $tagName = Tinebase_Record_Abstract::generateUID();
        }
    
        $targ = array(
            'type'          => $tagType,
            'name'          => $tagName,
            'description'   => 'testTagDescription',
            'color'         => '#009B31',
        );
    
        if ($contexts) {
            $targ['contexts'] = $contexts;
        }
    
        return new Tinebase_Model_Tag($targ);
    }
    
    /**
     * delete groups and their members
     * 
     * - also deletes groups and users in sync backends
     */
    protected function _deleteGroups()
    {
        foreach ($this->_groupIdsToDelete as $groupId) {
            if ($this->_removeGroupMembers) {
                foreach (Tinebase_Group::getInstance()->getGroupMembers($groupId) as $userId) {
                    try {
                        Tinebase_User::getInstance()->deleteUser($userId);
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' error while deleting user: ' . $e->getMessage());
                    }
                }
            }
            try {
                Tinebase_Group::getInstance()->deleteGroups($groupId);
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' error while deleting group: ' . $e->getMessage());
            }
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

    /**
     * get personal container
     * 
     * @param string $applicationName
     * @param Tinebase_Model_User $user
     * @return Tinebase_Model_Container
     */
    protected function _getPersonalContainer($applicationName, $user = null)
    {
        if ($user === null) {
            $user = Tinebase_Core::getUser();
        }
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            $user,
            $applicationName, 
            $user,
            Tinebase_Model_Grants::GRANT_EDIT
        )->getFirstRecord();

        if (! $personalContainer) {
            throw new Tinebase_Exception_UnexpectedValue('no personal container found!');
        }

        return $personalContainer;
    }
    
    /**
     * get test container
     * 
     * @param string $applicationName
     * @param string $model
     */
    protected function _getTestContainer($applicationName, $model = null)
    {
        return Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit ' . $model .' container',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'Sql',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName($applicationName)->getId(),
            'model'          => $model,
        ), true));
    }
    
    /**
     * get test mail domain
     * 
     * @return string
     */
    protected function _getMailDomain()
    {
        $testconfig = Zend_Registry::get('testConfig');
        
        if ($testconfig && isset($testconfig->maildomain)) {
            return $testconfig->maildomain;
        }
        
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            list($user, $domain) = explode('@', Tinebase_Core::getUser()->accountEmailAddress, 2);
            
            return $domain;
        }
        
        return 'tine20.org';
    }
    
    /**
     * get test user email address
     * 
     * @return test user email address
     */
    protected function _getEmailAddress()
    {
        $testConfig = Zend_Registry::get('testConfig');
        return ($testConfig->email) ? $testConfig->email : Tinebase_Core::getUser()->accountEmailAddress;
    }
    
    /**
     * lazy init of uit
     * 
     * @return Object
     * 
     * @todo fix ide object class detection for completions
     */
    protected function _getUit()
    {
        if ($this->_uit === null) {
            $uitClass = preg_replace('/Tests{0,1}$/', '', get_class($this));
            if (@method_exists($uitClass, 'getInstance')) {
                $this->_uit = call_user_func($uitClass . '::getInstance');
            } else if (@class_exists($uitClass)) {
                $this->_uit = new $uitClass();
            } else {
                throw new Exception('could not find class ' . $uitClass);
            }
        }
        
        return $this->_uit;
    }
    
    /**
     * get messages
     * 
     * @return array
     */
    public static function getMessages()
    {
        // make sure messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(100);
        }
        
        return self::getMailer()->getMessages();
    }
    
    /**
     * get mailer
     * 
     * @return Zend_Mail_Transport_Abstract
     */
    public static function getMailer()
    {
        if (! self::$_mailer) {
            self::$_mailer = Tinebase_Smtp::getDefaultTransport();
        }
        
        return self::$_mailer;
    }
    
    /**
     * flush mailer (send all remaining mails first)
     */
    public static function flushMailer()
    {
        // make sure all messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        self::getMailer()->flush();
    }
    
    /**
     * returns the content.xml of an ods document
     * 
     * @param string $filename
     * @return SimpleXMLElement
     */
    protected function _getContentXML($filename)
    {
        $zipHandler = zip_open($filename);
        
        do {
            $entry = zip_read($zipHandler);
        } while ($entry && zip_entry_name($entry) != "content.xml");
        
        // open entry
        zip_entry_open($zipHandler, $entry, "r");
        
        // read entry
        $entryContent = zip_entry_read($entry, zip_entry_filesize($entry));
        
        $xml = simplexml_load_string($entryContent);
        zip_close($zipHandler);
        
        return $xml;
    }
    
    /**
     * get test temp file
     * 
     * @return Tinebase_TempFile
     */
    protected function _getTempFile()
    {
        $tempFileBackend = new Tinebase_TempFile();
        $tempFile = $tempFileBackend->createTempFile(dirname(__FILE__) . '/Filemanager/files/test.txt');
        return $tempFile;
    }
    
    /**
     * remove right in all users roles
     * 
     * @param string $applicationName
     * @param string $right
     * @param boolean $removeAdminRight
     * @return array original role rights by role id
     */
    protected function _removeRoleRight($applicationName, $rightToRemove, $removeAdminRight = true)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($applicationName);
        $rolesOfUser = Tinebase_Acl_Roles::getInstance()->getRoleMemberships(Tinebase_Core::getUser()->getId());
        $this->_invalidateRolesCache = true;

        $roleRights = array();
        foreach ($rolesOfUser as $roleId) {
            $roleRights[$roleId] = $rights = Tinebase_Acl_Roles::getInstance()->getRoleRights($roleId);
            foreach ($rights as $idx => $right) {
                if ($right['application_id'] === $app->getId() && ($right['right'] === $rightToRemove || $right['right'] === Tinebase_Acl_Rights_Abstract::ADMIN)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Removing right ' . $right['right'] . ' from app ' . $applicationName . ' in role (id) ' . $roleId);
                    unset($rights[$idx]);
                }
            }
            Tinebase_Acl_Roles::getInstance()->setRoleRights($roleId, $rights);
        }
        
        return $roleRights;
    }
    
    /**
     * set grants for a persona and the current user
     * 
     * @param integer $containerId
     * @param string $persona
     * @param string $adminGrant
     */
    protected function _setPersonaGrantsForTestContainer($containerId, $persona, $personaAdminGrant = false, $userAdminGrant = true)
    {
        $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(array(
            'account_id'    => $this->_personas[$persona]->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => $personaAdminGrant,
        ), array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => $userAdminGrant,
        )));
        
        Tinebase_Container::getInstance()->setGrants($containerId, $grants, TRUE);
    }

    /**
     * set current user
     *
     * @param $user
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setUser($user)
    {
        Tinebase_Core::set(Tinebase_Core::USER, $user);
    }
}
