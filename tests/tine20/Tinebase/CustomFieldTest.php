<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_CustomFieldTest::main();
}

/**
 * Test class for Tinebase_CustomField
 */
class Tinebase_CustomFieldTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_CustomField
     */
    protected $_instance;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_CustomFieldTest');
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_instance = Tinebase_CustomField::getInstance();
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
     * test add customfield to the same record
     * #7330: https://forge.tine20.org/mantisbt/view.php?id=7330
     */
    public function testAddSelfCustomField()
    {
        $cf = $this->_getCustomField(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'record', "recordConfig" => array("value" => array("records" => "Tine.Addressbook.Model.Contact")))
            ));

        $cf = $this->_instance->addCustomField($cf);
        
        $record = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array('n_family' => 'Clever', 'n_given' => 'Rupert')));
        $cfName = $cf->name;
        $record->customfields = array($cfName => $record->toArray());
        
        $this->setExpectedException('Tinebase_Exception_Record_Validation');
        $newRecord = Addressbook_Controller_Contact::getInstance()->update($record);
    }
    
    /**
     * test add customfield to the same record by multiple update
     * #7330: https://forge.tine20.org/mantisbt/view.php?id=7330
     */
    public function testAddSelfCustomFieldByMultipleUpdate()
    {
        $cf = $this->_getCustomField(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'record', "recordConfig" => array("value" => array("records" => "Tine.Addressbook.Model.Contact")))
            ));

        $cf = $this->_instance->addCustomField($cf);
        
        $record1 = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array('n_family' => 'Clever', 'n_given' => 'Rupert')));
        $record2 = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array('n_family' => 'Clever', 'n_given' => 'Matt')));
        
        $json = new Tinebase_Frontend_Json();
        
        $result = $json->updateMultipleRecords(
            'Addressbook',
            'Contact',
            array(array('name' => 'customfield_' . $cf->name, 'value' => $record1->getId())),
            array(array('field' => 'id', 'operator' => 'in', 'value' => array($record1->getId(), $record2->getId())))
        );
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals(1, $result['failcount']);
    }
    
    
    /**
     * test custom fields
     *
     * - add custom field
     * - get custom fields for app
     * - delete custom field
     */
    public function testCustomFields()
    {
        // create
        $customField = $this->_getCustomField();
        $createdCustomField = $this->_instance->addCustomField($customField);
        $this->assertEquals($customField->name, $createdCustomField->name);
        $this->assertNotNull($createdCustomField->getId());
        
        // fetch
        $application = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        $appCustomFields = $this->_instance->getCustomFieldsForApplication(
            $application->getId()
        );
        $this->assertGreaterThan(0, count($appCustomFields));
        $this->assertEquals($application->getId(), $appCustomFields[0]->application_id);

        // check with model name
        $appCustomFieldsWithModelName = $this->_instance->getCustomFieldsForApplication(
            $application->getId(),
            $customField->model
        );
        $this->assertGreaterThan(0, count($appCustomFieldsWithModelName));
        $this->assertEquals($customField->model, $appCustomFieldsWithModelName[0]->model, 'didn\'t get correct model name');
        
        // check if grants are returned
        $this->_instance->resolveConfigGrants($appCustomFields);
        $accountGrants = $appCustomFields->getFirstRecord()->account_grants;
        sort($accountGrants);
        $this->assertEquals(Tinebase_Model_CustomField_Grant::getAllGrants(), $accountGrants);
        
        // delete
        $this->_instance->deleteCustomField($createdCustomField);
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $this->_instance->getCustomField($createdCustomField->getId());
    }
    
    /**
     * test custom field acl
     *
     * - add custom field
     * - remove grants
     * - cf should no longer be returned
     */
    public function testCustomFieldAcl()
    {
        $createdCustomField = $this->_instance->addCustomField($this->_getCustomField());
        $this->_instance->setGrants($createdCustomField);
        
        $application = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        $appCustomFields = $this->_instance->getCustomFieldsForApplication(
            $application->getId()
        );
        
        $this->assertEquals(0, count($appCustomFields));
    }
    
    /**
     * get custom field record
     *
     * @param array $config 
     * @return Tinebase_Model_CustomField_Config
     */
    protected function _getCustomField($config = array())
    {
        return new Tinebase_Model_CustomField_Config(array_replace_recursive(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => Tinebase_Record_Abstract::generateUID(),
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )  
        ), $config));
    }
}
