<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_PreferenceTest
 */
class Tinebase_PreferenceTest extends TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Preference
     */
    protected $_instance;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_instance = Tinebase_Core::getPreference();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }
    
    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
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
     * @todo use existant pref
     */
    public function testForcedPreference()
    {
        $forcedPrefName ='locale';
        $forcedPref = $this->_createTestPreference($forcedPrefName);
        
        // set pref for user
        $this->_instance->testForcedPref = 'user value';
        
        $pref = $this->_instance->$forcedPrefName;
        
        $this->assertEquals($forcedPref->value, $pref);
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
        
        $this->assertTrue(! in_array(Setup_Core::getUser()->getId(), $userIds), 'admin user should have other timezone setting');
        $this->assertGreaterThan(4, count($userIds), 'too few users found');
        
        $this->_instance->{Tinebase_Preference::TIMEZONE} = 'Europe/Berlin';
    }
    
    /**
     * test search for preferences and check if preference that is not defined is removed from result
     */
    public function testSearchPreferences()
    {
        $testPref = $this->_createTestPreference('testPref');
        
        $result = $this->_instance->search($this->_getPreferenceFilter(Tinebase_Model_Preference::TYPE_FORCED));
        
        $this->assertTrue(count($result) == 0);
    }
    
    /**
     * test search for preferences for anyone of calendar
     * 
     * @see 0005298: wrong default favorite in admin pref panel
     */
    public function testSearchCalendarPreferencesForAnyone()
    {
        $tasksPersistentFilter = Tinebase_PersistentFilter::getInstance()->getPreferenceValues('Tasks');
        $this->assertTrue(isset($tasksPersistentFilter[5]), 'could not find tasks persistent filter pref: ' . print_r($tasksPersistentFilter, true));
        
        $json = new Tinebase_Frontend_Json();
        $filter = $this->_getPreferenceFilter(NULL, Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        $result = $json->searchPreferencesForApplication('Tasks', $filter->toArray());
        $prefData = $result['results'];
        $prefToSave = array();
        foreach ($prefData as $pref) {
            if ($pref['name'] === Tasks_Preference::DEFAULTPERSISTENTFILTER) {
                $prefToSave[$pref['id']] = array(
                    'name'  => Tasks_Preference::DEFAULTPERSISTENTFILTER,
                    'value' => $tasksPersistentFilter[5][0],
                    'type'  => Tinebase_Model_Preference::TYPE_ADMIN,
                );
            }
        }
        Tinebase_Core::getPreference('Tasks')->saveAdminPreferences($prefToSave);

        $result = $json->searchPreferencesForApplication('Calendar', $filter->toArray());
        
        $this->assertGreaterThan(0, $result['totalcount']);
        
        $filterPref = NULL;
        foreach ($result['results'] as $pref) {
            if ($pref['name'] === Calendar_Preference::DEFAULTPERSISTENTFILTER) {
                $filterPref = $pref;
            }
        }
        $this->assertTrue($filterPref !== NULL);
        $this->assertEquals(Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(), $filterPref['application_id'], print_r($filterPref, TRUE));
    }
    
    /**
     * testDeleteAdminPrefByChangingUserPref
     * 
     * @see 0008052: Change in user preference alters admin preferences
     */
    public function testDeleteAdminPrefByChangingUserPref()
    {
        $defaultValue = 'Europe/Nicosia';
        $defaultPref = $this->_createTestPreference(Tinebase_Preference::TIMEZONE, 'Europe/Nicosia', Tinebase_Model_Preference::TYPE_DEFAULT);

        $this->_instance->{Tinebase_Preference::TIMEZONE} = 'Europe/Berlin';
        
        $defaultPref = $this->_instance->get($defaultPref->getId());
        $this->assertEquals($defaultValue, $defaultPref->value, 'defaultpref value overwritten: ' . print_r($defaultPref->toArray(), TRUE));
    }
    
    /**
     * testSetForcedDefaultPref
     * 
     * @see 0009606: preferences: forcing current default value is not working
     */
    public function testSetForcedDefaultPref()
    {
        $defaultPref = $this->_instance->getApplicationPreferenceDefaults(Tinebase_Preference::TIMEZONE);
        $this->_instance->saveAdminPreferences(array(
            $defaultPref->getId() => array(
                'type'  => Tinebase_Model_Preference::TYPE_FORCED,
                'value' => 0,
                'name'  => Tinebase_Preference::TIMEZONE
            )
        ));
        
        $result = $this->_instance->search();
        $pref = $result->filter('name', Tinebase_Preference::TIMEZONE)->getFirstRecord();
        
        $this->assertTrue($pref !== null);
        $this->assertEquals(Tinebase_Model_Preference::TYPE_FORCED, $pref->type);
        $this->assertEquals(0, $pref->value);
    }

    /**
     * testSetForcedDefaultPref
     */
    public function testSetForcedDefaultPrefDefaultValue()
    {
        $defaultPref = (new Filemanager_Preference())->getApplicationPreferenceDefaults(
            Filemanager_Preference::DB_CLICK_ACTION);
        (new Tinebase_Frontend_Json())->savePreferences(['Filemanager' => [
            $defaultPref->getId() => array(
                'type'  => Tinebase_Model_Preference::TYPE_FORCED,
                'value' => Tinebase_Model_Preference::DEFAULT_VALUE,
                'name'  => Filemanager_Preference::DB_CLICK_ACTION
            )
        ]], 1);

        $appPrefs = Tinebase_Core::getPreference('Filemanager');
        static::assertSame('download', $appPrefs->{Filemanager_Preference::DB_CLICK_ACTION});
    }

    /**
     * testSetLockedPref
     *
     * @see 0011178: allow to lock preferences for individual users
     */
    public function testSetLockedPref()
    {
        $tzPref = $this->_instance->getApplicationPreferenceDefaults(Tinebase_Preference::TIMEZONE);
        $tzPref->type = Tinebase_Model_Preference::TYPE_USER;
        $tzPref->locked = true;
        $tzPref->id = null;
        $tzPref->account_id = Tinebase_Core::getUser()->getId();
        $tzPref->account_type = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
        $tzPref = $this->_instance->create($tzPref);

        $result = $this->_instance->search();
        $pref = $result->filter('name', Tinebase_Preference::TIMEZONE)->getFirstRecord();

        $this->assertTrue($pref !== null);
        $this->assertEquals(Tinebase_Model_Preference::TYPE_USER, $pref->type);
        $this->assertEquals(true, $pref->locked);

        try {
            $this->_instance->setValue(Tinebase_Preference::TIMEZONE, 'Europe/Berlin');
            $this->fail('it is not allowed to set locked preference: ' . print_r($tzPref->toArray(), true));
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->assertEquals('You are not allowed to change the locked preference.', $tead->getMessage());
        }
    }

    /**
     * @see 0013214: allow to set fixed calendars as user preference
     */
    public function testSetFixedCalendarsPreference()
    {
        $personalContainer = $this->_getPersonalContainer(Calendar_Model_Event::class);
        $data =
          array (
            'Calendar' =>
            array (
              'fixedCalendars' =>
              array (
                'value' =>
                array (
                  0 =>
                      $personalContainer->toArray()
                ),
              ),
            ),
        );

        $frontend = new Tinebase_Frontend_Json();
        $result = $frontend->savePreferences($data, /* adminMode */ false);
        self::assertEquals('success', $result['status']);

        $preferences = $frontend->searchPreferencesForApplication('Calendar', array());
        $fixedCalendarsPref = false;
        foreach ($preferences['results'] as $preference) {
            if ($preference['name'] == 'fixedCalendars') {
                $fixedCalendarsPref = $preference;
            }
        }
        self::assertTrue(is_array($fixedCalendarsPref), print_r($preferences['results'], true));
        self::assertEquals(1, count($fixedCalendarsPref['value']), 'did not find personal container in value: '
            . print_r($fixedCalendarsPref, true));
        $container = $fixedCalendarsPref['value'][0];
        self::assertTrue(is_array($container), 'container not resolved: ' . print_r($fixedCalendarsPref, true));
        self::assertEquals($personalContainer->getId(), $container['id']);
        self::assertTrue(isset($fixedCalendarsPref['uiconfig']), 'uiconfig missing: '
            . print_r($fixedCalendarsPref, true));
        $fixedCalendarIds = Calendar_Controller_Event::getInstance()->getFixedCalendarIds();
        self::assertTrue(in_array($personalContainer->getId(), $fixedCalendarIds));
    }

    /******************** protected helper funcs ************************/
    
    /**
     * get preference filter
     *
     * @param string $type
     * @param string $accountType
     * @return Tinebase_Model_PreferenceFilter
     */
    protected function _getPreferenceFilter($type = Tinebase_Model_Preference::TYPE_USER, $accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $filterData = array(
            array(
                'field' => 'account', 
                'operator' => 'equals', 
                'value' => array(
                    'accountId'     => ($accountType === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) ? Tinebase_Core::getUser()->getId() : 0,
                    'accountType'   => $accountType
                )
            ),
        );
        
        if ($type !== NULL) {
            $filterData[] = array(
                'field' => 'type', 
                'operator' => 'equals', 
                'value' => $type
            );
        }
        
        return new Tinebase_Model_PreferenceFilter($filterData);
    }
    
    /**
     * create test preference
     * 
     * @param string $prefName
     * @param string $value,
     * @param string $type
     * @return Tinebase_Model_Preference
     */
    protected function _createTestPreference($prefName, $value = 'forced value', $type = Tinebase_Model_Preference::TYPE_FORCED)
    {
        $pref = new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => $prefName,
            'value'             => $value,
            'account_id'        => '0',
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => $type
        ));
        $pref = $this->_instance->create($pref);
        
        return $pref;
    }
}
