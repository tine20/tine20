<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_CustomField
 */
class Tinebase_CustomFieldTest extends TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_CustomField
     */
    protected $_instance;

    /**
     * @var Tinebase_Model_CustomField_Config
     */
    protected $_testCustomField = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_instance = Tinebase_CustomField::getInstance();
        
        Sales_Controller_Contract::getInstance()->setNumberPrefix();
        Sales_Controller_Contract::getInstance()->setNumberZerofill();
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
        
        $record = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array('n_family' => 'Cleverer', 'n_given' => 'Ben')));
        $cfName = $cf->name;
        $record->customfields = array($cfName => $record->toArray());
        
        $this->setExpectedException('Tinebase_Exception_Record_Validation');
        Addressbook_Controller_Contact::getInstance()->update($record);
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
     *
     * @todo test write grant
     */
    public function testAddressbookCustomFieldAcl($setViaCli = false)
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
                Tinebase_Core::getUser(), Addressbook_Model_Contact::class, Tinebase_Model_Grants::GRANT_READ)->getFirstRecord()->getId()
        )));
        $cfValue = array(
            $createdCustomField->name => 'test value',
            $anotherCustomField->name => 'test value 2'
        );
        $contact->customfields = $cfValue;
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);
        self::assertEquals($cfValue, $contact->customfields, 'cf not saved: ' . print_r($contact->toArray(), TRUE));
        
        // create group and only give acl to this group
        $group = Tinebase_Group::getInstance()->getDefaultAdminGroup();

        if ($setViaCli) {
            $result = $this->_appCliHelper('Tinebase', 'setCustomfieldAcl', [
                '--',
                'application=Addressbook',
                'model=Addressbook_Model_Contact',
                'name=' . $createdCustomField->name . '',
                'grants=[{"account":"' . $group->name . '","account_type":"group","readGrant":1,"writeGrant":1},{"account":"'
                    . Tinebase_Core::getUser()->accountLoginName . '","writeGrant":1}]'
            ]);
            self::assertEquals("", $result);
        } else {
            $this->_instance->setGrants($createdCustomField, array(
                Tinebase_Model_CustomField_Grant::GRANT_READ,
                Tinebase_Model_CustomField_Grant::GRANT_WRITE,
            ), Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $group->getId());
        }

        $contact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        self::assertEquals(2, count($contact->customfields));
        
        // change user and check cfs
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        Tinebase_Core::set(Tinebase_Core::USER, $sclever);
        $contact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        self::assertEquals(array($anotherCustomField->name => 'test value 2'), $contact->customfields, 'cf should be hidden: ' . print_r($contact->customfields, TRUE));
    }

    /**
     * testAddressbookCustomFieldAclViaCli
     */
    public function testAddressbookCustomFieldAclViaCli()
    {
        $this->testAddressbookCustomFieldAcl(true);
    }

    /**
     * testMultiRecordCustomField
     */
    public function testRecordCustomField()
    {
        $this->_testCustomField = $this->_instance->addCustomField(self::getCustomField(array(
            'name' => 'test',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'record', "recordConfig" => array("value" => array("records" => "Tine.Addressbook.Model.Contact")))
        )));

        //Customfield record 1
        $contact1 = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'org_name'     => 'contact 1'
        )));

        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family'     => 'contact'
        )));

        $cfValue = array($this->_testCustomField->name => $contact1->getId());
        $contact->customfields = $cfValue;
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);

        $filtersToTest = [
            ['operator' => 'equals', 'value' => $contact1->getId(), 'expectContactToBeFound' => true],
            ['operator' => 'equals', 'value' => '',                 'expectContactToBeFound' => false],
            ['operator' => 'equals', 'value' => null,               'expectContactToBeFound' => false],
            ['operator' => 'AND',    'value' => [],                 'expectContactToBeFound' => false],
        ];

        foreach ($filtersToTest as $filterToTest) {
            $result = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter([
                ['field' => 'customfield', 'operator' => $filterToTest['operator'], 'value' => [
                    'cfId' => $this->_testCustomField->getId(),
                    'value' => $filterToTest['value']
                ]]
            ]));
            if ($filterToTest['expectContactToBeFound']) {
                static::assertEquals(1, $result->count(), 'contact not found with filter '
                    . print_r($filterToTest, true)
                    . ' cf value: ' . $contact1->getId()
                );
                static::assertTrue(in_array($contact->getId(), $result->getArrayOfIds()));
            } else {
                static::assertFalse(in_array($contact->getId(), $result->getArrayOfIds()));
            }
        }
    }

    /**
     * testMultiRecordCustomField
     */
    public function testMultiRecordCustomField()
    {
        $createdCustomField = $this->_instance->addCustomField(self::getCustomField(array(
            'name'              => 'test',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'recordList', "recordListConfig" => array("value" => array("records" => "Tine.Addressbook.Model.Contact")))
        )));

        //Customfield record 1
        $contact1 = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'org_name'     => 'contact 1'
        )));
        //Customfield record 2
        $contact2 = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'org_name'     => 'contact 2'
        )));

        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family'     => 'contact'
        )));

        $cfValue = array($createdCustomField->name => array($contact1, $contact2));
        $contact->customfields = $cfValue;
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);

        self::assertTrue(is_array($contact->customfields['test']),
            'cf not saved: ' . print_r($contact->toArray(), TRUE));
        self::assertEquals(2, count($contact->customfields['test']));
        self::assertTrue(in_array($contact->customfields['test'][0]['org_name'], array('contact 1', 'contact 2')));
    }

    /**
     * testBoolCustomField
     */
    public function testBoolCustomField()
    {
        $filtersToTest = [
            ['operator' => 'equals', 'value' => 1, 'expectContactToBeFound' => true],
            ['operator' => 'equals', 'value' => '1', 'expectContactToBeFound' => true],
            ['operator' => 'equals', 'value' => true, 'expectContactToBeFound' => true],
            ['operator' => 'equals', 'value' => 0, 'expectContactToBeFound' => false],
            ['operator' => 'equals', 'value' => '0', 'expectContactToBeFound' => false],
            ['operator' => 'equals', 'value' => false, 'expectContactToBeFound' => false],
            ['operator' => 'equals', 'value' => null, 'expectContactToBeFound' => false],
        ];
        $this->_testContactCustomFieldOfType('bool', 1, $filtersToTest);
    }

    /**
     * @param mixed $customFieldValue
     * @return Tinebase_Record_Interface
     */
    protected function _createContactWithCustomField($customFieldValue)
    {
        return Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family'     => 'customfield_test_contact',
            'customfields' => [
                $this->_testCustomField->name => $customFieldValue
            ]
        )));
    }

    /**
     * @param string $type
     * @param mixed $customFieldValue
     * @param array $filtersToTest
     */
    protected function _testContactCustomFieldOfType($type, $customFieldValue, $filtersToTest)
    {
        $this->_testCustomField = $this->_createCustomField('test', 'Addressbook_Model_Contact', $type);
        $contact = $this->_createContactWithCustomField($customFieldValue);

        foreach ($filtersToTest as $filterToTest) {
            $result = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter([
                ['field' => 'customfield', 'operator' => $filterToTest['operator'], 'value' => [
                    // TODO is this needed for textarea types?
                    //Tinebase_Model_Filter_CustomField::OPT_FORCE_FULLTEXT => true,
                    'cfId' => $this->_testCustomField->getId(),
                    'value' => $filterToTest['value']
                ]]
            ]));
            if ($filterToTest['expectContactToBeFound']) {
                static::assertEquals(1, $result->count(), 'contact not found with filter '
                    . print_r($filterToTest, true)
                    . ' cf value: ' . $customFieldValue
                );
                static::assertTrue(in_array($contact->getId(), $result->getArrayOfIds()));
            } else {
                static::assertFalse(in_array($contact->getId(), $result->getArrayOfIds()));
            }
        }
    }

    /**
     * testStringCustomField
     */
    public function testStringCustomField()
    {
        $value = 'abc def 1234';
        $filtersToTest = [
            ['operator' => 'equals', 'value' => $value, 'expectContactToBeFound' => true],
            ['operator' => 'contains', 'value' => 'a', 'expectContactToBeFound' => true],
            ['operator' => 'contains', 'value' => '1234', 'expectContactToBeFound' => true],
            ['operator' => 'startswith', 'value' => 'abc', 'expectContactToBeFound' => true],
            ['operator' => 'endswith', 'value' => '1234', 'expectContactToBeFound' => true],
            ['operator' => 'equals', 'value' => 'abc', 'expectContactToBeFound' => false],
            ['operator' => 'contains', 'value' => 'x', 'expectContactToBeFound' => false],
            ['operator' => 'contains', 'value' => '', 'expectContactToBeFound' => false],
            ['operator' => 'contains', 'value' => null, 'expectContactToBeFound' => false],
            ['operator' => 'contains', 'value' => '0', 'expectContactToBeFound' => false],
        ];
        $this->_testContactCustomFieldOfType('string', $value, $filtersToTest);
    }

    /**
     * testFullTextCustomField
     *
     * @todo make it work
     * @todo is textarea always fulltext?
     */
    public function testFullTextCustomField()
    {
        self::markTestSkipped('TODO make it work');

        $value = 'abc def 1234';
        $filtersToTest = [
            ['operator' => 'startswith', 'value' => 'abc', 'expectContactToBeFound' => true],
            ['operator' => 'startswith', 'value' => 'def', 'expectContactToBeFound' => true],
            ['operator' => 'startswith', 'value' => '1234', 'expectContactToBeFound' => true],
            ['operator' => 'equals', 'value' => $value, 'expectContactToBeFound' => false],
            ['operator' => 'contains', 'value' => 'abc', 'expectContactToBeFound' => false],
        ];
        $this->_testContactCustomFieldOfType('textarea', $value, $filtersToTest);
    }

    /**
     * testIntCustomField
     */
    public function testIntCustomField()
    {
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            static::markTestSkipped('pgsql doesnt support int customfield filters');
        }

        $value = 1234;
        $filtersToTest = [
            ['operator' => 'equals', 'value' => $value, 'expectContactToBeFound' => true],
            ['operator' => 'greater', 'value' => 1233, 'expectContactToBeFound' => true],
            ['operator' => 'less', 'value' => 1235, 'expectContactToBeFound' => true],
            ['operator' => 'equals', 'value' => 123, 'expectContactToBeFound' => false],
            ['operator' => 'greater', 'value' => $value, 'expectContactToBeFound' => false],
        ];
        $this->_testContactCustomFieldOfType('int', $value, $filtersToTest);
    }

    /**
     * @see 0012222: customfields with space in name are not shown
     */
    public function testAddCustomFieldWithSpace()
    {
        $createdCustomField = $this->_instance->addCustomField(self::getCustomField(array(
            'name'              => 'my customfield',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_Contact',
        )));

        self::assertEquals('mycustomfield', $createdCustomField->name);
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
        $date->setTimezone(Tinebase_Core::getUserTimezone());
        $cf = self::getCustomField([
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array('type' => 'date')
        ]);
        $this->_instance->addCustomField($cf);
        
        $contact = new Addressbook_Model_Contact(array('n_given' => 'Rita', 'n_family' => 'Blütenrein'));
        $contact->customfields = array($cf->name => $date);
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact, false);
        
        $json = new Addressbook_Frontend_Json();
        $filter = array("condition" => "OR",
            "filters" => array(array("condition" => "AND",
                "filters" => array(
                    array("field" => "customfield", "operator" => "within", "value" => array("cfId" => $cf->getId(), "value" => "weekThis")),
                )
            ))
        );
        $result = $json->searchContacts(array($filter), array());
        
        $this->assertEquals(1, $result['totalcount'], 'searched contact not found. filter: ' . print_r($filter, true));
        $this->assertEquals('Rita', $result['results'][0]['n_given']);

        // new syntax
        $filter = array("condition" => "OR",
            "filters" => array(array("condition" => "AND",
                "filters" => array(
                    array("field" => "#" . $cf->name, "operator" => "within", "value" => "weekThis"),
                )
            ))
        );
        $result = $json->searchContacts(array($filter), array());

        $this->assertEquals(1, $result['totalcount'], 'searched contact not found. filter: ' . print_r($filter, true));
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

        // new syntax
        $result = $json->searchContacts(array(
            array("condition" => "OR",
                "filters" => array(array("condition" => "AND",
                    "filters" => array(
                        array("field" => "#" . $cf->name, "operator" => "equals", "value" => false),
                        array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Blütenrein')
                    )
                ))
            )
        ), array());

        $this->assertEquals(1, $result['totalcount'], 'One Record should have been found where cf-bool is not set (Rainer Blütenrein)');
        $this->assertEquals('Rainer', $result['results'][0]['n_given'], 'The Record should be Rainer Blütenrein');
    }

    public function testSystemCF()
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $systemCF = new Tinebase_Model_CustomField_Config([
            'application_id'    => $app->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => Addressbook_Model_Contact::class,
            'definition'        => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    Tinebase_ModelConfiguration::TYPE       => Tinebase_ModelConfiguration::TYPE_INTEGER,
                    Tinebase_ModelConfiguration::UNSIGNED   => true,
                    Tinebase_ModelConfiguration::DEFAULT_VAL   => 0,
                ],
            ],
            'is_system'         => 1,
        ], true);

        Tinebase_CustomField::getInstance()->addCustomField($systemCF);

        $record = new Addressbook_Model_Contact([], true);
        static::assertTrue($record->has($systemCF->name), 'record does not have the system cf property');

        $setup = Setup_Backend_Factory::factory();
        static::assertTrue($setup->columnExists($systemCF->name, Addressbook_Model_Contact::getConfiguration()
            ->getTableName()), 'system cf column was not created');

        // test calling it with an id, not a record
        Tinebase_CustomField::getInstance()->deleteCustomField($systemCF->getId());
        static::assertFalse($setup->columnExists($systemCF->name, Addressbook_Model_Contact::getConfiguration()
            ->getTableName()), 'system cf column was not removed');

        $record = new Addressbook_Model_Contact([], true);
        static::assertFalse($record->has($systemCF->name), 'record still has the system cf property');
    }
}
