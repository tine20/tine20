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
        if (! TestServer::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }

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
}
