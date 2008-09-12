<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Json
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_JsonTest::main();
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Json
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_JsonTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * set up tests
     *
     */
    public function setUp()
    {
        $this->_instance = new Tinebase_Json();
        
        $this->_objects['record'] = array(
            'id'        => 1,
            'model'     => 'Addressbook_Model_Contact',
            'backend'    => 'Sql',
        );        

        $this->_objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',    
            'record_model'      => $this->_objects['record']['model'],
            'record_backend'    => $this->_objects['record']['backend'],       
            'record_id'         => $this->_objects['record']['id']
        ));        
    }
    
    /**
     * try to add a note type
     *
     */
    public function testSearchNotes()
    {
        Tinebase_Notes::getInstance()->addNote($this->_objects['note']);

        $filter = array(array(
            'field' => 'query',
            'operator' => '',
            'value' => 'phpunit'
        ));
        $paging = array();
        
        $notes = $this->_instance->searchNotes(Zend_Json::encode($filter), Zend_Json::encode($paging));
        
        $this->assertGreaterThan(0, $notes['totalcount']);        
        $this->assertEquals($this->_objects['note']->note, $notes['results'][0]['note']);
        
        // delete note
        Tinebase_Notes::getInstance()->deleteNotesOfRecord(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );        
    }
    
    /**
     * test getCountryList
     *
     */
    public function testGetCountryList()
    {
        $list = $this->_instance->getCountryList();
        $this->assertTrue(count($list['results']) >= 263);
    }
    
    /**
     * test get translations
     *
     */
    public function testGetAvailableTranslations()
    {
        $list = $this->_instance->getAvailableTranslations();
        $this->assertTrue(count($list['results']) > 3);
    }
    
    /**
     * test set locale and save it in db
     */
    public function testSetLocaleAsPreference()
    {
        $userId = Zend_Registry::get('currentAccount')->getId();
        $oldPreference = Tinebase_Config::getInstance()->getPreference($userId, 'Locale');
        
        $locale = 'de_DE';
        $result = $this->_instance->setLocale($locale, true);
        
        //print_r($result);
        $this->assertEquals($locale, $result['locale']['locale']);
        
        // get config setting from db
        $preference = Tinebase_Config::getInstance()->getPreference($userId, 'Locale');
        $this->assertEquals($locale, $preference->value, "didn't get right locale preference");
        
        // restore old setting
        Tinebase_Config::getInstance()->setPreference($userId, $oldPreference);
    }

    /**
     * test get timezones
     *
     */
    public function testGetAvailableTimezones()
    {
        $list = $this->_instance->getAvailableTimezones();        
        $this->assertTrue(count($list['results']) > 85);
    }
    
    /**
     * test set timezone and save it in db
     */
    public function testSetTimezoneAsPreference()
    {
        $userId = Zend_Registry::get('currentAccount')->getId();
        $oldPreference = Tinebase_Config::getInstance()->getPreference($userId, 'Timezone');
        
        $timezone = 'America/Vancouver';
        $result = $this->_instance->setTimezone($timezone, true);        
        //print_r($result);
        
        // check json result
        $this->assertEquals($timezone, $result);
        
        // get config setting from db
        $preference = Tinebase_Config::getInstance()->getPreference($userId, 'Timezone');
        $this->assertEquals($timezone, $preference->value, "didn't get right timezone preference");
        
        // restore old setting
        Tinebase_Config::getInstance()->setPreference($userId, $oldPreference);
    }
}

