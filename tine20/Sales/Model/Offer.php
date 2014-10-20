<?php
/**
 * class to hold Division data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold Offer data
 *
 * @package     Sales
 */
class Sales_Model_Offer extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Offer',
        'recordsName'       => 'Offers', // ngettext('Offer', 'Offers', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
    
        'titleProperty'     => 'title',
        'appName'           => 'Sales',
        'modelName'         => 'Offer',
        'filterModel' => array(
            'customer'    => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'title' => 'Customer', // _('Customer')
                'options' => array(
                    'controller' => 'Sales_Controller_Customer',
                    'filtergroup' => 'Sales_Model_CustomerFilter',
                    'own_filtergroup' => 'Sales_Model_OfferFilter',
                    'own_controller' => 'Sales_Controller_Offer',
                    'related_model' => 'Sales_Model_Customer'
                ),
                'jsConfig' => array('filtertype' => 'sales.offer-customer')
            ),
            'order_confirmation'    => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'title' => 'Order Confirmation', // _('Order Confirmation')
                'options' => array(
                    'controller' => 'Sales_Controller_OrderConfirmation',
                    'filtergroup' => 'Sales_Model_OrderConfirmationFilter',
                    'own_filtergroup' => 'Sales_Model_OfferFilter',
                    'own_controller' => 'Sales_Controller_Offer',
                    'related_model' => 'Sales_Model_OrderConfirmation'
                ),
                'jsConfig' => array('filtertype' => 'sales.offer-order_confirmation')
            ),
        ),
        'fields'            => array(
            'number' => array(
                'label' => 'Number', //_('Number')
                'type'  => 'string',
                'queryFilter' => TRUE,
            ),
            'title' => array(
                'label'   => 'Title', // _('Title')
                'type'    => 'string',
                'queryFilter' => TRUE,
            ),
            'description'       => array(
                'label'      => 'Description',    // _('Description')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter' => TRUE,
                'type' => 'text',
            ),
            'customer' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Customer',    // _('Customer')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Customer',
                        'type' => 'OFFER'
                    )
                )
            ),
            'order_confirmation' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Order Confirmation',    // _('Order Confirmation')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'OrderConfirmation',
                        'type' => 'OFFER'
                    )
                )
            )
        )
    );
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Sales', 'relatedModel' => 'OrderConfirmation', 'config' => array(
            array('type' => 'OFFER', 'degree' => 'sibling', 'text' => 'Offer', 'max' => '0:0'),
        ), 'defaultType' => 'OFFER'),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Customer', 'config' => array(
            array('type' => 'OFFER', 'degree' => 'sibling', 'text' => 'Offer', 'max' => '0:0'),
        ), 'defaultType' => 'OFFER'),
    );
}
