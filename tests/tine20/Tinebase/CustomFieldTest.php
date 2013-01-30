<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
     * transaction id if test is wrapped in an transaction
     */
    protected $_transactionId = NULL;
    
    protected $_user = NULL;
    
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
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
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
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
        
        if ($this->_user) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_user);
        }
    }
    
    /**
     * test add customfield to the same record
     * #7330: https://forge.tine20.org/mantisbt/view.php?id=7330
     */
    public function testAddSelfCustomField()
    {
        $cf = self::getCustomField(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'record', "recordConfig" => array("value" => array("records" => "Tine.Addressbook.Model.Contact")))
            ));

        $cf = $this->_instance->addCustomField($cf);
        
        $record = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array('n_family' => 'Clever', 'n_given' => 'Ben')));
        $cfName = $cf->name;
        $record->customfields = array($cfName => $record->toArray());
        
        $this->setExpectedException('Tinebase_Exception_Record_Validation');
        $newRecord = Addressbook_Controller_Contact::getInstance()->update($record);
    }
    
    /**
     * test add customfield to the same record by multiple update
     * 
     * @see #7330: https://forge.tine20.org/mantisbt/view.php?id=7330
     * @see 0007350: multipleUpdate - record not found
     */
    public function testAddSelfCustomFieldByMultipleUpdate()
    {
        // test needs transaction because Controller does rollback when exception is thrown
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = NULL;
        
        $cf = self::getCustomField(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'record', "recordConfig" => array("value" => array("records" => "Tine.Addressbook.Model.Contact")))
        ));

        $cf = $this->_instance->addCustomField($cf);
        $c = Addressbook_Controller_Contact::getInstance();

        $record1 = $c->create(new Addressbook_Model_Contact(array('n_family' => 'Friendly', 'n_given' => 'Rupert')), false);
        $record2 = $c->create(new Addressbook_Model_Contact(array('n_family' => 'Friendly', 'n_given' => 'Matt')), false);
        $contactIds = array($record1->getId(), $record2->getId());
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Friendly')
            ), 'AND');

        $result = $c->updateMultiple($filter, array('#' . $cf->name => $contactIds[0]));
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals(1, $result['failcount']);
        
        // cleanup required because we do not have the tearDown() rollback here
        $this->_instance->deleteCustomField($cf);
        Addressbook_Controller_Contact::getInstance()->delete($contactIds);
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
        $customField = self::getCustomField();
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
        $createdCustomField = $this->_instance->addCustomField(self::getCustomField());
        $this->_instance->setGrants($createdCustomField);
        
        $application = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        $appCustomFields = $this->_instance->getCustomFieldsForApplication(
            $application->getId()
        );
        
        $this->assertEquals(0, count($appCustomFields));
    }
    
    /**
     * testAddressbookCustomFieldAcl
     * 
     * @see 0007630: Customfield read access to all users
     */
    public function testAddressbookCustomFieldAcl()
    {
        $createdCustomField = $this->_instance->addCustomField(self::getCustomField(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_Contact',
        )));
        $anotherCustomField = $this->_instance->addCustomField(self::getCustomField(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_Contact',
        )));
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family'     => 'testcontact',
            'container_id' => Tinebase_Container::getInstance()->getSharedContainer(
                Tinebase_Core::getUser(), 'Addressbook', Tinebase_Model_Grants::GRANT_READ)->getFirstRecord()->getId()
        )));
        $cfValue = array(
            $createdCustomField->name => 'test value',
            $anotherCustomField->name => 'test value 2'
        );
        $contact->customfields = $cfValue;
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);
        $this->assertEquals($cfValue, $contact->customfields, 'cf not saved: ' . print_r($contact->toArray(), TRUE));
        
        // create group and only give acl to this group
        $group = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $this->_instance->setGrants($createdCustomField, array(
            Tinebase_Model_CustomField_Grant::GRANT_READ,
            Tinebase_Model_CustomField_Grant::GRANT_WRITE,
        ), Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $group->getId());
        $contact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        $this->assertEquals(2, count($contact->customfields));
        
        // change user and check cfs
        $this->_user = Tinebase_Core::getUser();
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        Tinebase_Core::set(Tinebase_Core::USER, $sclever);
        $contact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        $this->assertEquals(array($anotherCustomField->name => 'test value 2'), $contact->customfields, 'cf should be hidden: ' . print_r($contact->customfields, TRUE));
    }
    
    /**
     * get custom field record
     *
     * @param array $config 
     * @return Tinebase_Model_CustomField_Config
     */
    public static function getCustomField($config = array())
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
    
    /**
     * test searching records by date as a customfield type
     * https://forge.tine20.org/mantisbt/view.php?id=6730
     */
    public function testSearchByDate()
    {
        $date = new Tinebase_DateTime();
        $cf = self::getCustomField(array('application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(), 'model' => 'Addressbook_Model_Contact', 'definition' => array('type' => 'date')));
        $this->_instance->addCustomField($cf);
        
        $contact = new Addressbook_Model_Contact(array('n_given' => 'Rita', 'n_family' => 'Blütenrein'));
        $contact->customfields = array($cf->name => $date);
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact, false);
        
        $json = new Addressbook_Frontend_Json();
        $result = $json->searchContacts(array(
            array("condition" => "OR",
                "filters" => array(array("condition" => "AND", 
                    "filters" => array(
                        array("field" => "customfield", "operator" => "within", "value" => array("cfId" => $cf->getId(), "value" => "weekThis")),
                        )
                ))
            )
        ), array());
        
        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals('Rita', $result['results'][0]['n_given']);
        
        $json->deleteContacts(array($contact->getId()));
        
        $this->_instance->deleteCustomField($cf);
    }
    
    /**
     * test searching records by bool as a customfield type
     * https://forge.tine20.org/mantisbt/view.php?id=6730
     */
    public function testSearchByBool()
    {
        $cf = self::getCustomField(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'bool')
        ));
        $this->_instance->addCustomField($cf);
        
        // contact1 with customfield bool = true
        $contact1 = new Addressbook_Model_Contact(array('n_given' => 'Rita', 'n_family' => 'Blütenrein'));
        $contact1->customfields = array($cf->name => true);
        $contact1 = Addressbook_Controller_Contact::getInstance()->create($contact1, false);
        
        // contact2 with customfield bool is not set -> should act like set to false
        $contact2 = new Addressbook_Model_Contact(array('n_given' => 'Rainer', 'n_family' => 'Blütenrein'));
        $contact2 = Addressbook_Controller_Contact::getInstance()->create($contact2, false);
        
        // test bool = true
        $json = new Addressbook_Frontend_Json();
        $result = $json->searchContacts(array(
            array("condition" => "OR",
                "filters" => array(array("condition" => "AND", 
                    "filters" => array(
                        array("field" => "customfield", "operator" => "equals", "value" => array("cfId" => $cf->getId(), "value" => true)),
                        array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Blütenrein')
                    )
                ))
            )
        ), array());
        
        // test bool = false
        $this->assertEquals(1, $result['totalcount'], 'One Record should have been found where cf-bool = true (Rita Blütenrein)');
        $this->assertEquals('Rita', $result['results'][0]['n_given'], 'The Record should be Rita Blütenrein');
        
        $result = $json->searchContacts(array(
            array("condition" => "OR",
                "filters" => array(array("condition" => "AND", 
                    "filters" => array(
                        array("field" => "customfield", "operator" => "equals", "value" => array("cfId" => $cf->getId(), "value" => false)),
                        array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Blütenrein')
                    )
                ))
            )
        ), array());
        
        $this->assertEquals(1, $result['totalcount'], 'One Record should have been found where cf-bool is not set (Rainer Blütenrein)');
        $this->assertEquals('Rainer', $result['results'][0]['n_given'], 'The Record should be Rainer Blütenrein');
    }
    
    /**
     * test searching records by record as a customfield type
     * https://forge.tine20.org/mantisbt/view.php?id=6730
     */
    public function testSearchByRecord()
    {
        $cf = self::getCustomField(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'record', "recordConfig" => array("value" => array("records" => "Tine.Sales.Model.Contract")))
        ));
        $this->_instance->addCustomField($cf);
        
        $contract = Sales_Controller_Contract::getInstance()->create(
            new Sales_Model_Contract(
                array(
                    'number' => Tinebase_Record_Abstract::generateUID(10),
                    'title' => Tinebase_Record_Abstract::generateUID(10),
                    'container_id' => Tinebase_Container::getInstance()->getDefaultContainer('Sales_Model_Contract')->getId()
                )
            )
        );
        
        // contact1 with customfield record = contract
        $contact1 = new Addressbook_Model_Contact(array('n_given' => 'Rita', 'n_family' => 'Blütenrein'));
        $contact1->customfields = array($cf->name => $contract->getId());
        $contact1 = Addressbook_Controller_Contact::getInstance()->create($contact1, false);
        
        // contact2 with customfield record is not set -> should act like without this record
        $contact2 = new Addressbook_Model_Contact(array('n_given' => 'Rainer', 'n_family' => 'Blütenrein'));
        $contact2 = Addressbook_Controller_Contact::getInstance()->create($contact2, false);
        
        $json = new Addressbook_Frontend_Json();
        
        $result = $json->searchContacts(array(
            array("condition" => "OR",
                "filters" => array(array("condition" => "AND", 
                    "filters" => array(
                        array("field" => "customfield", "operator" => "equals", "value" => array("cfId" => $cf->getId(), "value" => $contract->getId())),
                    )
                ))
            )
        ), array());
        
        $this->assertEquals(1, $result['totalcount'], 'One Record should have been found where cf-record = contract (Rita Blütenrein)');
        $this->assertEquals('Rita', $result['results'][0]['n_given'], 'The Record should be Rita Blütenrein');
        
        $result = $json->searchContacts(array(
            array("condition" => "OR",
                "filters" => array(array("condition" => "AND", 
                    "filters" => array(
                        array("field" => "customfield", "operator" => "not", "value" => array("cfId" => $cf->getId(), "value" => $contract->getId())),
                        array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Blütenrein')
                    )
                ))
            )
        ), array());
        
        $this->assertEquals(1, $result['totalcount'], 'One Record should have been found where cf-record is not set (Rainer Blütenrein)');
        $this->assertEquals('Rainer', $result['results'][0]['n_given'], 'The Record should be Rainer Blütenrein');
    }
}
