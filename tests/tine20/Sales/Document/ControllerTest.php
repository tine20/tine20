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
 * Test class for Sales_Controller_Document_*
 */
class Sales_Document_ControllerTest extends Sales_Document_Abstract
{
    public function testTransitionOfferOrder()
    {
        $customer = $this->_createCustomer();
        $product1 = $this->_createProduct();
        $product2 = $this->_createProduct();

        $offer = Sales_Controller_Document_Offer::getInstance()->create(new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID => $customer->postal->toArray(),
            Sales_Model_Document_Offer::FLD_POSITIONS => [
                new Sales_Model_DocumentPosition_Offer([
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Offer::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Offer::FLD_NET_PRICE => 1,
                ], true),
                new Sales_Model_DocumentPosition_Offer([
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'pos 2',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product2->getId(),
                    Sales_Model_DocumentPosition_Offer::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Offer::FLD_NET_PRICE => 1,
                ], true),
            ],
        ]));

        $offer->{Sales_Model_Document_Offer::FLD_OFFER_STATUS} = Sales_Model_Document_Offer::STATUS_RELEASED;
        $offer = Sales_Controller_Document_Offer::getInstance()->update($offer);

        Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Order::class,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Offer::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $offer,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                ]),
            ]
        ]));
    }
}
