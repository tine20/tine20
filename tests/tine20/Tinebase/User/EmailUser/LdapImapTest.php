<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_EmailUser_LdapImapTest::main');
}

/**
 * Test class for Tinebase_EmailUser_Imap_Ldap
 */
class Tinebase_User_EmailUser_LdapImapTest extends Tinebase_User_EmailUser_ImapAbstractTest
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_EmailUser_LdapImapTest');
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
        $groups = Tinebase_Group::getInstance()->getGroups();
        
        $this->_backend = Tinebase_EmailUser::factory(Tinebase_EmailUser::LDAP_IMAP);
        
        try {
            $this->_objects['ldapUser'] = Tinebase_User::getInstance()->getFullUserByLoginName('tine20phpunit');
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->_objects['ldapUser'] = Tinebase_User::getInstance()->addUser(new Tinebase_Model_FullUser(array(
                'accountLoginName'      => 'tine20phpunit',
                'accountStatus'         => 'enabled',
                'accountExpires'        => NULL,
                'accountPrimaryGroup'   => $groups[0]->id,
                'accountLastName'       => 'Tine 2.0',
                'accountFirstName'      => 'PHPUnit',
                'accountEmailAddress'   => 'phpunit@metaways.de'
            )));
        }
        
        $this->_objects['addedUser'] = $this->_addUser($this->_objects['ldapUser']);
    }
    
    /**
     * try to add an email account
     *
     */
    public function testAddEmailAccount()
    {
        $this->assertEquals(array(
            'emailUID'          => $this->_objects['ldapUser']->accountLoginName,
            'emailGID'          => 1208394888,
            'emailMailQuota'    => 1000000,
            'emailForwardOnly'  => 0,
        ), $this->_objects['addedUser']->toArray());
    }
    
   /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        // delete ldap account
        Tinebase_User::getInstance()->deleteUser($this->_objects['ldapUser']->getId());
    }
}
