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

        $this->_instance = new Sales_Frontend_Json();
    }

    public function testOfferDocumentCustomerCopy()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        unset($customerData['delivery'][0]['id']);
        $document = new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customerData
        ]);

        $document = $this->_instance->saveDocument_Offer($document->toArray(true));
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
