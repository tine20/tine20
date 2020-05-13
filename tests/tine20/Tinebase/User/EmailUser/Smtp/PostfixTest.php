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
    protected function setUp()
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
    protected function tearDown()
    {
        foreach ($this->objects['users'] as $user) {
            $this->_backend->deleteUser($user);
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
        $this->assertTrue(in_array('unittest@' . $this->_mailDomain, $testUser->smtpUser->emailForwards),
            'forwards not found');
        $this->assertTrue(in_array('test@' . $this->_mailDomain, $testUser->smtpUser->emailForwards),
            'forwards not found');

        $expectedAliases = ['bla@' . $this->_mailDomain, 'blubb@' . $this->_mailDomain];
        $foundAliases = array_filter($testUser->smtpUser->emailAliases, function($alias) use ($expectedAliases) {
            return (in_array($alias['email'], $expectedAliases));
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
        $user->smtpUser->emailAliases = array('bla@' . $this->_mailDomain);
        $user->smtpUser->emailForwards = array();
        $user->accountEmailAddress = 'j.smith@' . $this->_mailDomain;

        $testUser = $this->_backend->updateUser($user);

        $this->assertEquals(array(), $testUser->smtpUser->emailForwards, 'forwards should be empty');
        $this->assertEquals([
            ['email' => 'bla@' . $this->_mailDomain, 'dispatch_address' => 1]
        ], $testUser->smtpUser->emailAliases, 'aliases mismatch');
        $this->assertEquals(false, $testUser->smtpUser->emailForwardOnly);
        $this->assertEquals('j.smith@' . $this->_mailDomain, $testUser->smtpUser->emailAddress);
        $this->assertEquals($testUser->smtpUser->emailAliases, $testUser->emailUser->emailAliases,
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
        $db = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP)->getDb();
        $select = $db->select()
            ->from(array('smtp_destinations'))
            ->where($db->quoteIdentifier('userid') . ' = ?', $user->xprops()[Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_SMTP]);
        $stmt = $db->query($select);
        $queryResult = $stmt->fetchAll();
        $stmt->closeCursor();

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
            $aliases[] = 'alias_blablablablablablablablalbalbbl' . $i . '@' . $this->_mailDomain;
        }
        $user->smtpUser->emailAliases = $aliases;
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
        $aliases = $user->emailUser->emailAliases;
        $aliases[] = [
            'email' => $user->accountEmailAddress,
            'dispatch_address' => 1,
        ];
        $user->smtpUser->emailAliases = $aliases;
        try {
            $updatedUser = $this->_backend->updateUser($user);
            self::fail('alias should not be allowed to equal email address' . print_r($updatedUser->toArray(), true));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
        }

        $user->accountEmailAddress = $user->emailUser->emailAliases[0]['email'];
        try {
            $updatedUser = $this->_backend->updateUser($user);
            self::fail('alias should not be allowed to equal email address' . print_r($updatedUser->toArray(), true));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
        }
    }

    public function testAliasesDispatchAddressFlag()
    {
        $user = $this->testAddUser();
        $aliases = $user->emailUser->emailAliases;
        foreach ([0, '0', false] as $inputvalue) {
            foreach ($aliases as &$alias) {
                $alias['dispatch_address'] = $inputvalue;
            }
            $user->smtpUser->emailAliases = $aliases;
            $updatedUser = $this->_backend->updateUser($user);
            foreach ($updatedUser->emailUser->emailAliases as $alias) {
                self::assertEquals(0, $alias['dispatch_address']);
            }
        }
    }
}
