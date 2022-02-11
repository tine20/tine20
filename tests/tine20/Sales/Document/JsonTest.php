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
class Sales_Document_JsonTest extends Sales_Document_Abstract
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

        $this->assertSame(1, Sales_Controller_Document_Offer::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Offer::class, [
                ['field' => 'customer_id', 'operator' => 'definedBy', 'value' => [
                    ['field' => 'name', 'operator' => 'equals', 'value' => $customer->name],
                ]],
            ]))->count());

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

    public function testOfferToOrderTransition()
    {
        $customer = $this->_createCustomer();
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
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product->toArray(),
                    Sales_Model_DocumentPosition_Offer::FLD_SALES_TAX_RATE => 19,
                    Sales_Model_DocumentPosition_Offer::FLD_SALES_TAX => 100 * 19 / 100,
                    Sales_Model_DocumentPosition_Offer::FLD_NET_PRICE => 100,
                ]
            ],
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID => $customer->postal->toArray(),
        ]);

        $savedDocument = $this->_instance->saveDocument_Offer($document->toArray(true));
        $savedDocument[Sales_Model_Document_Offer::FLD_OFFER_STATUS] = Sales_Model_Document_Offer::STATUS_RELEASED;
        $savedDocument = $this->_instance->saveDocument_Offer($savedDocument);

        $result = $this->_instance->createFollowupDocument((new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL => Sales_Model_Document_Offer::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $savedDocument,
                ]),
            ],
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE =>
                Sales_Model_Document_Order::class,
        ]))->toArray());
    }

    public function testOrderDocument()
    {
        $offer = $this->testOfferDocumentCustomerCopy(true);

        $order = new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $offer[Sales_Model_Document_Offer::FLD_CUSTOMER_ID],
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_RECEIVED,
            Sales_Model_Document_Order::FLD_PRECURSOR_DOCUMENTS => [
                $offer
            ]
        ]);
        $this->_instance->saveDocument_Order($order->toArray());
    }

    protected function getTrackingTestData()
    {
        $testData = [];
        $customer = $this->_createCustomer();

        $offer1 = Sales_Controller_Document_Offer::getInstance()->create(new Sales_Model_Document_Offer([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
        ]));
        $testData[$offer1->getId()] = $offer1;

        $offer2 = Sales_Controller_Document_Offer::getInstance()->create(new Sales_Model_Document_Offer([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
        ]));
        $testData[$offer2->getId()] = $offer2;

        $order = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS => new Tinebase_Record_RecordSet(
                Tinebase_Model_DynamicRecordWrapper::class, [
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => Sales_Model_Document_Offer::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $offer1->getId(),
                    ]),
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => Sales_Model_Document_Offer::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $offer2->getId(),
                    ])
                ]
            ),
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_RECEIVED,
        ]));
        $testData[$order->getId()] = $order;

        $delivery1 = Sales_Controller_Document_Delivery::getInstance()->create(new Sales_Model_Document_Delivery([
            Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS => new Tinebase_Record_RecordSet(
                Tinebase_Model_DynamicRecordWrapper::class, [
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => Sales_Model_Document_Order::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $order->getId(),
                    ])
                ]
            ),
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS => Sales_Model_Document_Delivery::STATUS_CREATED,
        ]));
        $testData[$delivery1->getId()] = $delivery1;

        $delivery2 = Sales_Controller_Document_Delivery::getInstance()->create(new Sales_Model_Document_Delivery([
            Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS => new Tinebase_Record_RecordSet(
                Tinebase_Model_DynamicRecordWrapper::class, [
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => Sales_Model_Document_Order::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $order->getId(),
                    ])
                ]
            ),
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS => Sales_Model_Document_Delivery::STATUS_CREATED,
        ]));
        $testData[$delivery2->getId()] = $delivery2;

        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS => new Tinebase_Record_RecordSet(
                Tinebase_Model_DynamicRecordWrapper::class, [
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => Sales_Model_Document_Order::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $order->getId(),
                    ])
                ]
            ),
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
        ]));
        $testData[$invoice->getId()] = $invoice;

        return $testData;
    }

    public function testTrackDocument()
    {
        $testData = $this->getTrackingTestData();
        $order = null;
        $offer = null;
        foreach ($testData as $document) {
            if ($document instanceof Sales_Model_Document_Order) {
                $order = $document;
            } elseif ($document instanceof Sales_Model_Document_Offer) {
                $offer = $document;
            }
        }
        $documents = $this->_instance->trackDocument(Sales_Model_Document_Order::class, $order->getId());
        $this->assertSame(count($testData), count($documents));

        $data = $testData;
        foreach ($documents as $wrapper) {
            $id = $wrapper[Tinebase_Model_DynamicRecordWrapper::FLD_RECORD]['id'];
            $this->assertArrayHasKey($id, $data);
            $this->assertSame(get_class($data[$id]), $wrapper[Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME]);
            unset($data[$id]);
        }
        $this->assertEmpty($data);

        $documents = $this->_instance->trackDocument(Sales_Model_Document_Offer::class, $offer->getId());
        $this->assertSame(count($testData), count($documents));

        $data = $testData;
        foreach ($documents as $wrapper) {
            $id = $wrapper[Tinebase_Model_DynamicRecordWrapper::FLD_RECORD]['id'];
            $this->assertArrayHasKey($id, $data);
            $this->assertSame(get_class($data[$id]), $wrapper[Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME]);
            unset($data[$id]);
        }
        $this->assertEmpty($data);
    }

    public function testDocumentPrecursorReadonly()
    {
        $testData = $this->getTrackingTestData();

        /**
         * @var string $id
         * @var Sales_Model_Document_Abstract $document
         */
        foreach ($testData as $id => $document) {
            if (!$document->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS} ||
                    $document->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS}->count() < 1) {
                continue;
            }
            (new Tinebase_Record_Expander(get_class($document), $document::getConfiguration()->jsonExpander))
                ->expand(new Tinebase_Record_RecordSet(get_class($document), [$document]));
            $oldId = $document->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS}->getFirstRecord()
                ->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD};
            $document->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS}->getFirstRecord()
                ->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD} = Tinebase_Record_Abstract::generateUID();
            $data = $document->toArray();
            $data = $this->_instance->{'save' . $document::getConfiguration()->getModelName()}($data);
            $this->assertSame($oldId, $data[Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS][0]
                [Tinebase_Model_DynamicRecordWrapper::FLD_RECORD]);
        }
    }
}
