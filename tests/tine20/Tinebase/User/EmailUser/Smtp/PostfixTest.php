<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_PostfixTest
 */
class Tinebase_User_EmailUser_Smtp_PostfixTest extends TestCase
{
    /**
     * user backend
     *
     * @var Tinebase_User
     */
    protected $_backend = NULL;

    /**
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * mailserver domain
     *
     * @var string
     */
    protected $_mailDomain = 'tine20.org';

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();

        $this->_backend = Tinebase_User::getInstance();

        if (   ! array_key_exists('Tinebase_EmailUser_Smtp_Postfix', $this->_backend->getPlugins())
            && ! array_key_exists('Tinebase_EmailUser_Smtp_PostfixMultiInstance', $this->_backend->getPlugins())
        ) {
            $this->markTestSkipped('Postfix SQL plugin not enabled');
        }

        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // error: Zend_Ldap_Exception: 0x44 (Already exists; 00002071: samldb: Account name (sAMAccountName)
            // 'tine20phpunituser' already in use!): adding: cn=PHPUnit User Tine 2.0,cn=Users,dc=example,dc=org
            $this->markTestSkipped('skipped for ad backends as it does not allow duplicate CNs');
        }

        $this->objects['users'] = array();

        $this->_mailDomain = TestServer::getPrimaryMailDomain();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
        foreach ($this->objects['users'] as $user) {
            try {
                Tinebase_User::getInstance()->deleteUser($user);
            } catch (Tinebase_Exception_NotFound $tenf) {
            }
        }

        parent::tearDown();
    }

    /**
     * try to add an user
     *
     * @return Tinebase_Model_FullUser
     */
    public function testAddUser()
    {
        $user = TestCase::getTestUser([
            'accountLoginName'      => 'phpunitpostfix',
            'accountEmailAddress'   => 'phpunitpostfix@' . $this->_mailDomain,
        ]);
        $user->smtpUser = new Tinebase_Model_EmailUser(array(
            'emailAddress'     => $user->accountEmailAddress,
            'emailForwardOnly' => true,
            'emailForwards'    => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
            'emailAliases'     => array('bla@' . $this->_mailDomain, 'blubb@' . $this->_mailDomain)
        ));

        $testUser = $this->_backend->addUser($user);
        $this->objects['users']['testUser'] = $testUser;

        $this->assertTrue($testUser instanceof Tinebase_Model_FullUser);
        $this->assertTrue(isset($testUser->smtpUser), 'no smtpUser data found in ' . print_r($testUser->toArray(),
                TRUE));
        $this->assertTrue(in_array('unittest@' . $this->_mailDomain, $testUser->smtpUser->emailForwards->email),
            'forwards not found');
        $this->assertTrue(in_array('test@' . $this->_mailDomain, $testUser->smtpUser->emailForwards->email),
            'forwards not found');

        $expectedAliases = ['bla@' . $this->_mailDomain, 'blubb@' . $this->_mailDomain];
        $foundAliases = array_filter($testUser->smtpUser->emailAliases->email, function($email) use ($expectedAliases) {
            return (in_array($email, $expectedAliases));
        });
        $this->assertEquals(2, count($foundAliases),
            'aliases not found: ' . print_r($expectedAliases, true) . ' in '
            . print_r($testUser->smtpUser->emailAliases, true));

        $this->assertEquals(true, $testUser->smtpUser->emailForwardOnly);
        $this->assertEquals($user->accountEmailAddress, $testUser->smtpUser->emailAddress);

        return $testUser;
    }

    /**
     * testGetUserAliases
     */
    public function testGetUserAliases()
    {
        $testUser = $this->testAddUser();
        $user = Tinebase_User::getInstance()->getFullUserByLoginName($testUser->accountLoginName);
        self::assertEquals(2, count($user->emailUser->emailAliases));
    }

    /**
     * try to update an email account
     */
    public function testUpdateUser()
    {
        // add smtp user
        $user = $this->testAddUser();

        // update user
        $user->smtpUser->emailForwardOnly = 1;
        $user->smtpUser->emailAliases = new Tinebase_Record_RecordSet(Tinebase_Model_EmailUser_Alias::class, [[
            Tinebase_Model_EmailUser_Alias::FLDS_EMAIL => 'bla@' . $this->_mailDomain,
            Tinebase_Model_EmailUser_Alias::FLDS_DISPATCH_ADDRESS => true,
        ]]);
        $user->smtpUser->emailForwards = new Tinebase_Record_RecordSet(Tinebase_Model_EmailUser_Forward::class);
        $user->accountEmailAddress = 'j.smith@' . $this->_mailDomain;

        $testUser = $this->_backend->updateUser($user);

        $this->assertEquals(0, count($testUser->smtpUser->emailForwards), 'forwards should be empty');
        $this->assertEquals(1, count($testUser->smtpUser->emailAliases), 'should have 1 alias: '
            . print_r($testUser->smtpUser->toArray(), true));
        $this->assertEquals([
            ['email' => 'bla@' . $this->_mailDomain, 'dispatch_address' => 1]
        ], $testUser->smtpUser->emailAliases->toArray(), 'aliases mismatch');
        $this->assertEquals(false, $testUser->smtpUser->emailForwardOnly);
        $this->assertEquals('j.smith@' . $this->_mailDomain, $testUser->smtpUser->emailAddress);
        $this->assertEquals($testUser->smtpUser->emailAliases->email, $testUser->emailUser->emailAliases->email,
            'smtp user data needs to be merged in email user: ' . print_r($testUser->emailUser->toArray(), TRUE));
    }

    /**
     * try to enable an account
     */
    public function testSetStatus()
    {
        $user = $this->testAddUser();
        $this->_backend->setStatus($user, Tinebase_User::STATUS_DISABLED);
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        $this->assertEquals(Tinebase_User::STATUS_DISABLED, $testUser->accountStatus);

        $this->_backend->setStatus($user, Tinebase_User::STATUS_ENABLED);
        $testUser = $this->_backend->getUserById($user, 'Tinebase_Model_FullUser');
        $this->assertEquals(Tinebase_User::STATUS_ENABLED, $testUser->accountStatus);
    }

    /**
     * try to update an email account
     */
    public function testSetPassword()
    {
        // add smtp user
        $user = $this->testAddUser();

        $newPassword = Tinebase_Record_Abstract::generateUID();
        $this->_backend->setPassword($user->getId(), $newPassword);

        // fetch email pw from db
        $db = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP)->getDb();
        $select = $db->select()
            ->from(array('smtp_users'))
            ->where($db->quoteIdentifier('userid') . ' = ?', $user->xprops()[Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_SMTP]);
        $stmt = $db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();

        $this->assertTrue(isset($queryResult['passwd']), 'no password in result: ' . print_r($queryResult, TRUE));
        $hashPw = new Hash_Password();
        $this->assertTrue($hashPw->validate($queryResult['passwd'], $newPassword), 'password mismatch: ' . print_r($queryResult, TRUE));
    }

    /**
     * testForwardedAlias
     *
     * @see 0007066: postfix email user: allow wildcard alias forwarding
     */
    public function testForwardedAlias()
    {
        if (array_key_exists('Tinebase_EmailUser_Smtp_PostfixMultiInstance', $this->_backend->getPlugins())
        ) {
            $this->markTestSkipped('Skipped for multiinstance backend because destination select works different');
        }

        $user = $this->testAddUser();

        // check destinations
        $queryResult = $this->_getDestinations($user);
        $this->assertEquals(6, count($queryResult), print_r($queryResult, TRUE));
        $expectedDestinations = array(
            'bla@' . $this->_mailDomain => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
            'blubb@' . $this->_mailDomain => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
            'phpunitpostfix@' . $this->_mailDomain => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
        );
        foreach ($expectedDestinations as $source => $destinations) {
            $foundDestinations = array();
            foreach ($queryResult as $row) {
                if ($row['source'] === $source) {
                    $foundDestinations[] = $row['destination'];
                }
            }
            $this->assertEquals(2, count($foundDestinations), 'source: ' . $source
                . ' / queryResult:' . print_r($queryResult, true));
            $this->assertTrue($foundDestinations == $destinations, print_r($destinations, TRUE));
        }
    }

    protected function _getDestinations($user)
    {
        $db = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP)->getDb();
        $select = $db->select()
            ->from(array('smtp_destinations'))
            ->where($db->quoteIdentifier('userid') . ' = ?', $user->xprops()[Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_SMTP]);
        $stmt = $db->query($select);
        $queryResult = $stmt->fetchAll();
        $stmt->closeCursor();

        return $queryResult;
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @todo make this test work
     */
    public function testAddUserCheckDefaultDestinations()
    {
        self::markTestSkipped('FIXME this breaks Felamimail_Frontend_ActiveSyncTest::testSendEmail');

        $this->_testNeedsTransaction();

        Tinebase_User::destroyInstance();
        $oldSmtpConf = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
        $smtpConf = clone $oldSmtpConf;
        $smtpConf->onlyemaildestination = true;
        Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $smtpConf);

        $emailAddress = 'phpunitpostfix2@' . $this->_mailDomain;
        $user = TestCase::getTestUser([
            'accountLoginName'      => 'phpunitpostfix2login',
            'accountEmailAddress'   => $emailAddress,
        ]);
        $user->smtpUser = new Tinebase_Model_EmailUser(array(
            'emailAddress'     => $user->accountEmailAddress,
            'emailForwardOnly' => true,
            'emailForwards'    => [],
            'emailAliases'     => [],
        ));

        $testUser = Tinebase_User::getInstance()->addUser($user);
        $this->objects['users'][] = $testUser;
        $queryResult = $this->_getDestinations($testUser);
        self::assertEquals(1, count($queryResult), 'user should only have 1 destination! '
            . print_r($queryResult, true));
        $destination = $queryResult[0];
        self::assertEquals($user->accountEmailAddress, $destination['source'], print_r($queryResult, true));
        self::assertEquals($user->accountEmailAddress, $destination['source'], print_r($queryResult, true));
        Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $oldSmtpConf);
        Tinebase_User::destroyInstance();
    }

    /**
     * testLotsOfAliasesAndForwards
     *
     * @see 0007194: alias table in user admin dialog truncated
     *
     * @todo make it work for multiinstance backend (102 aliases are found...)
     */
    public function testLotsOfAliasesAndForwards()
    {
        if (array_key_exists('Tinebase_EmailUser_Smtp_PostfixMultiInstance', $this->_backend->getPlugins())
        ) {
            $this->markTestSkipped('Skipped for multiinstance backend');
        }

        $user = $this->testAddUser();
        $aliases = $forwards = array();
        for ($i = 0; $i < 100; $i++) {
            $aliases[] = [
                Tinebase_Model_EmailUser_Alias::FLDS_EMAIL => 'alias_blablablablablablablablalbalbbl' . $i . '@' . $this->_mailDomain,
                Tinebase_Model_EmailUser_Alias::FLDS_DISPATCH_ADDRESS => true,
            ];
        }
        $user->smtpUser->emailAliases = new Tinebase_Record_RecordSet(Tinebase_Model_EmailUser_Alias::class, $aliases);
        for ($i = 0; $i < 100; $i++) {
            $forwards[] = 'forward_blablablablablablablablalbalbbl' . $i . '@' . $this->_mailDomain;
        }
        $user->smtpUser->emailForwards = $forwards;
        $testUser = $this->_backend->updateUser($user);

        $testUser = Tinebase_User::getInstance()->getUserById($testUser->getId(), 'Tinebase_Model_FullUser');
        $this->assertEquals(100, count($testUser->smtpUser->emailAliases));
        $this->assertEquals(100, count($testUser->smtpUser->emailForwards));
    }

    /**
     * testAliasEqualsDefaultMail
     */
    public function testAliasEqualsDefaultMail()
    {
        $user = $this->testAddUser();
        $user->smtpUser->emailAliases->addRecord(new Tinebase_Model_EmailUser_Alias([
            'email' => $user->accountEmailAddress,
            'dispatch_address' => 1,
        ]));
        try {
            $updatedUser = $this->_backend->updateUser($user);
            self::fail('alias should not be allowed to equal email address: ' . print_r($updatedUser->toArray(), true));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
        }

        $user->accountEmailAddress = $user->emailUser->emailAliases->getFirstRecord()->email;
        try {
            $updatedUser = $this->_backend->updateUser($user);
            self::fail('alias should not be allowed to equal email address: ' . print_r($updatedUser->toArray(), true));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
        }
    }

    public function testAliasesDispatchAddressFlag()
    {
        $user = $this->testAddUser();
        foreach ([0, '0', false] as $inputvalue) {
            foreach ($user->smtpUser->emailAliases as $alias) {
                $alias->dispatch_address = $inputvalue;
            }
            $updatedUser = $this->_backend->updateUser($user);
            foreach ($updatedUser->smtpUser->emailAliases as $alias) {
                self::assertEquals(0, $alias->dispatch_address, print_r($alias->toArray(), true));
            }
        }
    }

    public function testAddUserToSecondaryDomain()
    {
        $smtpConf = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
        if (empty($smtpConf->secondarydomains)) {
            self::markTestSkipped('only with configured secondary domain');
        }

        // create two users: one with email in primary and one with email in secondary domain
        $user1 = $this->testAddUser();
        $this->objects['users'][] = $user1;
        $user2 = TestCase::getTestUser([
            'accountLoginName'      => 'phpunitssecond',
            'accountEmailAddress'   => 'phpunitpostfix@' . $smtpConf->secondarydomains,
        ]);
        $user2->smtpUser = new Tinebase_Model_EmailUser(array(
            'emailAddress'     => $user2->accountEmailAddress,
            'emailForwardOnly' => false,
            'emailForwards'    => array(),
            'emailAliases'     => array(),
        ));
        $user2 = $this->_backend->addUser($user2);
        $this->objects['users'][] = $user2;

        $this->assertTrue($user2 instanceof Tinebase_Model_FullUser);
        $this->assertTrue(isset($user2->smtpUser), 'no smtpUser data found in ' . print_r($user2->toArray(),
                TRUE));

        // check dovecot users
        $imapConf = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP,
            new Tinebase_Config_Struct())->toArray();
        if (!isset($imapConf['backend']) || !('Imap_' . ucfirst($imapConf['backend']) == Tinebase_EmailUser::IMAP_DOVECOT) || $imapConf['active'] != true) {
            return;
        }
        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);

        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user1);
        $rawDovecotUser1 = $dovecot->getRawUserById($emailUser);
        self::assertEquals($user1->accountEmailAddress, $rawDovecotUser1['loginname']);

        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user2);
        $rawDovecotUser2 = $dovecot->getRawUserById($emailUser);
        self::assertEquals($user2->accountEmailAddress, $rawDovecotUser2['loginname']);
    }
}
