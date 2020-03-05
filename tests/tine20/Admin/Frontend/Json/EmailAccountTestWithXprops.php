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
class Admin_Frontend_Json_EmailAccountTestWithXprops extends Admin_Frontend_Json_EmailAccountTest
{
    protected $_oldSetting = null;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        // convert current accounts
        Admin_Controller_User::getInstance()->convertAccountsToSaveUserIdInXprops();
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_oldSetting = Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS};
        Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS} = true;
    }

    protected function tearDown()
    {
        parent::tearDown();
        Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS} = $this->_oldSetting;
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
            $account = $this->_json->saveEmailAccount($accountData);
        } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            // (re-)create system account for sclever first
            $credentials = TestServer::getInstance()->getTestCredentials();
            Tinebase_EmailUser_XpropsFacade::setXprops($this->_personas['sclever'], null, false);
            Admin_Controller_User::getInstance()->update($this->_personas['sclever']);
            Admin_Controller_User::getInstance()->setAccountPassword(
                $this->_personas['sclever'], $credentials['password'], $credentials['password']);
            $account = $this->_json->saveEmailAccount($accountData);
        }
        $this->_emailAccounts[] = $account;

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

        // check email user
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord(new Felamimail_Model_Account($account));
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertEquals($email, $userInBackend['loginname'], print_r($userInBackend, true));
    }

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
            Felamimail_Backend_ImapFactory::reset();
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
        $sharedAccount = $this->testConvertUserInternalEmailAccount($user);
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
}
