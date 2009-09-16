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
    define('PHPUnit_MAIN_METHOD', 'Tinebase_User_EmailUser_ImapAbstractTest::main');
}

/**
 * Test class for Tinebase_EmailUser_Imap_Dbmail
 */
class Tinebase_User_EmailUser_ImapAbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * email user backend
     *
     * @var Tinebase_EmailUser_Abstract
     */
    protected $_backend = NULL;
        
    /**
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_User_EmailUser_ImapAbstractTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }


    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // delete email account
        $this->_backend->deleteUser(Tinebase_Core::getUser()->getId());
    }
    
    /**
     * try to update an email account
     * 
     */
    public function testUpdateAccount()
    {
        // update user
        $this->_objects['addedUser']->emailMailQuota = 2000000;
        
        $updatedUser = $this->_backend->updateUser(Tinebase_Core::getUser(), $this->_objects['addedUser']);
        
        $this->assertEquals(2000000, $updatedUser->emailMailQuota);
    }
    
    /**
     * add new email user
     * 
     * @return Tinebase_Model_EmailUser
     */
    protected function _addUser($_user = NULL)
    {
        $user = ($_user !== NULL) ? $_user : Tinebase_Core::getUser();
        $emailUser = new Tinebase_Model_EmailUser(array(
            'emailMailQuota'    => 1000000
        ));
        $addedUser = $this->_backend->addUser($user, $emailUser);
        
        return $addedUser;
    }
}	
