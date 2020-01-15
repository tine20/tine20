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
    protected function setUp()
    {
        parent::setUp();

        $this->_json = new Admin_Frontend_Json();
    }

    protected function tearDown()
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
    }
    
    public function testEmailAccountApi()
    {
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

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
        $accountdata = array_merge([
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
        return $accountdata;
    }

    public static function getUserInternalAccountData($user)
    {
        $accountData = [
            'name' => 'unittest user internal account',
            'email' => 'myinternal' . Tinebase_Record_Abstract::generateUID(6) . '@' . TestServer::getPrimaryMailDomain(),
            'type' => Felamimail_Model_Account::TYPE_USER_INTERNAL,
            'user_id' => $user->getId(),
        ];
        return $accountData;
    }

    /**
     * testSearchUserEmailAccounts - returns all TYPE_SYSTEM user accounts
     *
     * @return array
     */
    public function testSearchUserEmailAccounts()
    {
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

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
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

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
        $updatedAccount = $this->_json->saveEmailAccount($account);
        self::assertEquals(Felamimail_Model_Account::DISPLAY_PLAIN, $updatedAccount['display_format']);

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
            static::setExpectedException(Tinebase_Exception_SystemGeneric::class, 'email account already exists');
            $this->testEmailAccountApiSharedAccount(true, [
                'email' => $account->email
            ]);
        } finally {
            $this->_json->deleteEmailAccounts($account->getId());
        }
    }

    public function testUpdateSystemAccount()
    {
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

        $systemaccount = $this->_getTestUserFelamimailAccount();
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
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

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
            self::assertEquals('email account already exists', $ted->getMessage());
        }
    }

    public function testUpdateSystemAccountWithDuplicateEmailAddress()
    {
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

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
            self::assertEquals('email account already exists', $ted->getMessage());
        }
    }

    public function testUpdateSystemAccountChangeUsername()
    {
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

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
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

        $user = $this->_createUserWithEmailAccount();
        $emailAccount = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);
        $emailAccount->email = 'somenewmail' . Tinebase_Record_Abstract::generateUID(6) . '@' . TestServer::getPrimaryMailDomain();
        $updatedAccount = $this->_json->saveEmailAccount($emailAccount->toArray());
        self::assertEquals($emailAccount->email, $updatedAccount['email']);
        $updatedUser = Tinebase_User::getInstance()->getFullUserById($user->getId());
        self::assertEquals($emailAccount->email, $updatedUser->accountEmailAddress);
    }

    public function testConvertEmailAccount()
    {
        self::markTestSkipped('TODO activate me');

        $this->_skipIfXpropsUserIdDeactivated();
        $this->_testNeedsTransaction();

        $user = $this->_createUserWithEmailAccount();

        $emailAccount = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);
        $this->_convertAccount($emailAccount, $user, Felamimail_Model_Account::TYPE_SHARED);
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
            // check if email account exists and mail sending works
            $subject = 'test message ' . Tinebase_Record_Abstract::generateUID(10);
            $message = new Felamimail_Model_Message(array(
                'account_id'    => $convertedAccount['id'],
                'subject'       => $subject,
                'to'            => Tinebase_Core::getUser()->accountEmailAddress,
                'body'          => 'aaaaaä <br>',
            ));
            $sendMessage = Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);
            self::assertEquals($message->subject, $sendMessage->subject);
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
        self::markTestSkipped('TODO activate me');

        $this->_skipIfXpropsUserIdDeactivated();

        $this->_testNeedsTransaction();

        if (! $user) {
            $user = $this->_createUserWithEmailAccount();
        }
        $accountData = self::getUserInternalAccountData($user);
        $internalAccount = Admin_Controller_EmailAccount::getInstance()->create(
            new Felamimail_Model_Account($accountData));
        $this->_emailAccounts[] = $internalAccount;

        return $this->_convertAccount($internalAccount, $user, Felamimail_Model_Account::TYPE_SHARED);
    }

    public function testConvertSharedToUserInternalEmailAccount()
    {
        self::markTestSkipped('TODO activate me');

        $this->_skipIfXpropsUserIdDeactivated();

        $sharedAccount = $this->testEmailAccountApiSharedAccount(false);
        $this->_convertAccount($sharedAccount, Tinebase_Core::getUser(), Felamimail_Model_Account::TYPE_USER_INTERNAL);
    }

    public function testConvertInternalToSharedToUserInternalEmailAccount()
    {
        self::markTestSkipped('TODO activate me');

        $this->_skipIfXpropsUserIdDeactivated();

        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }
        $user = $this->_createUserWithEmailAccount();
        $sharedAccount = $this->testConvertUserInternalEmailAccount($user);
        $sharedAccount['user_id'] = $user->getId();

        $this->_convertAccount(new Felamimail_Model_Account($sharedAccount), $user, Felamimail_Model_Account::TYPE_USER_INTERNAL);
    }

    public function testConvertSharedToUserInternalEmailAccountWithMails()
    {
        self::markTestSkipped('TODO activate me');

        $this->_skipIfXpropsUserIdDeactivated();

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
        self::assertEquals($folder->imap_totalcount + 1, $updatedFolder->imap_totalcount);
    }

    public function testSetSieveVacation()
    {
        $this->_checkMasterUserTable();
        $account = $this->testEmailAccountApiSharedAccount(false);

        // set vacation for account via admin fe
        $vacation = Felamimail_Frontend_JsonTest::getVacationData($account);
        $result = $this->_json->saveSieveVacation($vacation);
        self::assertEquals($vacation['subject'], $result['subject']);
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
            return ($account['user_id']['accountLoginName'] === $sclever->accountLoginName);
        });
        $scleverAccount = array_pop($scleverAccount);
        $result = $this->_json->saveRules($scleverAccount['id'], []);
        self::assertEquals(0, count($result));

        $result = $this->_json->getSieveRules($scleverAccount['id']);
        self::assertEquals(0, $result['totalcount']);

        $rules = $this->_getSieveRuleData();
        $result = $this->_json->saveRules($scleverAccount['id'], $rules);
        self::assertEquals(1, count($result));
    }

    protected function _checkMasterUserTable()
    {
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

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

        $emailUser = Felamimail_Controller_Account::getInstance()->getSharedAccountEmailUser($sharedAccount);
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertTrue(is_array($userInBackend), 'user not found in backend');
        self::assertNotEmpty($userInBackend['password'], print_r($userInBackend, true));
        $pw = $userInBackend['password'];

        $sharedAccountArray = $sharedAccount->toArray();
        $sharedAccountArray['password'] = 'someupdatedPW';
        $this->_json->saveEmailAccount($sharedAccountArray);
        // test imap login
        Felamimail_Backend_ImapFactory::factory($sharedAccount->getId());

        // check if pw was changed
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertNotEquals($pw, $userInBackend['password']);
    }
}
