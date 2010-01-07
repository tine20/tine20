<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_PreferenceTest::main();
}

/**
 * Test class for Tinebase_PreferenceTest
 */
class Tinebase_PreferenceTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Preference
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_PreferenceTest');
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
        $this->_instance = Tinebase_Core::getPreference();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {      
    }
    
    /**
     * get default preference
     *
     */
    public function testGetDefaultPreference()
    {
        // delete default pref first
        $preferences = $this->_instance->getMultipleByProperty(Tinebase_Preference::TIMEZONE);
        foreach ($preferences as $preference) {
            if (
                $preference->type === Tinebase_Model_Preference::TYPE_DEFAULT 
                || (
                    $preference->account_id === Tinebase_Core::getUser()->getId()
                    && $preference->account_type === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
                )
            ) {
                $this->_instance->delete($preference);
            }
        }
        
        $prefValue = $this->_instance->getValue(Tinebase_Preference::TIMEZONE);
        
        $this->assertEquals('Europe/Berlin', $prefValue);

        // test get interceptor
        $prefValue = $this->_instance->{Tinebase_Preference::TIMEZONE};
        
        $this->assertEquals('Europe/Berlin', $prefValue);
        
        // restore preferences
        foreach ($preferences as $preference) {
            $this->_instance->create($preference);
        }
    }
    
    /**
     * test set timezone pref
     *
     */
    public function testSetPreference()
    {
        $newValue = 'Europe/Nicosia';
        $this->_instance->setValue(Tinebase_Preference::TIMEZONE, $newValue);

        $prefValue = $this->_instance->getValue(Tinebase_Preference::TIMEZONE);
        $this->assertEquals($newValue, $prefValue);
        
        // reset old default value (with set interceptor)
        $this->_instance->{Tinebase_Preference::TIMEZONE} = 'Europe/Berlin';
        $prefValue = $this->_instance->getValue(Tinebase_Preference::TIMEZONE);
        $this->assertEquals('Europe/Berlin', $prefValue);
    }

    /**
     * test get default value
     *
     */
    public function testGetDefaultPreferenceValue()
    {
        $defaultValue = 'Shangri-La';
        $prefValue = $this->_instance->getValue('SomeNonexistantPref', $defaultValue);
        
        $this->assertEquals($defaultValue, $prefValue);
    }
    
    /**
     * test forced preference
     *
     */
    public function testForcedPreference()
    {
        $forcedPrefName ='testForcedPref';
        $forcedPref = new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => $forcedPrefName,
            'value'             => 'forced value',
            'account_id'        => '0',
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_FORCED
        ));
        $forcedPref = $this->_instance->create($forcedPref);
        
        // set pref for user
        $this->_instance->testForcedPref = 'user value';
        
        $pref = $this->_instance->$forcedPrefName;
        
        $this->assertEquals($forcedPref->value, $pref);
        
        // cleanup
        $this->_instance->delete($forcedPref);
    }

    /**
     * test public only preference, try to force it -> should throw exception
     *
     */
    public function testPublicOnlyPreference()
    {
        $prefName ='testForcedPref';
        $pref = new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => $prefName,
            'value'             => 'value',
            'account_id'        => '0',
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_FORCED,
            'personal_only'     => TRUE
        ));
        
        // try to force pref
        $this->setExpectedException('Tinebase_Exception_UnexpectedValue');
        $pref = $this->_instance->create($pref);
    }
    
    /**
     * test get users with pref function
     *
     */
    public function testGetUsersWithPref()
    {
        $this->_instance->{Tinebase_Preference::TIMEZONE} = 'Europe/Nicosia';
        $userIds = $this->_instance->getUsersWithPref(Tinebase_Preference::TIMEZONE, 'Europe/Berlin');
        
        //print_r($userIds);
        
        $this->assertTrue(! in_array(Setup_Core::getUser()->getId(), $userIds), 'admin user should have other timezone setting');
        $this->assertGreaterThan(4, count($userIds), 'too few users found');
        
        $this->_instance->{Tinebase_Preference::TIMEZONE} = 'Europe/Berlin';
    }
    
    /******************** protected helper funcs ************************/
    
    /**
     * get preference filter
     *
     * @return Tinebase_Model_PreferenceFilter
     */
    protected function _getPreferenceFilter()
    {
        return new Tinebase_Model_PreferenceFilter(array(
            array(
                'field' => 'account', 
                'operator' => 'equals', 
                'value' => array(
                    'accountId'     => Tinebase_Core::getUser()->getId(),
                    'accountType'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
                )
            ),
            array(
                'field' => 'type', 
                'operator' => 'equals', 
                'value' => Tinebase_Model_Preference::TYPE_NORMAL
            )
        ));
    }
}
