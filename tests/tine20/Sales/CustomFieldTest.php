<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_CustomField
 */
class Sales_CustomFieldTest extends TestCase // PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_CustomField
     */
    protected $_instance;

    /**
     * transaction id if test is wrapped in an transaction
     *
    protected $_transactionId = NULL;*/
    
    protected $_user = NULL;


    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        //$this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
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
        if ($this->_user) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_user);
        }

        parent::tearDown();
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

    protected function createCustomFieldTestData()
    {
        $cf = self::getCustomField(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model' => 'Addressbook_Model_Contact',
            'definition' => array(
                'type' => 'record',
                "recordConfig" => array("value" => array("records" => "Tine.Sales.Model.Contract"))
            )
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

        return [$cf, $contract, $contact1, $contact2];
    }

    public function testSearchByRecordNotDefinedBy()
    {
        list($cf, $contract, $contact1, $contact2) = $this->createCustomFieldTestData();

        $result = (new Addressbook_Frontend_Json())->searchContacts([[
                "condition" => "AND", "filters" => [
                    ["field" => "customfield", "operator" => 'notDefinedBy:AND',
                            "value" => ["cfId" => $cf->getId(), "value" => [
                                ['field' => ':id', 'operator' => 'equals', 'value' => $contract->getId()]
                            ]]],
                    ['field' => 'n_family', 'operator' => 'equals', 'value' => 'Blütenrein']
                ]
        ]], []);

        static::assertCount(1, $result['results']);
        static::assertSame($contact2->getId(), $result['results'][0]['id']);
    }

    public function testSearchByRecordNotNull()
    {
        list($cf, $contract, $contact1, $contact2) = $this->createCustomFieldTestData();

        $result = (new Addressbook_Frontend_Json())->searchContacts([[
            "condition" => "OR",
            "filters" => array(
                array(
                    "condition" => "AND",
                    "filters" => array(
                        array(
                            "field" => "customfield",
                            "operator" => 'notDefinedBy:AND',
                            "value" => array("cfId" => $cf->getId(), "value" => [
                                ['field' => ':id', 'operator' => 'in', 'value' => null]
                            ])
                        ),
                    )
                )
            )
        ]
        ], []);

        static::assertCount(1, $result['results']);
        static::assertSame($contact1->getId(), $result['results'][0]['id']);
    }

    public function testSearchByRecordNull()
    {
        list($cf, $contract, $contact1, $contact2) = $this->createCustomFieldTestData();

        $result = (new Addressbook_Frontend_Json())->searchContacts([[
                "condition" => "OR",
                "filters" => array(
                    array(
                        "condition" => "AND",
                        "filters" => array(
                            array(
                                "field" => "customfield",
                                "operator" => "AND",
                                "value" => array("cfId" => $cf->getId(), "value" => [
                                    ['field' => ':id', 'operator' => 'in', 'value' => null]
                                ])
                            ),
                        )
                    )
                )
            ]
        ], []);

        static::assertGreaterThan(1, count($result['results']));
        foreach ($result['results'] as $res) {
            static::assertNotSame($contact1->getId(), $res['id']);
        }
    }

    /**
     * test searching records by record as a customfield type
     * https://forge.tine20.org/mantisbt/view.php?id=6730
     */
    public function testSearchByRecord()
    {
        list($cf, $contract, $contact1, $contact2) = $this->createCustomFieldTestData();

        $json = new Addressbook_Frontend_Json();

        $result = $json->searchContacts(array(
            array(
                "condition" => "OR",
                "filters" => array(
                    array(
                        "condition" => "AND",
                        "filters" => array(
                            array(
                                "field" => "customfield",
                                "operator" => "equals",
                                "value" => array("cfId" => $cf->getId(), "value" => $contract->getId())
                            ),
                        )
                    )
                )
            )
        ), array());

        $this->assertEquals(1, $result['totalcount'],
            'One Record should have been found where cf-record = contract (Rita Blütenrein)');
        $this->assertEquals('Rita', $result['results'][0]['n_given'], 'The Record should be Rita Blütenrein');

        $result = $json->searchContacts(array(
            array(
                "condition" => "OR",
                "filters" => array(
                    array(
                        "condition" => "AND",
                        "filters" => array(
                            array(
                                "field" => "customfield",
                                "operator" => "not",
                                "value" => array("cfId" => $cf->getId(), "value" => $contract->getId())
                            ),
                            array('field' => 'n_family', 'operator' => 'equals', 'value' => 'Blütenrein')
                        )
                    )
                )
            )
        ), array());

        $this->assertEquals(1, $result['totalcount'],
            'One Record should have been found where cf-record is not set (Rainer Blütenrein)');
        $this->assertEquals('Rainer', $result['results'][0]['n_given'], 'The Record should be Rainer Blütenrein');

        // search using the same cf filter in an or - filter

        $contract2 = Sales_Controller_Contract::getInstance()->create(
            new Sales_Model_Contract(
                array(
                    'number' => Tinebase_Record_Abstract::generateUID(10),
                    'title' => Tinebase_Record_Abstract::generateUID(10),
                    'container_id' => Tinebase_Container::getInstance()->getDefaultContainer('Sales_Model_Contract')->getId()
                )
            )
        );
        $contact2->customfields = array($cf->name => $contract2->getId());
        Addressbook_Controller_Contact::getInstance()->update($contact2, false);

        //Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        //$this->_transactionId = null;

        $result = $json->searchContacts(array(
            array(
                "condition" => "OR",
                "filters" => array(
                    array(
                        "condition" => "AND",
                        "filters" => array(
                            array(
                                "field" => "customfield",
                                "operator" => "equals",
                                "value" => array("cfId" => $cf->getId(), "value" => $contract->getId())
                            ),
                        )
                    ),
                    array(
                        "condition" => "AND",
                        "filters" => array(
                            array(
                                "field" => "customfield",
                                "operator" => "equals",
                                "value" => array("cfId" => $cf->getId(), "value" => $contract2->getId())
                            ),
                        )
                    )
                )
            )
        ), array());

        $this->assertEquals(2, $result['totalcount'], 'Rainer and Rita should have been found.');

        $this->assertEquals('Blütenrein', $result['results'][0]['n_family'],
            'Rainer and Rita should have been found.');
        $this->assertEquals('Blütenrein', $result['results'][1]['n_family'],
            'Rainer and Rita should have been found.');
    }
}
