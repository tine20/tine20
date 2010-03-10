<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        extend Tinebase_Server_Abstract to init framework
 */

     
/**
 * helper class
 *
 */
class TestServer extends Tinebase_Server_Abstract
{
    /**
     * holdes the instance of the singleton
     *
     * @var TestServer
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return TestServer
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new TestServer;
        }
        
        return self::$instance;
    }

    /**
     * init the test framework
     *
     */
    public function initFramework()
    {
        $this->_initFramework();
        $this->_initTestUsers();
        
        // set default internal encoding
        //ini_set('iconv.internal_encoding', 'utf-8');
        iconv_set_encoding("internal_encoding", "UTF-8");
    }
    
    /**
     * inits (adds) some test users
     *
     */
    protected function _initTestUsers() {
        $personas = array(
            'jsmith'   => 'John Smith',
            'sclever'  => 'Susan Clever',
            'pwulf'    => 'Paul Wulf',
            'jmcblack' => 'James McBlack',
            'rwright'  => 'Roberta Wright',
        );
        
        foreach ($personas as $login => $fullName) {
            try {
                $user = Tinebase_User::getInstance()->getFullUserByLoginName($login);
            } catch (Tinebase_Exception_NotFound $e) {
                list($given, $last) = explode(' ', $fullName);
                $group = Tinebase_Group::getInstance()->getGroupByName('Users')->getId();
                $user = new Tinebase_Model_FullUser(array(
                    'accountLoginName'      => $login,
                    'accountPrimaryGroup'   => $group,
                    'accountDisplayName'    => $fullName,
                    'accountLastName'       => $last,
                    'accountFirstName'      => $given,
                    'accountFullName'       => $fullName,
                    //'accountEmailAddress'   => $login . '@tine-publications.co.uk',
                    'accountEmailAddress'   => $login . '@tine20.org',
                ));
                $user = Tinebase_User::getInstance()->addUser($user);
                
                Tinebase_Group::getInstance()->addGroupMember($group, $user);
                
                // give additional testusers the same password as the primary test account
                Tinebase_User::getInstance()->setPassword($user, Zend_Registry::get('testConfig')->password);
            }
            $personas[$login] = $user;
        }
        Zend_Registry::set('personas', $personas);
        
    }
    
    /**
     * don't use that
     *
     */
    public function handle()
    {
        
    }
}
