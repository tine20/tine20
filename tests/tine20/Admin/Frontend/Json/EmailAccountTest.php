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
 * Test class for Tinebase_Admin json frontend
 */
class Admin_Frontend_Json_EmailAccountTest extends TestCase
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

    protected $_scleverPwChanged = false;

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

        // re-set sclevers pw if changed
        if ($this->_scleverPwChanged) {
            $sclever = $this->_personas['sclever'];
            $creds = TestServer::getInstance()->getTestCredentials();
            Admin_Controller_User::getInstance()->setAccountPassword($sclever, $creds['password'], $creds['password']);
        }

        parent::tearDown();

        // remove instance to prevent acl pollution
        Admin_Controller_EmailAccount::destroyInstance();
    }
    
    public function testEmailAccountApi()
    {
        $this->_uit = $this->_json;
        $account = $this->_testSimpleRecordApi(
            'EmailAccount', // use non-existant model to make simple api test work
            'name',
            null,
            true,
            ['type' => Felamimail_Model_Account::TYPE_SHARED, 'password' => '123', 'email' => 'a@' . TestServer::getPrimaryMailDomain()],
            false
        );
        self::assertEquals('Templates', $account['templates_folder'], print_r($account, true));

        // search by some invalid string should not return any accounts
        $filter = [[
           'field' => 'query',
           'operator' => 'contains',
           'value' => Tinebase_Record_Abstract::generateUID()
        ]];
        $result = $this->_uit->searchEmailAccounts($filter, []);
        self::assertEquals(0, $result['totalcount'], 'a new (system?) account has been added');
    }

    /**
     * @param bool $sendgrant
     * @param array $data
     * @return array
     */
    public static function getSharedAccountData($sendgrant = true, $data = [])
    {
        return array_merge([
            'name' => 'unittest shared account',
            'email' => 'shared' . Tinebase_Record_Abstract::generateUID(6) . '@' . TestServer::getPrimaryMailDomain(),
            'type' => Felamimail_Model_Account::TYPE_SHARED,
            'password' => '123',
            'grants' => [
                [
                    'readGrant' => true,
                    'editGrant' => true,
                    'addGrant' => $sendgrant,
                    'account_type' => 'user',
                    'account_id' => Tinebase_Core::getUser()->getId(),
                ]
            ]
        ], $data);
    }

    /**
     * testSearchUserEmailAccounts - returns all TYPE_SYSTEM user accounts
     *
     * @return array
     */
    public function testSearchUserEmailAccounts()
    {
        // we should already have some "SYSTEM" accounts for the persona users
        $filter = [[
            'field' => 'type',
            'operator' => 'equals',
            'value' => Felamimail_Model_Account::TYPE_SYSTEM,
        ]];
        $result = $this->_json->searchEmailAccounts($filter, []);
        self::assertGreaterThan(1, $result['totalcount'], 'system accounts of other users not found');

        // client sends some strange filters ...
        $filter = array (
            0 =>
                array (
                    'condition' => 'OR',
                    'filters' =>
                        array (
                            0 =>
                                array (
                                    'condition' => 'AND',
                                    'filters' =>
                                        array (
                                            0 =>
                                                array (
                                                    'field' => 'query',
                                                    'operator' => 'contains',
                                                    'value' => '',
                                                    'id' => 'ext-record-23',
                                                ),
                                        ),
                                    'id' => 'ext-comp-1189',
                                    'label' => 'Konten',
                                ),
                        ),
                    'id' => 'FilterPanel',
                ),
            1 =>
                array (
                    'field' => 'query',
                    'operator' => 'contains',
                    'value' => '',
                    'id' => 'quickFilter',
                ));
        $result = $this->_json->searchEmailAccounts($filter, []);
        self::assertGreaterThan(1, $result['totalcount'], 'system accounts of other users not found');
        return $result['results'];
    }

    /**
     * @param bool $delete
     * @param array $accountdata
     * @return Felamimail_Model_Account
     * @throws Felamimail_Exception
     * @throws Felamimail_Exception_IMAPInvalidCredentials
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function testEmailAccountApiSharedAccount($delete = true, $accountdata = [])
    {
        $this->_uit = $this->_json;
        $accountdata = self::getSharedAccountData(true, $accountdata);
        $account = $this->_json->saveEmailAccount($accountdata);
        self::assertEquals($accountdata['email'], $account['email']);
        self::assertTrue(isset($account['grants']), 'grants missing');
        self::assertEquals(1, count($account['grants']));
        self::assertTrue(isset($account['grants'][0]['account_name']), 'account_id missing: '. print_r($account['grants'], true));
        self::assertTrue(is_array($account['grants'][0]['account_name']), 'account_id needs to be resolved: '
            . print_r($account['grants'], true));
        self::assertEquals(1, $account['grants'][0]['addGrant'], 'add grant should be set: '
            . print_r($account['grants'], true));

        $account['display_format'] = Felamimail_Model_Account::DISPLAY_PLAIN;
        // client sends empty pws - should not be changed!
        $account['password'] = '';
        $account['smtp_password'] = '';
        // client also sends empty user_id - server should handle this
        $account['user_id'] = null;
        $updatedAccount = $this->_json->saveEmailAccount($account);
        self::assertEquals(Felamimail_Model_Account::DISPLAY_PLAIN, $updatedAccount['display_format']);

        // TODO reactivate for shared accounts
        // self::assertEquals($account['xprops']['emailUserIdImap'], $updatedAccount['email_imap_user']['emailUserId']);

        // we need to commit so imap user is in imap db
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);

        $account = new Felamimail_Model_Account(array_filter($updatedAccount, function ($val) { return !is_array($val);}));
        $account->resolveCredentials(false);
        // this will actually log in into imap, which only works if the user is committed to imap db
        Felamimail_Backend_ImapFactory::factory($account);

        if ($delete) {
            $this->_uit->deleteEmailAccounts($account->getId());
        } else {
            $this->_emailAccounts[] = $account;
        }
        if ($delete) {
            return $account;
        } else {
            return Admin_Controller_EmailAccount::getInstance()->get($account->getId());
        }
    }

    public function testEmailAccountApiSharedDuplicateAccount()
    {
        $account = $this->testEmailAccountApiSharedAccount(false);

        try {
            static::expectException(Tinebase_Exception_SystemGeneric::class);
            $translate = Tinebase_Translation::getTranslation('Tinebase');
            $this->expectExceptionMessageMatches('/' . $translate->_('Email account already exists') . '/');
            $this->testEmailAccountApiSharedAccount(true, [
                'email' => $account->email
            ]);
        } finally {
            $this->_json->deleteEmailAccounts($account->getId());
        }
    }

    public function testEmailAccountSaveAsContact()
    {
        $this->_testNeedsTransaction();
        
        $account = $this->_createExternalAccount(Felamimail_Model_Account::VISIBILITY_DISPLAYED);

        try {
            
            $contact = Addressbook_Controller_Contact::getInstance()->get($account['contact_id']);

            self::assertEquals($account['user_id']['accountDisplayName'], $contact['n_fileas']);
            self::assertEquals($account['email'], $contact['email']);
            self::assertEquals($account['organization'], $contact['org_name']);

            $account['visibility'] = Felamimail_Model_Account::VISIBILITY_HIDDEN;
            $account = $this->_json->saveEmailAccount($account);
            $contact = Addressbook_Controller_Contact::getInstance()->get($account['contact_id'], null, true, true);
            // we do soft delete , so contact id should be set
            self::assertEquals('1', $contact['is_deleted'], 'contact should be delete softly');

            $account['visibility'] = Felamimail_Model_Account::VISIBILITY_DISPLAYED;
            $account = $this->_json->saveEmailAccount($account);
            $contact = Addressbook_Controller_Contact::getInstance()->get($account['contact_id'], null, true, true);
            self::assertEquals('0', $contact['is_deleted'], 'contact should be restored');
        } finally {
            if ($account) {
                $this->_json->deleteEmailAccounts([$account['id']]);
            }
        }
    }

    public function testUpdateSystemAccountReplyTo()
    {
        $systemaccount = TestServer::getInstance()->getTestEmailAccount();
        if (! $systemaccount) {
            self::markTestSkipped('no systemaccount configured');
        }
        $systemaccountArray = $this->_json->getEmailAccount($systemaccount->getId());
        $systemaccountArray['reply_to'] = 'someotheraddress@' . TestServer::getPrimaryMailDomain();

        // js fe sends credentials_id fields as empty string ...
        $systemaccountArray['credentials_id'] = '';
        $systemaccountArray['smtp_credentials_id'] = '';

        $updatedAccount = $this->_json->saveEmailAccount($systemaccountArray);
        self::assertEquals($systemaccountArray['reply_to'], $updatedAccount['reply_to']);
    }

    public function testCreateSystemAccountWithDuplicateEmailAddress()
    {
        $this->_uit = $this->_json;
        $accountdata = [
            'email' => Tinebase_Core::getUser()->accountEmailAddress,
            'type' => Felamimail_Model_Account::TYPE_SHARED,
            'password' => '123',
        ];
        try {
            $this->_json->saveEmailAccount($accountdata);
            self::fail('it should not be possible to create accounts with duplicate email addresses');
        } catch (Tinebase_Exception_SystemGeneric $ted) {
            $translate = Tinebase_Translation::getTranslation('Tinebase');
            self::assertEquals($translate->_('Email account already exists'), $ted->getMessage());
        }
    }

    public function testCreateExternalAccountAndUpdateCredentials()
    {
        $account = $this->_createExternalAccount();

        // check pw of account
        $fmailaccount = Felamimail_Controller_Account::getInstance()->get($account['id']);
        $imapConfig = $fmailaccount->getImapConfig();
        $credentials = TestServer::getInstance()->getTestCredentials();
        self::assertEquals($credentials['password'], $imapConfig['password']);

        // update credentials
        $account['password'] = 'someotherpw';
        $account['user'] = $this->_personas['sclever']->accountEmailAddress;
        $updatedAccount = $this->_json->saveEmailAccount($account);
        $fmailaccount = Felamimail_Controller_Account::getInstance()->get($account['id']);
        $imapConfig = $fmailaccount->getImapConfig();
        self::assertEquals($account['password'], $imapConfig['password']);
        self::assertTrue(isset($updatedAccount['user']), 'username should be resolved: '
            . print_r($updatedAccount, true));
        self::assertEquals($account['user'], $updatedAccount['user']);
    }

    /**
     * add sclevers email account as external
     *
     * @return array
     */
    protected function _createExternalAccount($visibility=Felamimail_Model_Account::VISIBILITY_HIDDEN)
    {
        $this->_uit = $this->_json;
        $credentials = TestServer::getInstance()->getTestCredentials();
        $accountdata = [
            'email' => Tinebase_Core::getUser()->accountEmailAddress,
            'type' => Felamimail_Model_Account::TYPE_USER,
            'user' => Tinebase_Core::getUser()->accountEmailAddress,
            'password' => $credentials['password'],
            'user_id' => Tinebase_Core::getUser()->getId(),
            'visibility' => $visibility
        ];
        return $this->_json->saveEmailAccount($accountdata);
    }

    public function testMoveExternalAccountToAnotherUser()
    {
        $account = $this->_createExternalAccount();
        $account['user_id'] = $this->_personas['sclever']['accountId'];
        try {
            $movedaccount = $this->_json->saveEmailAccount($account);
            self::fail('it should not be possible to change external accounts to another user');
        } catch (Tinebase_Exception_SystemGeneric $ted) {
            $translate = Tinebase_Translation::getTranslation('Tinebase');
            self::assertEquals($translate->_('Can´t add additional personal external account for another user account.'), $ted->getMessage());
        }
        /*$json = new Felamimail_Frontend_Json();
        $result = $json->searchAccounts([]);
        $scleverExtraAccounts = array_filter($result['results'], function($account) use ($movedaccount) {
            return ($account['id'] === $movedaccount['id']);
        });
        self::assertEquals(1, count($scleverExtraAccounts), 'sclever account is missing');
        @todo fix me to add external accounts
        */
    }

    public function testUpdateSystemAccountWithDuplicateEmailAddress()
    {
        $this->_uit = $this->_json;
        $accountdata = [
            'email' => 'shooo@' . TestServer::getPrimaryMailDomain(),
            'type' => Felamimail_Model_Account::TYPE_SHARED,
            'password' => '123',
        ];
        $account = $this->_json->saveEmailAccount($accountdata);
        $account['email'] = Tinebase_Core::getUser()->accountEmailAddress;

        try {
            $this->_json->saveEmailAccount($account);
            self::fail('it should not be possible to update accounts with duplicate email addresses');
        } catch (Tinebase_Exception_SystemGeneric $ted) {
            $translate = Tinebase_Translation::getTranslation('Tinebase');
            self::assertEquals($translate->_('Email account already exists'), $ted->getMessage());
        }
    }

    public function testUpdateSystemAccountChangeUsername()
    {
        $this->_uit = $this->_json;
        $accountdata = [
            'email' => 'shooo@' . TestServer::getPrimaryMailDomain(),
            'type' => Felamimail_Model_Account::TYPE_SHARED,
            'password' => '123',
        ];
        $account = $this->_json->saveEmailAccount($accountdata);
        $account['user'] = 'someusername';

        $updatedAccount = $this->_json->saveEmailAccount($account);
        $fmailaccount = Felamimail_Controller_Account::getInstance()->get($updatedAccount['id']);
        $imapConfig = $fmailaccount->getImapConfig();
        self::assertNotEquals($account['user'], $imapConfig['user']);
    }

    public function testUpdateSystemAccountChangeEmail()
    {
        $user = $this->_createUserWithEmailAccount();
        $emailAccount = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);
        self::assertNotNull($emailAccount);
        $emailAccount->email = 'somenewmail' . Tinebase_Record_Abstract::generateUID(6) . '@' . TestServer::getPrimaryMailDomain();
        $updatedAccount = $this->_json->saveEmailAccount($emailAccount->toArray());
        self::assertEquals($emailAccount->email, $updatedAccount['email']);
        $updatedUser = Tinebase_User::getInstance()->getFullUserById($user->getId());
        self::assertEquals($emailAccount->email, $updatedUser->accountEmailAddress);
        $updatedUser->accountEmailAddress = '';
        $updatedUserArray = $this->_json->saveUser($updatedUser->toArray());
        self::assertEmpty($updatedUserArray['accountEmailAddress']);
        self::assertNull(Admin_Controller_EmailAccount::getInstance()->getSystemAccount($updatedUser));
    }

    public function testSetSieveVacation()
    {
        $this->_checkMasterUserTable();
        $account = $this->testEmailAccountApiSharedAccount(false);

        // set vacation for account via admin fe
        $vacationData = Felamimail_Frontend_JsonTest::getVacationData($account);
        $vacationData['start_date'] = '2012-04-18';
        $vacationData['end_date'] = '2012-04-20';
        
        $result = $this->_json->saveSieveVacation($vacationData);
        $script = $this->_json->getSieveScript($account->getId());
        self::assertEquals($vacationData['subject'], $result['subject']);
        $this->assertStringContainsString('currentdate', $script);
    }

    public function testSaveEmailAccountWithSieveScript()
    {
        $this->_checkMasterUserTable();
        $account = $this->testEmailAccountApiSharedAccount(false);
        
        // set vacation and rule for account via admin fe
        $vacationData = Felamimail_Frontend_JsonTest::getVacationData($account);
        $vacationData['start_date'] = '2012-04-18';
        $vacationData['end_date'] = '2012-04-20';
        
        $account->sieve_rules = [];
        $account->sieve_vacation = $vacationData;
        
        $this->_json->saveEmailAccount($account->toArray());
        $script = $this->_json->getSieveScript($account->getId());
        $this->assertStringContainsString('currentdate', $script);

        $account->sieve_rules = $this->_getSieveRuleData();
        $this->_json->saveEmailAccount($account->toArray());
        $script = $this->_json->getSieveScript($account->getId());
        $this->assertStringContainsString('currentdate', $script);

        $translation = Tinebase_Translation::getTranslation('Felamimail');
        $this->_assertHistoryNote($account, $translation->_('Sieve vacation has been updated:') . ' ' .
            $translation->_('Vacation message is now active.'), Felamimail_Model_Account::class, 2);
    }

    public function testSetSieveRules()
    {
        $this->_checkMasterUserTable();
        $account = $this->testEmailAccountApiSharedAccount(false);

        // set rules for account via admin fe
        $rules = $this->_getSieveRuleData();
        $result = $this->_json->saveRules($account['id'], $rules);
        self::assertEquals(1, count($result));

        $result = $this->_json->getSieveRules($account['id']);
        self::assertEquals(1, $result['totalcount']);
    }

    protected function _getSieveRuleData()
    {
        return array(array(
            'id' => 1,
            'action_type' => Felamimail_Sieve_Rule_Action::FILEINTO,
            'action_argument' => 'Junk',
            'conjunction' => 'allof',
            'conditions' => array(array(
                'test' => Felamimail_Sieve_Rule_Condition::TEST_ADDRESS,
                'comperator' => Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS,
                'header' => 'From',
                'key' => '"abcd" <info@example.org>',
            )),
            'enabled' => 1,
        ));
    }

    public function testGetSetSieveRuleForSclever()
    {
        $this->_checkMasterUserTable();
        $systemAccounts = $this->testSearchUserEmailAccounts();

        $this->_testNeedsTransaction();

        $sclever = $this->_personas['sclever'];
        $newPw = Tinebase_Record_Abstract::generateUID(10);
        // set new pw to prevent access with the unittest users pw
        Admin_Controller_User::getInstance()->setAccountPassword($sclever, $newPw, $newPw);
        $this->_scleverPwChanged = true;
        $scleverAccount = array_filter($systemAccounts, function($account) use ($sclever) {
            return (is_array($account['user_id']) && $account['user_id']['accountId'] === $sclever->getId());
        });
        $scleverAccount = array_pop($scleverAccount);
        try {
            $result = $this->_json->saveRules($scleverAccount['id'], []);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            self::markTestSkipped('FIXME: something failed with the test account - maybe another test did not clean up?');
        }
        self::assertEquals(0, count($result));

        $result = $this->_json->getSieveRules($scleverAccount['id']);
        self::assertEquals(0, $result['totalcount']);

        $rules = $this->_getSieveRuleData();
        $result = $this->_json->saveRules($scleverAccount['id'], $rules);
        self::assertEquals(1, count($result));
    }

    protected function _checkMasterUserTable()
    {
        $imapEmailBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        if (method_exists($imapEmailBackend, 'checkMasterUserTable')) {
            try {
                $imapEmailBackend->checkMasterUserTable();
            } catch (Tinebase_Exception_NotFound $tenf) {
                self::markTestSkipped('could not find master user table');
            }
        } else {
            self::markTestSkipped('could not find checkMasterUserTable');
        }
    }

    public function testUpdatePasswordOfSharedAccount()
    {
        $sharedAccount = $this->testEmailAccountApiSharedAccount(false);
        $sharedAccount->resolveCredentials(false);
        self::assertEquals('123', $sharedAccount->password);

        $emailUser = Felamimail_Controller_Account::getInstance()->getSharedAccountEmailUser($sharedAccount);
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertTrue(is_array($userInBackend), 'user not found in backend');
        self::assertNotEmpty($userInBackend['password'], print_r($userInBackend, true));
        $pw = $userInBackend['password'];

        $sharedAccountArray = $sharedAccount->toArray();
        $newPw = 'someupdatedPW';
        $sharedAccountArray['password'] = $newPw;
        $this->_json->saveEmailAccount($sharedAccountArray);
        // test imap login
        $sharedAccount = Felamimail_Controller_Account::getInstance()->get($sharedAccount);
        self::assertEquals($sharedAccount->credentials_id, $sharedAccount->smtp_credentials_id);
        Felamimail_Backend_ImapFactory::factory($sharedAccount->getId());
        $sharedAccount->resolveCredentials(false);
        self::assertNotEmpty($sharedAccount->user, 'username should not be empty/overwritten! '
            . print_r($sharedAccount->toArray(), true));
        self::assertEquals($newPw, $sharedAccount->password);
        $sharedAccount->resolveCredentials(false, false, true);
        self::assertEquals($newPw, $sharedAccount->smtp_password);

        // check if pw was changed
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertNotEquals($pw, $userInBackend['password']);
    }

    public function testCreatePersonalSystemAccount()
    {
        $this->_testNeedsTransaction();

        // create "user" account for sclever
        $email = 'sclever2@' . TestServer::getPrimaryMailDomain();
        $accountData = [
            'name' => 'sclever 2 account',
            'email' => $email,
            'type' => Felamimail_Model_Account::TYPE_USER_INTERNAL,
            'user_id' => $this->_personas['sclever']->getId(),
        ];
        try {
            $userInternalAccount = $this->_json->saveEmailAccount($accountData);
        } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            // (re-)create system account for sclever first
            $credentials = TestServer::getInstance()->getTestCredentials();
            Tinebase_EmailUser_XpropsFacade::setXprops($this->_personas['sclever'], null, false);
            Admin_Controller_User::getInstance()->update($this->_personas['sclever']);
            Admin_Controller_User::getInstance()->setAccountPassword(
                $this->_personas['sclever'], $credentials['password'], $credentials['password']);
            $userInternalAccount = $this->_json->saveEmailAccount($accountData);
        }
        $this->_emailAccounts[] = $userInternalAccount;

        $filter = [[
            'field' => 'type',
            'operator' => 'equals',
            'value' => Felamimail_Model_Account::TYPE_USER_INTERNAL,
        ], [
            'field' => 'name',
            'operator' => 'equals',
            'value' => 'sclever 2 account',
        ]];
        $result = $this->_json->searchEmailAccounts($filter, []);
        self::assertEquals(1, $result['totalcount'], 'no USER_INTERNAL accounts found');
        $account = $result['results'][0];
        self::assertEquals($email, $account['email'], print_r($account, true));

        // check imap email user
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord(new Felamimail_Model_Account($account));
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertEquals($email, $userInBackend['loginname'], print_r($userInBackend, true));
        // credentials of new $userInternalAccount have been resolved in \Felamimail_Convert_Account_Json::fromTine20Model - check if they match
        self::assertEquals($userInternalAccount['user'], $userInBackend['username'], print_r($userInBackend, true));

        // check smtp email user
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        $userInSmtpBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertNotEmpty($userInSmtpBackend['destination'], print_r($userInSmtpBackend, true));

        // write message to userInternal account
        $this->_sendMessageWithAccount(null, $userInternalAccount['email']);

        return $account;
    }

    public function testResetUserPWOfPersonalSystemAccount()
    {
        $this->_skipIfLDAPBackend();
        $scleverId = $this->_personas['sclever']->getId();
        $pw = '54321';

        $adminFE = new Admin_Frontend_Json();
        $adminFE->resetPassword($scleverId, '12345', false);
        $this->testCreatePersonalSystemAccount();
        $adminFE->resetPassword($scleverId, $pw, false);
        self::validateImapUserPw($scleverId, $pw, Felamimail_Model_Account::TYPE_USER_INTERNAL);
    }

    public static function validateImapUserPw(string $userId, string $pw, string $accountType = Felamimail_Model_Account::TYPE_SYSTEM)
    {
        $account = Admin_Controller_EmailAccount::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                ['field' => 'type', 'operator' => 'equals', 'value' => $accountType],
                ['field' => 'user_id', 'operator' => 'equals', 'value' => $userId]
            ]))->getFirstRecord();

        self::assertNotNull($account);

        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
        // fetch email pw from db
        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        $rawDovecotUser = $dovecot->getRawUserById($emailUser);
        if ($rawDovecotUser) {
            // only check pw if dovecot user is found
            self::assertNotEmpty($rawDovecotUser['password']);
            $hashPw = new Hash_Password();
            self::assertTrue($hashPw->validate($rawDovecotUser['password'], $pw), 'password mismatch! dovecot user:'
                . print_r($rawDovecotUser, TRUE));
        }
    }

    /**
     * system -> shared
     *
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testConvertEmailAccount()
    {
        $this->_testNeedsTransaction();

        $user = $this->_createUserWithEmailAccount();

        $emailAccount = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);
        $this->_emailAccounts[] = $emailAccount;
        $this->_convertAccount($emailAccount, $user, Felamimail_Model_Account::TYPE_SHARED);
        $updatedUser = Admin_Controller_User::getInstance()->get($user->getId());
        self::assertEmpty($updatedUser->accountEmailAddress);
        self::assertFalse(isset($updatedUser->xprops()[Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_IMAP]),
            'email user xprops still set: ' . print_r($updatedUser->xprops(), true));
    }

    /**
     * @return array
     * @param Felamimail_Model_Account $emailAccount
     * @param Tinebase_Model_FullUser $user
     * @param string $convertTo
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _convertAccount(Felamimail_Model_Account $emailAccount, $user, $convertTo)
    {
        if ($emailAccount->type !== Felamimail_Model_Account::TYPE_SHARED) {
            $emailAccount->migration_approved = 1;
            Admin_Controller_EmailAccount::getInstance()->update($emailAccount);
        }

        $emailAccountArray = $emailAccount->toArray();
        if ($convertTo === Felamimail_Model_Account::TYPE_SHARED) {
            $emailAccountArray['password'] = Tinebase_Record_Abstract::generateUID(10);
        } else if (in_array($convertTo, [
            Felamimail_Model_Account::TYPE_SYSTEM,
            Felamimail_Model_Account::TYPE_USER_INTERNAL,
        ])) {
            $emailAccountArray['user_id'] = $user->getId();
        }
        $convertFrom = $emailAccount->type;
        $emailAccountArray['type'] = $convertTo;
        $convertedAccount = $this->_json->saveEmailAccount($emailAccountArray);
        $this->_emailAccounts[] = $convertedAccount;

        self::assertEquals($convertTo, $convertedAccount['type']);
        if (in_array($convertTo, [
            Felamimail_Model_Account::TYPE_SYSTEM,
            Felamimail_Model_Account::TYPE_USER_INTERNAL,
        ])) {
            self::assertTrue(is_array($convertedAccount['user_id']), print_r($convertedAccount, true));
            self::assertEquals($user->getId(), $convertedAccount['user_id']['accountId'],
                'user id of ' . $user->accountLoginName . ' not found in converted account: '
                . print_r($convertedAccount, true));
            $testUserAccount = $convertedAccount['user_id']['accountId'] === Tinebase_Core::getUser()->getId();
        } else {
            self::assertEmpty($convertedAccount['user_id'], 'user_id should be empty: ' . print_r($convertedAccount, true));
            $testUserAccount = false;
        }

        if ($convertFrom === Felamimail_Model_Account::TYPE_SYSTEM) {
            $updatedUser = Tinebase_User::getInstance()->getUserById($user->getId());
            self::assertEmpty($updatedUser->accountEmailAddress, 'user email address should be empty after account conversion');
        }

        // add current user to shared account
        if ($convertTo === Felamimail_Model_Account::TYPE_SHARED) {
            $convertedAccount['grants'][] = [
                'readGrant' => true,
                'editGrant' => true,
                'addGrant' => true,
                'account_type' => 'user',
                'account_id' => Tinebase_Core::getUser()->getId(),
            ];
            $this->_json->saveEmailAccount($convertedAccount);
        }

        if ($convertTo === Felamimail_Model_Account::TYPE_SHARED || $testUserAccount) {
            $this->_sendMessageWithAccount($convertedAccount);
        }

        return $convertedAccount;
    }

    /**
     * @return array
     * @param Tinebase_Model_FullUser $user
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testConvertUserInternalEmailAccount($user = null)
    {
        $this->_testNeedsTransaction();

        if (! $user) {
            $user = $this->_createUserWithEmailAccount();
        }
        $accountData = self::getUserInternalAccountData($user);
        $internalAccount = Admin_Controller_EmailAccount::getInstance()->create(
            new Felamimail_Model_Account($accountData));
        $this->_emailAccounts[] = $internalAccount;

        Felamimail_Controller_AccountTest::checkInternalUserAccount($internalAccount);

        return $this->_convertAccount($internalAccount, $user, Felamimail_Model_Account::TYPE_SHARED);
    }

    /**
     * @param Tinebase_Model_User $user
     * @return array
     */
    public static function getUserInternalAccountData($user)
    {
        return [
            'name' => 'unittest user internal account',
            'email' => 'myinternal' . Tinebase_Record_Abstract::generateUID(6) . '@' . TestServer::getPrimaryMailDomain(),
            'type' => Felamimail_Model_Account::TYPE_USER_INTERNAL,
            'user_id' => $user->getId(),
        ];
    }

    public function testConvertSharedToUserInternalEmailAccount()
    {
        $sharedAccount = $this->testEmailAccountApiSharedAccount(false);
        $this->_convertAccount($sharedAccount, Tinebase_Core::getUser(), Felamimail_Model_Account::TYPE_USER_INTERNAL);
    }

    public function testConvertInternalToSharedToUserInternalEmailAccount()
    {
        $user = $this->_createUserWithEmailAccount();
        $systemAccount = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);
        $this->_emailAccounts[] = $systemAccount;
        $sharedAccount = $this->testConvertUserInternalEmailAccount($user);
        $this->_emailAccounts[] = $sharedAccount;
        $sharedAccount['user_id'] = $user->getId();

        try {
            // does not work because the users system account has been converted and the users password is needed
            $this->_convertAccount(new Felamimail_Model_Account($sharedAccount), $user, Felamimail_Model_Account::TYPE_USER_INTERNAL);
        } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            self::assertEquals('System account of user is missing', $teuv->getMessage());
        }
    }

    public function testConvertSharedToUserInternalEmailAccountWithMails()
    {
        // send mail to shared account
        $sharedAccount = $this->testEmailAccountApiSharedAccount(false);
        $subject = 'test message ' . Tinebase_Record_Abstract::generateUID(10);
        $message = new Felamimail_Model_Message(array(
            'account_id'    => $sharedAccount->getId(),
            'subject'       => $subject,
            'to'            => $sharedAccount->email,
            'body'          => 'aaaaaä <br>',
        ));
        Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);
        $account = $this->_convertAccount($sharedAccount, Tinebase_Core::getUser(), Felamimail_Model_Account::TYPE_USER_INTERNAL);

        Felamimail_Controller_Cache_Folder::getInstance()->update($account['id']);
        $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account['id'], 'INBOX');
        $updatedFolder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10, 1);
        self::assertGreaterThan($folder->imap_totalcount, $updatedFolder->imap_totalcount);
    }

    public function testConvertSystemToUserInternalEmailAccount()
    {
        $this->_testNeedsTransaction();

        $user = $this->_createUserWithEmailAccount();

        $emailAccount = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);
        $this->_emailAccounts[] = $emailAccount;
        $this->_convertAccount($emailAccount, $user, Felamimail_Model_Account::TYPE_USER_INTERNAL);
        $updatedUser = Admin_Controller_User::getInstance()->get($user->getId());
        self::assertFalse(isset($updatedUser->xprops()[Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_IMAP]),
            'email user xprops still set: ' . print_r($updatedUser->xprops(), true));
    }
    
    public function testResolveAccountEmailUsers()
    {
        $systemaccount = TestServer::getInstance()->getTestEmailAccount();
        if (! $systemaccount) {
            self::markTestSkipped('no systemaccount configured');
        }
        try {
            $systemaccountArray = $this->_json->getEmailAccount($systemaccount->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            self::markTestSkipped('email account not found - maybe some test setup failure');
        }

        self::assertNotNull($systemaccountArray['xprops'],  'xprops should not be null');
        self::assertArrayHasKey(Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP , $systemaccountArray['xprops'],  'imap email user id should be set');
        self::assertArrayHasKey(Felamimail_Model_Account::XPROP_EMAIL_USERID_SMTP , $systemaccountArray['xprops'],  'smtp email user id should be set');
        self::assertArrayHasKey('email_imap_user', $systemaccountArray,  'email_imap_user should be set');
        self::assertArrayHasKey('email_smtp_user', $systemaccountArray,  'email_smtp_user should be set');
    }


    public function testUpdateAccountEmailUsers()
    {
        // change email address and check if email user is updated, too
        $this->_testNeedsTransaction();
  
        $sharedAccount = $this->testEmailAccountApiSharedAccount(false);

        // test imap user
        $quotaByte = 3000 * 1024 * 1024;
        $sharedAccount->email_imap_user = [
            'emailMailQuota' => $quotaByte,
            'emailSieveQuota' => $quotaByte
        ];

        Admin_Controller_EmailAccount::getInstance()->updateAccountEmailUsers($sharedAccount);
        
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $pseudoFullUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($sharedAccount);
        $userInBackend = $emailUserBackend->getRawUserById($pseudoFullUser);

        self::assertEquals($quotaByte , $userInBackend['quota_bytes'] * 1024 * 1024, 'email was not updated');
        self::assertEquals($quotaByte , $userInBackend['quota_message'] * 1024 * 1024, 'email was not updated');

        // test smtp user
        $sharedAccount->email_smtp_user = [
            'emailForwards'    => array(Tinebase_Core::getUser()->accountEmailAddress, 'test@tine20.org'),
            'emailAliases'     => array('bla@tine20.org', 'blubb@tine20.org')
        ];
        
        Admin_Controller_EmailAccount::getInstance()->updateAccountEmailUsers($sharedAccount);
        $pseudoFullUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($sharedAccount);

        $this->assertEquals(array(Tinebase_Core::getUser()->accountEmailAddress, 'test@tine20.org'),
            $pseudoFullUser->smtpUser->emailForwards->email, 'emailForwards was not updated');
        $this->assertEquals(array('bla@tine20.org', 'blubb@tine20.org'),
            $pseudoFullUser->smtpUser->emailAliases->email, 'emailAliases was not updated');
    }

    public function testCreateSharedAccountWithInbox()
    {
        // change email address and check if email user is updated, too
        $this->_testNeedsTransaction();
        $sharedAccount = $this->testEmailAccountApiSharedAccount(false);
        $json = new Felamimail_Frontend_Json();
        $folders = $json->searchFolders([
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $sharedAccount['id']],
            ['field' => 'globalname', 'operator' => 'equals', 'value' => ''],
        ]);
        self::assertEquals(4, $folders['totalcount'], 'should find 5 initial folders. got: '
            . print_r($folders, true));
    }

    public function testRevealEmailAccountPassword()
    {
        $account = $this->testEmailAccountApiSharedAccount(false);
        $fmailaccount = Felamimail_Controller_Account::getInstance()->get($account['id']);
        $imapConfig = $fmailaccount->getImapConfig();

        $result = $this->_json->revealEmailAccountPassword($account->getId());
        self::assertEquals($result['password'], $imapConfig['password'], 'reveal password failed');

        $records = Tinebase_Notes::getInstance()->searchNotes(new Tinebase_Model_NoteFilter([
            ['field' => 'record_id', 'operator' => 'equals', 'value' => $account->getId()],
            ['field' => 'record_model', 'operator' => 'equals', 'value' => Felamimail_Model_Account::class],
            ['field' => 'note_type_id', 'operator' => 'equals', 'value' => Tinebase_Model_Note::SYSTEM_NOTE_REVEAL_PASSWORD]
        ]));
        self::assertCount(1, $records, 'reveal password failed');

        // test again with empty param
        $result = $this->_json->revealEmailAccountPassword('');
        self::assertEmpty($result);
    }
}
