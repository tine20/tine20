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

    public function testOfferDocumentCustomerCopy($noAsserts = false)
    {
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document = new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customerData
        ]);

        $document = $this->_instance->saveDocument_Offer($document->toArray(true));
        if ($noAsserts) {
            return $document;
        }

        $customerCopy = Sales_Controller_Document_Customer::getInstance()->get($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]);
        $expander = new Tinebase_Record_Expander(Sales_Model_Document_Customer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'delivery' => [],
            ]
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Customer::class, [$customerCopy]));

        $this->assertNotSame($customer->getId(), $customerCopy->getId());
        $this->assertSame($customer->name, $customerCopy->name);
        $this->assertNotSame($customer->delivery->getId(), $customerCopy->delivery->getId());
        $this->assertSame($customer->delivery->name, $customerCopy->delivery->name);

        return $document;
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
        ]);

        $document = $this->_instance->saveDocument_Offer($document->toArray(true));
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
                'name' => 'some addess for ' . $name,
                'type' => 'delivery'
            ]]),
        ]));

        $expander = new Tinebase_Record_Expander(Sales_Model_Customer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'delivery' => [],
            ]
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Customer::class, [$customer]));
        return $customer;
    }
}
