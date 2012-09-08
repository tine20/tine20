<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_PostfixTest
 */
class Tinebase_User_EmailUser_Smtp_PostfixTest extends PHPUnit_Framework_TestCase
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
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_EmailUser_Smtp_PostfixTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_backend = Tinebase_User::getInstance();
        
        if (! array_key_exists('Tinebase_EmailUser_Smtp_Postfix', $this->_backend->getPlugins())) {
            $this->markTestSkipped('Postfix SQL plugin not enabled');
        }
        
        $this->objects['users'] = array();
        
        $config = TestServer::getInstance()->getConfig();
        if ($config->maildomain) {
            $this->_mailDomain = $config->maildomain;
        }
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
    }
    
    /**
     * try to add an user
     * 
     * @return Tinebase_Model_FullUser
     */
    public function testAddUser()
    {
        $user = Tinebase_User_LdapTest::getTestRecord();
        $user->smtpUser = new Tinebase_Model_EmailUser(array(
            'emailAddress'     => $user->accountEmailAddress,
            'emailForwardOnly' => true,
            'emailForwards'    => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
            'emailAliases'     => array('bla@' . $this->_mailDomain, 'blubb@' . $this->_mailDomain)
        ));
        
        $testUser = $this->_backend->addUser($user);
        $this->objects['users']['testUser'] = $testUser;

        $this->assertTrue($testUser instanceof Tinebase_Model_FullUser);
        $this->assertTrue(isset($testUser->smtpUser), 'no smtpUser data found in ' . print_r($testUser->toArray(), TRUE));
        $this->assertEquals(array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain), $testUser->smtpUser->emailForwards, 'forwards not found');
        $this->assertEquals(array('bla@' . $this->_mailDomain, 'blubb@' . $this->_mailDomain),     $testUser->smtpUser->emailAliases, 'aliases not found');
        $this->assertEquals(true,                                            $testUser->smtpUser->emailForwardOnly);
        $this->assertEquals($user->accountEmailAddress,                      $testUser->smtpUser->emailAddress);
        
        return $testUser;
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
        
        $this->assertEquals(array(),                            $testUser->smtpUser->emailForwards, 'forwards mismatch');
        $this->assertEquals(array('bla@' . $this->_mailDomain), $testUser->smtpUser->emailAliases,  'aliases mismatch');
        $this->assertEquals(false,                              $testUser->smtpUser->emailForwardOnly);
        $this->assertEquals('j.smith@' . $this->_mailDomain,    $testUser->smtpUser->emailAddress);
        $this->assertEquals($testUser->smtpUser->emailAliases,  $testUser->emailUser->emailAliases,
            'smtp user data needs to be merged in email user: ' . print_r($testUser->emailUser->toArray(), TRUE));
    }
    
    /**
     * try to enable an account
     *
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
            ->where($db->quoteIdentifier('userid') . ' = ?', $user->getId());
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
        $user = $this->testAddUser();
        
        // check destinations
        $db = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP)->getDb();
        $select = $db->select()
            ->from(array('smtp_destinations'))
            ->where($db->quoteIdentifier('userid') . ' = ?', $user->getId());
        $stmt = $db->query($select);
        $queryResult = $stmt->fetchAll();
        $stmt->closeCursor();
        
        $this->assertEquals(6, count($queryResult), print_r($queryResult, TRUE));
        $expectedDestinations = array(
            'bla@' . $this->_mailDomain => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
            'blubb@' . $this->_mailDomain => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
            'phpunit@' . $this->_mailDomain => array('unittest@' . $this->_mailDomain, 'test@' . $this->_mailDomain),
        );
        foreach ($expectedDestinations as $source => $destinations) {
            $foundDestinations = array();
            foreach ($queryResult as $row) {
                if ($row['source'] === $source) {
                    $foundDestinations[] = $row['destination'];
                }
            }
            $this->assertEquals(2, count($foundDestinations));
            $this->assertTrue($foundDestinations == $destinations, print_r($destinations, TRUE));
        }
    }
}
