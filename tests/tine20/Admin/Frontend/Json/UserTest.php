<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2022 Metaways Infosystems GmbH (http://www.metaways.de)
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
     */
    public function testSaveAccount()
    {
        $accountData = $this->_getUserArrayWithPw();
        $account = $this->_json->saveUser($accountData);

        $this->assertTrue(is_array($account));
        $this->assertEquals('PHPUnitup', $account['accountFirstName'], print_r($account, true));
        $this->assertEquals(Tinebase_Group::getInstance()->getGroupByName('tine20phpunitgroup')->getId(), $account['accountPrimaryGroup']['id']);
        $this->assertTrue(! empty($account['accountId']), 'no account id');

        // FIXME make auth check work for ldap backends!
        if (Tinebase_User::getConfiguredBackend() !== Tinebase_User::LDAP &&
            Tinebase_User::getConfiguredBackend() !== Tinebase_User::ACTIVEDIRECTORY
        ) {
            // check password
            $authResult = Tinebase_Auth::getInstance()->authenticate($account['accountLoginName'], $accountData['accountPassword']);
            $this->assertTrue($authResult->isValid(), 'auth fail: ' . print_r($authResult->getMessages(), true));
        }

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

    public function _getUserArrayWithPw($pwdMustChange = false)
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

        $this->expectException('Tinebase_Exception_NotFound');
        Tinebase_User::getInstance()->getUserById($id);
    }

    /**
     * get account that doesn't exist (by login name)
     */
    public function testGetNonExistentAccountByLoginName()
    {
        $loginName = 'something';

        $this->expectException('Tinebase_Exception_NotFound');
        Tinebase_User::getInstance()->getUserByLoginName($loginName);
    }

    /**
     * try to create an account with existing login name
     *
     * @see 0006770: check if username already exists when creating new user / changing username
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
            self::fail('Updating an account with existing login name should throw exception: ' . print_r($account, TRUE));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            self::assertEquals('Login name already exists. Please choose another one.', $tesg->getMessage());
        }
    }

    public function testSaveAccountWithAndWithoutEmail()
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        $accountData = $this->_getUserArrayWithPw();
        $emailUser = array (
            'emailMailQuota' => 0,
            'emailMailSize' => 0,
            'emailSieveQuota' => 0,
            'emailSieveSize' => 0,
            'emailLastLogin' => '',
            'emailForwardOnly' => false,
            'emailAddress' => '',
            'emailUsername' => '',
            'emailAliases' => [],
            'emailForwards' => []
        );
        $accountData['emailUser'] = $emailUser;
        $accountData['accountEmailAddress'] = null;
        $account = $this->_json->saveUser($accountData);
        // assert no email account has been created
        self::assertFalse(isset($account['xprops'][Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP]), 'imap user found!');
        self::assertFalse(isset($account['xprops'][Tinebase_Model_FullUser::XPROP_EMAIL_USERID_SMTP]), 'smtp user found!');
        self::assertEmpty($account['accountEmailAddress']);

        if (Tinebase_Application::getInstance()->isInstalled('Felamimail')) {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                ['field' => 'user_id', 'operator' => 'equals', 'value' => $account['accountId']]
            ]);
            $emailAccounts = Admin_Controller_EmailAccount::getInstance()->search($filter);
            self::assertCount(0, $emailAccounts, 'empty mail account created: ' . print_r($emailAccounts->toArray(), true));

            // add email address -> accounts should be created
            $account['accountEmailAddress'] = $account['accountLoginName'] . '@' . TestServer::getPrimaryMailDomain();
            $account['accountPassword'] = Tinebase_Record_Abstract::generateUID('20');
            $account = $this->_json->saveUser($account);
            self::assertTrue(isset($account['xprops'][Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP]), 'imap user not found!');
            self::assertTrue(isset($account['xprops'][Tinebase_Model_FullUser::XPROP_EMAIL_USERID_SMTP]), 'smtp user not found!');
            $emailAccounts = Admin_Controller_EmailAccount::getInstance()->search($filter);
            self::assertCount(1, $emailAccounts);
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
     */
    public function testUpdateUserWithoutContainerACL()
    {
        $account = $this->_createTestUser();
        $internalContainer = $this->_removeGrantsOfInternalContainer($account);
        $account = $this->_json->getUser($account->getId());

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
     * @return Tinebase_Model_Container
     */
    protected function _removeGrantsOfInternalContainer($account)
    {
        /** @var Tinebase_Model_Container $internalContainer */
        $internalContainer = Tinebase_Container::getInstance()->getContainerByName(
            Addressbook_Model_Contact::class, 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
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

        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_Group::getInstance()->delete(array($accountData['accountPrimaryGroup']));

        $savedAccount = $this->_json->saveUser($accountData);

        $this->assertEquals(Tinebase_Group::getInstance()->getDefaultGroup()->getId(), $savedAccount['accountPrimaryGroup']['id']);
    }
    
    /**
     * try to delete accounts with confirmation
     */
    public function testDeleteAccountsWithConfirmation()
    {
        try {
            Admin_Controller_User::getInstance()->setRequestContext([]);
            $userArray = $this->_createTestUser();
            Admin_Controller_User::getInstance()->delete($userArray['accountId']);
            self::fail('should throw Tinebase_Exception_Confirmation');
        } catch (Tinebase_Exception_Confirmation $e) {
            $translate = Tinebase_Translation::getTranslation('Admin');
            $translation = $translate->_('Delete user will trigger the [V] events, do you still want to execute this action?');

            self::assertEquals($translation, $e->getMessage());
        }

        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete($userArray['accountId']);

        if (Tinebase_User::getInstance()->isHardDeleteEnabled()) {
            $this->expectException('Tinebase_Exception_NotFound');
        }

        $account = Tinebase_User::getInstance()->getUserById($userArray['accountId']);
        if (! Tinebase_User::getInstance()->isHardDeleteEnabled()) {
            $this->assertTrue((bool)$account->is_deleted);
        }
    }

    /**
     * try to delete accounts
     */
    public function testDeleteAccounts()
    {
        $userArray = $this->_createTestUser();

        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete($userArray['accountId']);

        if (Tinebase_User::getInstance()->isHardDeleteEnabled()) {
            $this->expectException('Tinebase_Exception_NotFound');
        }

        $account = Tinebase_User::getInstance()->getUserById($userArray['accountId']);
        if (! Tinebase_User::getInstance()->isHardDeleteEnabled()) {
            $this->assertTrue((bool)$account->is_deleted);
        }
    }
    
    /**
     * try to set account state
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
        //$this->assertStringContainsString($userArray['accountLoginName'], $bodyText);
        $this->assertStringContainsString(Tinebase_Core::getHostname(), $bodyText);
    }

    /**
     * try to reset password
     */
    public function testResetPassword()
    {
        $this->_skipIfLDAPBackend();

        $userArray = $this->_createTestUser();

        $pw = 'dpIg6komP';
        $this->_json->resetPassword($userArray, $pw, false);

        $authResult = Tinebase_Auth::getInstance()->authenticate($userArray['accountLoginName'], $pw);
        $this->assertTrue($authResult->isValid(), 'auth fail: ' . print_r($authResult->getMessages(), true));
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

    /**
     * @param string $domain
     * @param string $localPart
     * @throws Admin_Exception
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testAdditionalDomainInUserAccount($domain = 'anotherdomain.com', $localPart = 'somemail')
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);

        $smtpConfig->additionaldomains = Tinebase_Helper::convertDomainToPunycode($domain);
        Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $smtpConfig);

        $user = $this->_createTestUser();
        $userArray = $user->toArray();
        $userArray['accountEmailAddress'] = $localPart . '@' . $domain;
        $updatedUser = $this->_json->saveUser($userArray);
        self::assertEquals($userArray['accountEmailAddress'], $updatedUser['accountEmailAddress']);

        // check if smtp address is empty - it should be because email address is not in primary/secondary domains!
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        $xpropsUser = clone($user);
        Tinebase_EmailUser_XpropsFacade::setIdFromXprops($user, $xpropsUser);
        $userInSmtpBackend = $emailUserBackend->getRawUserById($xpropsUser);
        self::assertFalse($userInSmtpBackend);
    }

    public function testUmlautsInDomainAndEmailAddress()
    {
        $umlautDomain = 'myümläutdomain.de';
        $this->testAdditionalDomainInUserAccount($umlautDomain, 'müller');

        // check if umlaut domain is in registry
        Tinebase_EmailUser::clearCaches();
        $tbJson = new Tinebase_Frontend_Json();
        $registry = $tbJson->getRegistryData();
        self::assertEquals($umlautDomain, $registry['additionaldomains']);
    }

    public function testUmlautDomainInAliases()
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        $umlautDomain = 'myümläutdomain.de';
        $aliasAddress = 'alias@' . $umlautDomain;
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
        $secondaryDomains = $smtpConfig->secondarydomains . ',' . Tinebase_Helper::convertDomainToPunycode($umlautDomain);
        $smtpConfig->secondarydomains = $secondaryDomains;
        Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $smtpConfig);
        // need to destroy user singleton because it still has the email plugin with the old config...
        Admin_Controller_User::destroyInstance();
        Tinebase_User::destroyInstance();
        Tinebase_EmailUser::destroyInstance();

        $user = $this->_createTestUser();
        $userArray = $user->toArray();
        $userArray['emailUser']['emailAliases'] = [
            ['email' => $aliasAddress, 'dispatch_address' => true],
        ];
        $updatedUser = $this->_json->saveUser($userArray);
        self::assertIsArray($updatedUser['emailUser']['emailAliases'],
            'aliases not saved: ' . print_r($updatedUser['emailUser'], true));
        self::assertCount(1, $updatedUser['emailUser']['emailAliases'],
            'aliases not saved: ' . print_r($updatedUser['emailUser'], true));
        self::assertequals($aliasAddress, $updatedUser['emailUser']['emailAliases'][0]['email'],
            'aliases not correct: ' . print_r($updatedUser['emailUser'], true));
    }

    public function testSetMFA()
    {
        $this->_createAreaLockConfig();

        $user = $this->_personas['sclever']->toArray();
        $user['mfa_configs'] = [[
            'id' => Tinebase_Record_Abstract::generateUID(),
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'pin',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_PinUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG => [
                Tinebase_Model_MFA_PinUserConfig::FLD_PIN => '123456',
            ],
        ]];

        $savedUser = $this->_json->saveUser($user);

        $this->assertCount(1, $savedUser['mfa_configs']);
        $this->assertEmpty($savedUser['mfa_configs'][0][Tinebase_Model_MFA_UserConfig::FLD_CONFIG]
        [Tinebase_Model_MFA_PinUserConfig::FLD_PIN]);

        $sclever = Tinebase_User::getInstance()->getFullUserById($savedUser['accountId']);
        $this->assertNotEmpty($sclever->mfa_configs->getFirstRecord()->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->getHashedPin());
    }
    
    /**
     * test set expired status
     */
    public function testSetUserExpiredStatus()
    {
        $userArray = $this->_createTestUser();
        $result = Admin_Controller_User::getInstance()->setAccountStatus($userArray['accountId'], Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED);
        $this->assertEquals(1, $result);
    }
}
