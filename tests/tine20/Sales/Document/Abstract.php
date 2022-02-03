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
 * AbstractTest class for Sales_Document_*
 */
class Sales_Document_Abstract extends TestCase
{
    protected function _createProduct(array $data = []): Sales_Model_Product
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Sales_Controller_Product::getInstance()->create(new Sales_Model_Product(array_merge([
            Sales_Model_Product::FLD_NAME => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                Sales_Model_ProductLocalization::FLD_TEXT => Tinebase_Record_Abstract::generateUID(),
            ]],
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
