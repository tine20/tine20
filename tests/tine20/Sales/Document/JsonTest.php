<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Sales_Frontend_Json
 */
class Sales_Document_JsonTest extends TestCase
{
    /**
     * @var Sales_Frontend_Json
     */
    protected $_instance = null;

    protected function setUp(): void
    {
        parent::setUp();

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $this->_instance = new Sales_Frontend_Json();
    }

    public function testOfferBoilerplates()
    {
        $boilerplate = Sales_Controller_Boilerplate::getInstance()->create(
            Sales_BoilerplateControllerTest::getBoilerplate());

        $document = new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_BOILERPLATES => [
                $boilerplate->toArray()
            ],
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT
        ]);
        $document = $this->_instance->saveDocument_Offer($document->toArray(true));

        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_BOILERPLATES]);
        $this->assertCount(1, $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES]);
        $this->assertNotSame($boilerplate->getId(), $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0]['id']);
        $this->assertSame('0', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);
        $this->assertSame($boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE},
            $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);

        $boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE} = 'cascading changes?';
        $boilerplate = Sales_Controller_Boilerplate::getInstance()->update($boilerplate);
        $document = $this->_instance->getDocument_Offer($document['id']);

        $this->assertNotSame($boilerplate->getId(), $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0]['id']);
        $this->assertSame('0', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);
        $this->assertSame($boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE},
            $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);

        $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE] =
            'local stuff';
        $document = $this->_instance->saveDocument_Offer($document);
        $this->assertSame('1', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);

        $boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE} = 'not cascading';
        $boilerplate = Sales_Controller_Boilerplate::getInstance()->update($boilerplate);
        $document = $this->_instance->getDocument_Offer($document['id']);
        $this->assertSame('1', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);
        $this->assertNotSame($boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE},
            $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);
        $this->assertSame('local stuff', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);

        $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0] =
            $boilerplate = Sales_BoilerplateControllerTest::getBoilerplate()->toArray(false);
        $document = $this->_instance->saveDocument_Offer($document);
        $this->assertSame('1', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);
        $this->assertSame($boilerplate[Sales_Model_Boilerplate::FLD_BOILERPLATE],
            $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);
    }

    public function testOfferDocumentWithoutRecipient()
    {
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document = new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customerData,
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID => '',
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
        ]);
        $document = $this->_instance->saveDocument_Offer($document->toArray(true));

        $this->assertFalse(isset($document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]));
    }

    public function testDeleteDocument()
    {
        $boilerplate = Sales_Controller_Boilerplate::getInstance()->create(
            Sales_BoilerplateControllerTest::getBoilerplate());
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document = new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customerData,
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID => '',
            Sales_Model_Document_Offer::FLD_BOILERPLATES => [
                $boilerplate->toArray()
            ],
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
        ]);
        $document = $this->_instance->saveDocument_Offer($document->toArray(true));
        $this->_instance->deleteDocument_Offers($document['id']);
    }

    public function testOfferDocumentCustomerCopy($noAsserts = false)
    {
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document = new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customerData,
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID => $customerData['delivery'][0],
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
        ]);

        $document = $this->_instance->saveDocument_Offer($document->toArray(true));
        if ($noAsserts) {
            return $document;
        }

        $this->assertSame($customerData['number'] . ' - ' . $customerData['name'], $customerData['fulltext']);

        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID], 'customer_id is not an array');
        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['billing'], 'customer_id.billing is not an array');
        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['delivery'], 'customer_id.delivery is not an array');
        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['postal'], 'customer_id.postal is not an array');
        $this->assertIsString($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['delivery'][0]['id'], 'customer_id.delivery.0.id is not set');
        $this->assertIsString($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['postal']['id'], 'customer_id.postal.id is not set');
        $this->assertStringStartsWith('teststreet for ', $document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['postal']['street']);
        $this->assertSame($customerData['number'] . ' - ' . $customerData['name'],
            $document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['fulltext']);
        $this->assertArrayHasKey('fulltext', $document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]);
        $this->assertStringContainsString('delivery', $document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]['fulltext']);

        $customerCopy = Sales_Controller_Document_Customer::getInstance()->get($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]);
        $expander = new Tinebase_Record_Expander(Sales_Model_Document_Customer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'delivery' => [],
                'billing'  => [],
                'postal'   => [],
            ]
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Customer::class, [$customerCopy]));

        $this->assertNotSame($customer->getId(), $customerCopy->getId());
        $this->assertSame($customer->name, $customerCopy->name);
        $this->assertSame(1, $customerCopy->delivery->count());
        $this->assertSame(1, $customerCopy->billing->count());
        $this->assertNotSame($customer->delivery->getId(), $customerCopy->delivery->getId());
        $this->assertSame($customer->delivery->name, $customerCopy->delivery->name);
        $this->assertNotSame($customer->billing->getId(), $customerCopy->billing->getId());
        $this->assertSame($customer->billing->name, $customerCopy->billing->name);
        $this->assertSame($customer->postal->name, $customerCopy->postal->name);

        $this->assertNotSame($document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]['id'], $customerCopy->delivery->getFirstRecord()->getId());
        $this->assertNotSame($document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]['id'], $customer->delivery->getFirstRecord()->getId());

        return $document;
    }

    public function testOfferDocumentUpdate()
    {
        $document = $this->testOfferDocumentCustomerCopy(true);

        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document[Sales_Model_Document_Offer::FLD_CUSTOMER_ID] = $customerData;
        $document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID] = '';

        $updatedDocument = $this->_instance->saveDocument_Offer($document);

        $this->assertNotSame($updatedDocument[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['id'],
            $document[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['id']);
        $this->assertNotSame($updatedDocument[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['delivery'][0]['id'],
            $document[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['delivery'][0]['id']);
        $this->assertNotSame($updatedDocument[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'][0]['id'],
            $document[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'][0]['id']);
        $this->assertEmpty($updatedDocument[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]);

        $updated2Document = $updatedDocument;
        $updated2Document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID] =
            $updatedDocument[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'][0];
        $updated2Document[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'] = null;
        $updated2Document = $this->_instance->saveDocument_Offer($updated2Document);
        $this->assertSame($updatedDocument[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'][0]['id'],
            $updated2Document[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'][0]['id']);
        $this->assertNotSame($updatedDocument[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'][0]['id'],
            $updated2Document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]['id']);
        $this->assertSame($updatedDocument[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'][0]['original_id'],
            $updated2Document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]['original_id']);
        $this->assertNull($updated2Document[Sales_Model_Document_Offer::FLD_RECIPIENT_ID]['customer_id']);

        $updated2Document[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing'] = [];
        $updated2Document = $this->_instance->saveDocument_Offer($updated2Document);
        $this->assertEmpty($updated2Document[Sales_Model_Document_Offer::FLD_CUSTOMER_ID]['billing']);

        $document = Sales_Controller_Document_Offer::getInstance()->get($document['id']);
        $docExpander = new Tinebase_Record_Expander(Sales_Model_Document_Offer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Sales_Model_Document_Offer::FLD_CUSTOMER_ID => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'delivery' => [],
                        'postal' => [],
                    ]
                ]
            ]
        ]);
        $docExpander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Offer::class, [$document]));

        $deliveryAddress = $document->{Sales_Model_Document_Offer::FLD_CUSTOMER_ID}->delivery->getFirstRecord();
        $oldDeliveryAddress = clone $deliveryAddress;
        $deliveryAddress->name = 'other name';

        $documentUpdated = $this->_instance->saveDocument_Offer($document->toArray(true));

        $customer = $document->{Sales_Model_Document_Offer::FLD_CUSTOMER_ID};
        $customerUpdated = Sales_Controller_Document_Customer::getInstance()->get($documentUpdated[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]);
        $expander = new Tinebase_Record_Expander(Sales_Model_Document_Customer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'delivery' => [],
                'postal' => [],
            ]
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Customer::class, [$customerUpdated]));

        $this->assertSame($customer->getId(), $customerUpdated->getId());
        $this->assertSame($customer->delivery->getId(), $customerUpdated->delivery->getId());
        $this->assertSame($oldDeliveryAddress->getId(), $customerUpdated->delivery->getFirstRecord()->getId());
        $this->assertNotSame($oldDeliveryAddress->name, $customerUpdated->delivery->getFirstRecord()->name);
        $this->assertSame('other name', $customer->delivery->getFirstRecord()->name);

        $secondCustomer = $this->_createCustomer();
        $document = Sales_Controller_Document_Offer::getInstance()->get($documentUpdated['id']);
        $docExpander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Offer::class, [$document]));

        $document->{Sales_Model_Document_Offer::FLD_CUSTOMER_ID}->delivery->getFirstRecord()->name = 'shoo';
        $document->{Sales_Model_Document_Offer::FLD_CUSTOMER_ID}->delivery->addRecord(new Sales_Model_Document_Address($secondCustomer->delivery->getFirstRecord()->toArray()));
        $document->{Sales_Model_Document_Offer::FLD_CUSTOMER_ID}->postal = [
            'name' => 'new postal adr',
            'seq' => $document->{Sales_Model_Document_Offer::FLD_CUSTOMER_ID}->postal->seq,
        ];

        $documentUpdated = $this->_instance->saveDocument_Offer($document->toArray(true));
        $customerUpdated = Sales_Controller_Document_Customer::getInstance()->get($documentUpdated[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Customer::class, [$customerUpdated]));

        $this->assertSame(2, $customerUpdated->delivery->count());
        foreach ($customerUpdated->delivery as $address) {
            if ('shoo' === $address->name) {
                $this->assertSame($oldDeliveryAddress->getId(), $address->getId());
            } else {
                $this->assertNotSame($oldDeliveryAddress->getId(), $address->getId());
                $this->assertNotSame($secondCustomer->delivery->getFirstRecord()->getId(), $address->getId());
                $this->assertSame($secondCustomer->delivery->getFirstRecord()->name, $address->name);
            }
        }
        $this->assertSame($customer->postal->getId(), $customerUpdated->postal->getId());
        $this->assertSame('new postal adr', $customerUpdated->postal->name);
    }

    public function testOfferDocumentPosition()
    {
        $subProduct = $this->_createProduct();
        $product = $this->_createProduct([
            Sales_Model_Product::FLD_SUBPRODUCTS => [(new Sales_Model_SubProductMapping([
                Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProduct,
                Sales_Model_SubProductMapping::FLD_SHORTCUT => 'lorem',
                Sales_Model_SubProductMapping::FLD_QUANTITY => 1,
            ], true))->toArray()]
        ]);

        $document = new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_POSITIONS => [
                [
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'ipsum',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product->toArray()
                ]
            ],
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
        ]);

        $this->_instance->saveDocument_Offer($document->toArray(true));
    }

    public function testOrderDocument()
    {
        $offer = $this->testOfferDocumentCustomerCopy(true);

        $order = new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $offer[Sales_Model_Document_Offer::FLD_CUSTOMER_ID],
            Sales_Model_Document_Order::FLD_PRECURSOR_DOCUMENTS => [
                $offer
            ]
        ]);
        $this->_instance->saveDocument_Order($order->toArray());
    }

    protected function _createProduct(array $data = []): Sales_Model_Product
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Sales_Controller_Product::getInstance()->create(new Sales_Model_Product(array_merge([
            Sales_Model_Product::FLD_NAME => Tinebase_Record_Abstract::generateUID(),
        ], $data)));
    }

    protected function _createCustomer(): Sales_Model_Customer
    {
        $name = Tinebase_Record_Abstract::generateUID();
        /** @var Sales_Model_Customer $customer */
        $customer = Sales_Controller_Customer::getInstance()->create(new Sales_Model_Customer([
            'name' => $name,
            'cpextern_id' => $this->_personas['sclever']->contact_id,
            'bic' => 'SOMEBIC',
            'delivery' => new Tinebase_Record_RecordSet(Sales_Model_Address::class,[[
                'name' => 'some delivery address for ' . $name,
                'type' => 'delivery'
            ]]),
            'billing' => new Tinebase_Record_RecordSet(Sales_Model_Address::class,[[
                'name' => 'some billing address for ' . $name,
                'type' => 'billing'
            ]]),
            'postal' => new Sales_Model_Address([
                'name' => 'some postal address for ' . $name,
                'street' => 'teststreet for ' . $name,
                'type' => 'postal'
            ]),
        ]));

        $expander = new Tinebase_Record_Expander(Sales_Model_Customer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'delivery' => [],
                'billing' => [],
                'postal' => [],
            ]
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Customer::class, [$customer]));
        return $customer;
    }
}
