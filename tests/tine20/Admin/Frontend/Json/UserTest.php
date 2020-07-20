<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Admin json frontend (User API)
 */
class Admin_Frontend_Json_UserTest extends Admin_Frontend_TestCase
{
    /**
     * try to save an account
     *
     * @return array
     *
     * @group nogitlabci_ldap
     */
    public function testSaveAccount()
    {
        $accountData = $this->_getUserArrayWithPw();
        $account = $this->_json->saveUser($accountData);

        $this->assertTrue(is_array($account));
        $this->assertEquals('PHPUnitup', $account['accountFirstName'], print_r($account, true));
        $this->assertEquals(Tinebase_Group::getInstance()->getGroupByName('tine20phpunitgroup')->getId(), $account['accountPrimaryGroup']['id']);
        $this->assertTrue(! empty($account['accountId']), 'no account id');
        // check password
        $authResult = Tinebase_Auth::getInstance()->authenticate($account['accountLoginName'], $accountData['accountPassword']);
        $this->assertTrue($authResult->isValid());
        $this->assertTrue(isset($account['xprops'][Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA])
            && $account['xprops'][Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA] === 100,
            'failed to set/get account filesystem personal quota');
        $this->assertTrue(isset($account['effectiveAndLocalQuota']) &&
            100 === $account['effectiveAndLocalQuota']['localQuota']);

        $account['xprops'][Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA] = 200;
        $account['accountPrimaryGroup'] = $account['accountPrimaryGroup']['id'];
        $account['groups'] = array($account['groups']['results'][0]['id']);
        $updatedAccount = $this->_json->saveUser($account);

        $this->assertTrue(isset($updatedAccount['xprops'][Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA])
            && $updatedAccount['xprops'][Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA] === 200,
            'failed to set/get account filesystem personal quota');
        $this->assertTrue(isset($updatedAccount['effectiveAndLocalQuota']) &&
            200 === $updatedAccount['effectiveAndLocalQuota']['localQuota']);

        $account['accountPrimaryGroup'] = $accountData['accountPrimaryGroup'];
        return $account;
    }

    protected function _getUserArrayWithPw($pwdMustChange = false)
    {
        $group = $this->_createGroup();
        $accountData = $this->_getUserData();
        $accountData['accountPrimaryGroup'] = $group['id'];
        $accountData['accountFirstName'] = 'PHPUnitup';
        $accountData['accountLastName'] = 'PHPUnitlast';
        $accountData['xprops'][Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA] = 100;
        $accountData['accountPassword'] = Tinebase_Record_Abstract::generateUID(10);
        $accountData['password_must_change'] = $pwdMustChange ? 1 : 0;
        return $accountData;
    }

    public function testPwdMustChange()
    {
        $this->_skipIfLDAPBackend();

        $accountData = $this->_getUserArrayWithPw(true);
        $account = $this->_json->saveUser($accountData);
        $account =$this->_json->getUser($account['accountId']);
        self::assertTrue(isset($account['password_must_change']), 'property not set in account');
        self::assertEquals(1, $account['password_must_change']);
        $credentials = TestServer::getInstance()->getTestCredentials();
        $this->_json->resetPassword($account, $credentials['password'], 0);
        $account = $this->_json->getUser($account['accountId']);
        self::assertEquals(0, $account['password_must_change']);
        $account['password_must_change'] = 1;
        $account['accountPassword'] = $credentials['password'];
        $account['groups'] = array(Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(), $account['groups']['results'][0]['id']);
        $this->_json->saveUser($account);
        $updatedAccount =$this->_json->getUser($account['accountId']);
        self::assertEquals(1, $updatedAccount['password_must_change']);
    }

    /**
     * try to get all accounts
     *
     * @group nogitlabci_ldap
     */
    public function testGetAccounts()
    {
        $account = $this->_createTestUser();

        $accounts = $this->_json->getUsers($account->accountLoginName, 'accountDisplayName', 'ASC', 0, 10);

        $this->assertGreaterThan(0, $accounts['totalcount']);
    }

    /**
     * testGetUserCount
     *
     * @see 0006544: fix paging in admin/users grid
     *
     * @group nogitlabci_ldap
     */
    public function testGetUserCount()
    {
        $this->testSetAccountState();
        $accounts = $this->_json->getUsers('phpunitadminjson', 'accountDisplayName', 'ASC', 0, 100);
        $this->assertEquals(count($accounts['results']), $accounts['totalcount'], print_r($accounts['results'], TRUE));
    }

    /**
     * get account that doesn't exist (by id)
     */
    public function testGetNonExistentAccountById()
    {
        Tinebase_Translation::getTranslation('Tinebase');
        $id = 12334567;

        $this->setExpectedException('Tinebase_Exception_NotFound');
        Tinebase_User::getInstance()->getUserById($id);
    }

    /**
     * get account that doesn't exist (by login name)
     */
    public function testGetNonExistentAccountByLoginName()
    {
        $loginName = 'something';

        $this->setExpectedException('Tinebase_Exception_NotFound');
        Tinebase_User::getInstance()->getUserByLoginName($loginName);
    }

    /**
     * try to create an account with existing login name
     *
     * @see 0006770: check if username already exists when creating new user / changing username
     *
     * @group nogitlabci_ldap
     */
    public function testSaveAccountWithExistingName()
    {
        $accountData = $this->_createTestUser()->toArray();
        unset($accountData['accountId']);

        try {
            $account = $this->_json->saveUser($accountData);
            $this->fail('Creating an account with existing login name should throw exception: ' . print_r($account, TRUE));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            $this->assertEquals('Login name already exists. Please choose another one.', $tesg->getMessage());
        }

        $accountData = $this->_getUserArrayWithPw();
        $accountData['accountLoginName'] = Tinebase_Core::getUser()->accountLoginName;

        try {
            $account = $this->_json->saveUser($accountData);
            $this->fail('Updating an account with existing login name should throw exception: ' . print_r($account, TRUE));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            $this->assertEquals('Login name already exists. Please choose another one.', $tesg->getMessage());
        }
    }

    /**
     * try to save a hidden account
     */
    public function testSaveHiddenAccount()
    {
        $accountData = $this->_getUserArrayWithPw();
        $accountData['visibility'] = Tinebase_Model_User::VISIBILITY_HIDDEN;
        $accountData['container_id'] = 0;

        $account = $this->_json->saveUser($accountData);

        $this->assertTrue(is_array($account));
        $this->assertTrue(! empty($account['contact_id']));
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        $this->assertEquals($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK], $account['container_id']['id']);
    }

    /**
     * testUpdateUserWithoutContainerACL
     *
     * @see 0006254: edit/create user is not possible
     *
     * @group nogitlabci_ldap
     */
    public function testUpdateUserWithoutContainerACL()
    {
        $account = $this->_createTestUser();
        $internalContainer = $this->_removeGrantsOfInternalContainer($account);
        $account = $this->_json->getUser($account['accountId']);

        self::assertTrue(isset($account['groups']['results']), 'account got no groups: ' . print_r($account, true));
        $account['groups'] = array(Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(), $account['groups']['results'][0]['id']);
        $account['container_id'] = $internalContainer->getId();
        $account['accountPrimaryGroup'] = $account['accountPrimaryGroup']['id'];
        $account = $this->_json->saveUser($account);

        self::assertTrue(isset($account['groups']['results']), 'account got no groups: ' . print_r($account, true));
        self::assertEquals(2, $account['groups']['totalcount']);
    }

    /**
     * @param Tinebase_Model_FullUser $account
     * @group nogitlabci_ldap
     */
    protected function _removeGrantsOfInternalContainer($account)
    {
        /** @var Tinebase_Model_Container $internalContainer */
        $internalContainer = Tinebase_Container::getInstance()->get($account->container_id);
        $this->_originalGrants[$internalContainer->getId()] = Tinebase_Container::getInstance()->getGrantsOfContainer(
            $internalContainer, true);
        Tinebase_Container::getInstance()->setGrants($internalContainer, new Tinebase_Record_RecordSet(
            $internalContainer->getGrantClass()), true, false);

        return $internalContainer;
    }

    /**
     * testUpdateUserRemoveGroup
     *
     * @see 0006762: user still in admin role when admin group is removed
     *
     * @group nogitlabci_ldap
     */
    public function testUpdateUserRemoveGroup()
    {
        $account = $this->_createTestUser();
        $this->_removeGrantsOfInternalContainer($account);

        $adminGroupId = Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
        $account->groups = array($account->accountPrimaryGroup, $adminGroupId);
        $account = $this->_json->saveUser($account->toArray());

        $roles = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($account['accountId']);
        $adminRole = Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role');
        $this->assertTrue(in_array($adminRole->getId(), $roles));

        $account['accountPrimaryGroup'] = $account['accountPrimaryGroup']['id'];
        $account['groups'] = array($account['accountPrimaryGroup']);

        if (is_array($account['container_id']) && is_array($account['container_id']['id'])) {
            $account['container_id'] = $account['container_id']['id'];
        }

        $account = $this->_json->saveUser($account);

        $roles = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($account['accountId']);
        $this->assertEquals(1, count($roles));
        $this->assertTrue(isset($account['last_modified_by']), 'modlog fields missing from account: ' . print_r($account, true));
        $this->assertEquals(Tinebase_Core::getUser()->accountId, $account['last_modified_by']['accountId'], print_r($account, true));
    }

    /**
     * testUpdateUserRemovedPrimaryGroup
     *
     * @see 0006710: save user fails if primary group no longer exists
     */
    public function testUpdateUserRemovedPrimaryGroup()
    {
        $this->_createGroup();

        $accountData = $this->_getUserArrayWithPw();
        $accountData['accountPrimaryGroup'] = Tinebase_Group::getInstance()->getGroupByName('tine20phpunitgroup')->getId();

        Admin_Controller_Group::getInstance()->delete(array($accountData['accountPrimaryGroup']));

        $savedAccount = $this->_json->saveUser($accountData);

        $this->assertEquals(Tinebase_Group::getInstance()->getDefaultGroup()->getId(), $savedAccount['accountPrimaryGroup']['id']);
    }

    /**
     * try to delete accounts
     */
    public function testDeleteAccounts()
    {
        $userArray = $this->_createTestUser();
        Admin_Controller_User::getInstance()->delete($userArray['accountId']);

        $this->setExpectedException('Tinebase_Exception_NotFound');
        Tinebase_User::getInstance()->getUserById($userArray['accountId']);
    }

    /**
     * try to set account state
     *
     * @group nogitlabci_ldap
     */
    public function testSetAccountState()
    {
        $userArray = $this->_createTestUser();

        $this->_json->setAccountState(array($userArray['accountId']), 'disabled');

        $account = Tinebase_User::getInstance()->getFullUserById($userArray['accountId']);

        $this->assertEquals('disabled', $account->accountStatus);
    }

    /**
     * test send deactivation notification
     *
     * @see 0009956: send mail on account deactivation
     *
     * @group nogitlabci_ldap
     */
    public function testAccountDeactivationNotification()
    {
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
        if (! isset($smtpConfig->from) && ! isset($smtpConfig->primarydomain)) {
            $this->markTestSkipped('no notification service address configured.');
        }

        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_DEACTIVATION_NOTIFICATION, true);

        $userArray = $this->_createTestUser();

        self::flushMailer();

        $this->_json->setAccountState(array($userArray['accountId']), 'disabled');

        $messages = self::getMessages();

        $this->assertEquals(1, count($messages), 'did not get notification message');

        $message = $messages[0];
        $bodyText = $message->getBodyText(/* textOnly = */ true);

        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $this->assertEquals($translate->_('Your Tine 2.0 account has been deactivated'), $message->getSubject());
        // @todo make this work. currently it does not work in de translation as the user name is cropped (tine20phpuni=)
        //$this->assertContains($userArray['accountLoginName'], $bodyText);
        $this->assertContains(Tinebase_Core::getHostname(), $bodyText);
    }

    /**
     * try to reset password
     *
     * @group nogitlabci_ldap
     */
    public function testResetPassword()
    {
        $userArray = $this->_createTestUser();

        $pw = 'dpIg6komP';
        $this->_json->resetPassword($userArray, $pw, false);

        $authResult = Tinebase_Auth::getInstance()->authenticate($userArray['accountLoginName'], $pw);
        $this->assertTrue($authResult->isValid());
    }

    /**
     * try to reset pin
     *
     * @see 0013320: allow admin to reset pin for accounts
     *
     * @group nogitlabci_ldap
     */
    public function testResetPin()
    {
        $userArray = $this->_createTestUser();

        $pw = '1234';
        $this->_json->resetPin($userArray, $pw);

        $pinAuth = Tinebase_Auth_Factory::factory(Tinebase_Auth::PIN);
        $pinAuth->setIdentity($userArray['accountLoginName']);
        $pinAuth->setCredential($pw);
        $result = $pinAuth->authenticate();
        $this->assertEquals(Tinebase_Auth::SUCCESS, $result->getCode());
    }

    /**
     * testAccountContactModlog
     *
     * @see 0006688: contact of new user should have modlog information
     */
    public function testAccountContactModlog()
    {
        $user = $this->_createTestUser();

        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($user['accountId']);

        $this->assertTrue(! empty($contact->creation_time));
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contact->created_by);
    }

    /**
     * testChangeContactEmailCheckPrimaryDomain
     *
     * @group nogitlabci_ldap
     */
    public function testChangeContactEmailCheckPrimaryDomain()
    {
        $primaryDomain = $this->_getPrimaryDomain();
        if ($primaryDomain === '') {
            self::markTestSkipped('test does not work without primary domain cfg');
        }

        $user = $this->_createTestUser();
        $contact = Addressbook_Controller_Contact::getInstance()->get($user['contact_id']);
        $contact->email = 'somemail@anotherdomain.com';
        try {
            Addressbook_Controller_Contact::getInstance()->update($contact);
            self::fail('update should throw an exception - email should not be updateable: ' . print_r($contact->toArray(), true));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
        }
    }

    protected function _getPrimaryDomain()
    {
        $smtpConfig = Tinebase_EmailUser::getConfig(Tinebase_Config::SMTP);
        return Tinebase_EmailUser::manages(Tinebase_Config::SMTP) && isset($smtpConfig['primarydomain'])
            ? $smtpConfig['primarydomain'] : '';
    }

    public function testAdditionalDomainInUserAccount()
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        $addDomain = 'anotherdomain.com';
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
        $smtpConfig->additionaldomains = $addDomain;
        Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $smtpConfig);

        $user = $this->_createTestUser()->toArray();
        $user['accountEmailAddress'] = 'somemail@' . $addDomain;
        $updatedUser = $this->_json->saveUser($user);
        self::assertEquals($user['accountEmailAddress'], $updatedUser['accountEmailAddress']);

        // TODO email user should be removed afterwards
    }

    /**
     * test set expired status
     *
     * @group nogitlabci_ldap
     */
    public function testSetUserExpiredStatus()
    {
        $userArray = $this->_createTestUser();
        $result = Admin_Controller_User::getInstance()->setAccountStatus($userArray['accountId'], Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED);
        $this->assertEquals(1, $result);
    }
}
