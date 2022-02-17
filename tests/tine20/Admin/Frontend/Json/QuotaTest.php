<?php

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * 
 */

/**
 * Test class for Tinebase_Admin json frontend
 */
class Admin_Frontend_Json_QuotaTest extends Admin_Frontend_TestCase
{
    /**
     * Backend
     *
     * @var Admin_Frontend_Json
     */
    protected $_json;

    /**
     * @var array test $_emailAccounts
     */
    protected $_emailAccounts = array();

    protected $_originalRoleRights = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        parent::setUp();

        $this->_json = new Admin_Frontend_Json();
    }

    protected function tearDown(): void
    {
        foreach ($this->_emailAccounts as $account) {
            try {
                $this->_json->deleteEmailAccounts([is_array($account) ? $account['id'] : $account->getId()]);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // already removed
            }
        }

        $this->_resetOriginalRoleRights();

        parent::tearDown();
    }

    protected function _resetOriginalRoleRights()
    {
        if (!empty($this->_originalRoleRights)) {
            foreach ($this->_originalRoleRights as $roleId => $rights) {
                Tinebase_Acl_Roles::getInstance()->setRoleRights($roleId, $rights);
            }

            $this->_originalRoleRights = null;
        }
    }


    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testSaveQuotaTotalInMB()
    {
        // save total quota
        $app = 'Tinebase';
        $additionalData['totalInMB'] = 1234 * 1024 * 1024;

        Admin_Config::getInstance()->{Admin_Config::QUOTA_ALLOW_TOTALINMB_MANAGEMNET} = false;
        $result = $this->_json->saveQuota($app, null, $additionalData);
        
        $totalQuotaConfig = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA}->{Tinebase_Config::QUOTA_FILESYSTEM_TOTALINMB};
        static::assertEquals($result[Tinebase_Config::QUOTA_TOTALINMB], $totalQuotaConfig , true);
        static::assertEquals($totalQuotaConfig,  1234 , true);

        $additionalData['totalInMB'] = 5678 * 1024 * 1024;
        Admin_Config::getInstance()->{Admin_Config::QUOTA_ALLOW_TOTALINMB_MANAGEMNET} = true;
        $result = $this->_json->saveQuota($app, null, $additionalData);
        
        $totalQuotaConfig = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA}->{Tinebase_Config::QUOTA_TOTALINMB};
        static::assertEquals($result[Tinebase_Config::QUOTA_TOTALINMB], $totalQuotaConfig , true);
        static::assertEquals($totalQuotaConfig,  5678 , true);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testSaveQuotaFSPersonal()
    {
        // save filesystem personalFsQuota
        $application = 'Filemanager';
        $scleverPath = $this->_getPersonalPath();
        $node = Tinebase_FileSystem::getInstance()->stat($scleverPath);
        $node->quota = 1234;
        $additionalData['isPersonalNode'] = true;
        $additionalData['accountId'] = $node->name;

        $result = $this->_json->saveQuota($application, $node->toArray(), $additionalData);
        $user = Admin_Controller_User::getInstance()->get($additionalData['accountId']);

        static::assertEquals($user->xprops()[Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA], $node->quota ,true);

        try {
            $this->_json->saveQuota($application, null, $additionalData);
            self::fail('should throw Tinebase_Exception_UnexpectedValue');
        } catch (Tinebase_Exception_UnexpectedValue $e) {
            $translate = Tinebase_Translation::getTranslation('Admin');
            self::assertEquals($translate->_('Record data needs to be set!'), $e->getMessage());
        }

        try {
            $this->_originalRoleRights = $this->_removeRoleRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS, true);
            $this->_json->saveQuota($application, $node->toArray(), $additionalData);
            self::fail('should throw Tinebase_Exception_AccessDenied');
        } catch (Tinebase_Exception_AccessDenied $e) {
            self::assertEquals("You are not allowed to MANAGE_ACCOUNTS in application Admin !", $e->getMessage());
        }
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testSaveQuotaFSPersonalChildNode()
    {
        // save filesystem personal child folder Quota
        $application = 'Filemanager';
        $scleverPath = $this->_getPersonalPath();
        $subdir = $scleverPath . '/sub';
        $childNode = Tinebase_FileSystem::getInstance()->mkdir($subdir);
        $childNode->quota = 11234 * 1024 * 1024;

        $result = $this->_json->saveQuota($application, $childNode->toArray());

        static::assertEquals($childNode->quota, $result->quota , true);
    }
    
    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testSaveQuotaEmailPersonal()
    {
        $application = 'Felamimail';
        // save email personal quota
        $systemaccount = $this->_getTestUserFelamimailAccount();

        // must have data
        $quotaNodeData['name'] = $systemaccount['email'];
        $additionalData['isPersonalNode'] = true;
        $additionalData['emailMailQuota'] = 12345 * 1024 * 1024;
        $additionalData['emailSieveQuota'] = 45678 * 1024 * 1024;

        $result = $this->_json->saveQuota($application, $quotaNodeData, $additionalData);

        static::assertEquals($additionalData['emailMailQuota'], $result['email_imap_user']['emailMailQuota'], true);
        static::assertEquals($additionalData['emailSieveQuota'], $result['email_imap_user']['emailSieveQuota'], true);


        try {
            $account = $quotaNodeData;
            $account['name'] = '';
            $this->_json->saveQuota($application, $account, $additionalData);
            self::fail('should throw Tinebase_Exception_UnexpectedValue');
        } catch (Tinebase_Exception_UnexpectedValue $e) {
            $translate = Tinebase_Translation::getTranslation('Admin');
            self::assertEquals($translate->_("Account E-Mail needs to be set!"), $e->getMessage());
        }

        try {
            $account = $quotaNodeData;
            $account['name'] = 'testAccount';
            $this->_json->saveQuota($application, $account, $additionalData);
            self::fail('should throw Tinebase_Exception_UnexpectedValue');
        } catch (Tinebase_Exception_UnexpectedValue $e) {
            $translate = Tinebase_Translation::getTranslation('Admin');
            self::assertEquals($translate->_("E-Mail account not found."), $e->getMessage());
        }

        // save without account manage right
        try {
            $this->_originalRoleRights = $this->_removeRoleRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS, true);
            $this->_json->saveQuota($application, $quotaNodeData, $additionalData);
            self::fail('should throw Tinebase_Exception_AccessDenied');
        } catch (Tinebase_Exception_AccessDenied $e) {
            self::assertEquals("You are not allowed to MANAGE_ACCOUNTS in application Admin !", $e->getMessage());
        }
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testSaveQuotaEmailShared()
    {
        // save email shared folder quota
        $application = 'Felamimail';
        $path = '/' . Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId() . '/folders/shared';
        $childNode = Tinebase_FileSystem::getInstance()->mkdir($path);
        $childNode->quota = 11234 * 1024 * 1024;
        $result = $this->_json->saveQuota($application, $childNode);

        static::assertEquals($childNode->quota, $result->quota , true);

        // save quota without manage share email quota right
        try {
            $this->_originalRoleRights = $this->_removeRoleRight('Admin', Admin_Acl_Rights::MANAGE_SHARED_EMAIL_QUOTAS, true);
            $result = $this->_json->saveQuota($application, $childNode);
            self::fail('should throw Tinebase_Exception_AccessDenied');
        } catch (Tinebase_Exception_AccessDenied $e) {
            self::assertEquals("You do not have admin share rights for $application", $e->getMessage());
        }
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testSaveQuotaFSShared()
    {
        // save email shared folder quota
        $application = 'Filemanager';
        $path = '/' . Tinebase_Application::getInstance()->getApplicationByName('Filemanager')->getId() . '/folders/shared';
        $childNode = Tinebase_FileSystem::getInstance()->mkdir($path);
        $childNode->quota = 11234 * 1024 * 1024;
        $result = $this->_json->saveQuota($application, $childNode);

        static::assertEquals($childNode->quota, $result->quota , true);

        // save quota without manage share email quota right
        try {
            $this->_originalRoleRights = $this->_removeRoleRight('Admin', Admin_Acl_Rights::MANAGE_SHARED_FILESYSTEM_QUOTAS, true);
            $result = $this->_json->saveQuota($application, $childNode);
            self::fail('should throw Tinebase_Exception_AccessDenied');
        } catch (Tinebase_Exception_AccessDenied $e) {
            self::assertEquals("You do not have admin share rights for $application", $e->getMessage());
        }
    }
}
