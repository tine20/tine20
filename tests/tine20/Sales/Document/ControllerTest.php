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
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID => $customer->postal,
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

        $order = Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
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

        return $order;
    }

    public function testPositionRemoval()
    {
        $order = $this->testTransitionOfferOrder();
        Tinebase_Record_Expander::expandRecord($order);

        $order->{Sales_Model_Document_Abstract::FLD_POSITIONS}->removeFirst();
        $order = Sales_Controller_Document_Order::getInstance()->update($order);
        Tinebase_Record_Expander::expandRecord($order);

        $this->assertCount(1, $order->{Sales_Model_Document_Abstract::FLD_POSITIONS});
        $this->assertCount(1, $order->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS});

        $order->{Sales_Model_Document_Abstract::FLD_POSITIONS}->removeFirst();
        $order = Sales_Controller_Document_Order::getInstance()->update($order);
        Tinebase_Record_Expander::expandRecord($order);

        $this->assertCount(0, $order->{Sales_Model_Document_Abstract::FLD_POSITIONS});
        $this->assertCount(0, $order->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS});
    }

    public function testInvoiceNumbers()
    {
        $customer = $this->_createCustomer();

        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Abstract::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
        ]));
        $expander = new Tinebase_Record_Expander(Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Invoice::getConfiguration()->jsonExpander);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Invoice::class, [$invoice]));

        $translate = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
            new Zend_Locale(Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE}));

        $inTranslated = $translate->_('IN-');
        $piTranslated = $translate->_('PI-');

        $this->assertStringStartsWith($piTranslated, $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertEmpty($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER});

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_DISCOUNT_TYPE} = Sales_Config::INVOICE_DISCOUNT_SUM;
        $updatedInvoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Invoice::class, [$updatedInvoice]));

        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER},
            $updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertEmpty($updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER});

        $updatedInvoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $updatedInvoice = Sales_Controller_Document_Invoice::getInstance()->update($updatedInvoice);

        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER},
            $updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertNotEmpty($updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER});
        $this->assertStringStartsWith($inTranslated, $updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER});
    }

    public function testDeliveryNumbers()
    {
        $customer = $this->_createCustomer();

        $delivery = Sales_Controller_Document_Delivery::getInstance()->create(new Sales_Model_Document_Delivery([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Abstract::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS => Sales_Model_Document_Delivery::STATUS_CREATED,
        ]));
        $expander = new Tinebase_Record_Expander(Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Delivery::getConfiguration()->jsonExpander);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Delivery::class, [$delivery]));

        $translate = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
            new Zend_Locale(Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE}));

        $dnTranslated = $translate->_('DN-');
        $pdTranslated = $translate->_('PD-');

        $this->assertStringStartsWith($pdTranslated, $delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertEmpty($delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER});

        $delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_DATE} = Tinebase_DateTime::today()->subDay(1);
        $updatedDelivery = Sales_Controller_Document_Delivery::getInstance()->update($delivery);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Delivery::class, [$updatedDelivery]));

        $this->assertSame($delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER},
            $updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertEmpty($updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER});

        $updatedDelivery->{Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS} = Sales_Model_Document_Delivery::STATUS_DELIVERED;
        $updatedDelivery = Sales_Controller_Document_Delivery::getInstance()->update($updatedDelivery);

        $this->assertSame($delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER},
            $updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertNotEmpty($updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER});
        $this->assertStringStartsWith($dnTranslated, $updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER});
    }
}
