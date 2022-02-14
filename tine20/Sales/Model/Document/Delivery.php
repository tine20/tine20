<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Delivery Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Delivery extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Delivery';
    public const TABLE_NAME = 'sales_document_delivery';

    public const FLD_DELIVERY_STATUS = 'delivery_status';
    public const FLD_PROFORMA_NUMBER = 'proformaNumber';

    /**
     * delivery status
     */
    public const STATUS_CREATED = 'CREATED';
    public const STATUS_DELIVERED = 'DELIVERED';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Delivery'; // gettext('GENDER_Delivery')
        $_definition[self::RECORDS_NAME] = 'Deliveries'; // ngettext('Delivery', 'Deliveries', n)

        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME                      => self::TABLE_NAME,
            /*self::INDEXES                   => [
                self::FLD_PRODUCT_ID            => [
                    self::COLUMNS                   => [self::FLD_PRODUCT_ID],
                ],
            ]*/
        ];

        // delivery recipient type
        $_definition[self::FIELDS][self::FLD_RECIPIENT_ID][self::CONFIG][self::TYPE] = Sales_Model_Document_Address::TYPE_DELIVERY;

        // delivery positions
        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Delivery::MODEL_NAME_PART;

        // delivery status
        $_definition[self::FIELDS][self::FLD_DELIVERY_STATUS] = [
            self::LABEL => 'Status', // _('Status')
            self::TYPE => self::TYPE_KEY_FIELD,
            self::NAME => Sales_Config::DOCUMENT_DELIVERY_STATUS,
            self::LENGTH => 255,
            self::NULLABLE => true,
        ];

        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::NULLABLE] = true;
        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable::CONFIG_OVERRIDE] =
            Sales_Controller_Document_Delivery::class . '::documentNumberConfigOverride';

        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_PROFORMA_NUMBER => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::LABEL                     => 'Proforma Number', //_('Proforma Number')
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
                    Tinebase_Numberable::BUCKETKEY         => self::class . '#' . self::FLD_PROFORMA_NUMBER,
                    Tinebase_Numberable_String::PREFIX     => 'PD-', // _('PD-')
                    Tinebase_Numberable_String::ZEROFILL   => 7,
                    Tinebase_Numberable::CONFIG_OVERRIDE   =>
                        Sales_Controller_Document_Delivery::class . '::proformaNumberConfigOverride',
                ],
            ],
        ]);
    }

    protected static $_statusField = self::FLD_DELIVERY_STATUS;
    protected static $_statusConfigKey = Sales_Config::DOCUMENT_DELIVERY_STATUS;
    protected static $_documentNumberPrefix = 'DN-'; // _('DN-')

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function transitionFrom(Sales_Model_Document_Transition $transition)
    {
        parent::transitionFrom($transition);

        $this->{self::FLD_RECIPIENT_ID} = $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}
            ->getFirstRecord()->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
            ->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID};
    }
}

