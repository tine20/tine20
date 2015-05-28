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
 * class to hold OrderConfirmation data
 *
 * @package     Sales
 */
class Sales_Model_OrderConfirmation extends Tinebase_Record_Abstract
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
        'recordName'        => 'Order Confirmation',
        'recordsName'       => 'Order Confirmations', // ngettext('Order Confirmation', 'Order Confirmations', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,

        'titleProperty'     => 'fulltext', //array('%s - %s', array('number', 'title')),
        'appName'           => 'Sales',
        'modelName'         => 'OrderConfirmation',
        'filterModel' => array(
            'contract'    => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'title' => 'Contract', // _('Contract')
                'options' => array(
                    'controller' => 'Sales_Controller_Contract',
                    'filtergroup' => 'Sales_Model_ContractFilter',
                    'own_filtergroup' => 'Sales_Model_OrderConfirmationFilter',
                    'own_controller' => 'Sales_Controller_OrderConfirmation',
                    'related_model' => 'Sales_Model_Contract'
                ),
                'jsConfig' => array('filtertype' => 'sales.orderconfirmation-contract')
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
            'contract' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Contract',    // _('Contract')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Contract',
                        'type' => 'CONTRACT'
                    )
                )
            ),
            'fulltext' => array(
                'type' => 'string',
            )
        )
    );

    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     **/
    public function setFromArray(array $_data)
    {
        parent::setFromArray($_data);
        $this->fulltext = $this->number . ' - ' . $this->title;
    }

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Sales', 'relatedModel' => 'Contract', 'config' => array(
            array('type' => 'CONTRACT', 'degree' => 'sibling', 'text' => 'Contract', 'max' => '1:0'),
        ), 'defaultType' => ''
    ));
}
