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
 * class to hold InvoicePosition data
 *
 * @package     Sales
 */
class Sales_Model_InvoicePosition extends Tinebase_Record_Abstract
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
        'recordName'        => 'Invoice Position',
        'recordsName'       => 'Invoice Positions', // ngettext('Invoice Position', 'Invoice Positions', n)
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => FALSE,
        'hasAttachments'    => FALSE,
        'createModule'      => FALSE,
        'containerProperty' => NULL,
    
        'titleProperty'     => 'title',
        'appName'           => 'Sales',
        'modelName'         => 'InvoicePosition',

        'fields'            => array(
            'model' => array(
                'label'   => 'Type', // _('Type')
                'type'    => 'string',
            ),
            'invoice_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label' => NULL,
                'type'  => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Invoice',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'title' => array(
                'label'   => 'Title', // _('Title')
                'type'    => 'string',
                'queryFilter' => TRUE,
            ),
            'accountable_id' => array(
                'label'   => NULL,
                'type'    => 'string',
            ),
            'month' => array(
                'label'   => 'Month', // _('Month')
                'type'    => 'month',
            ),
            'unit' => array(
                'label'   => 'Unit', // _('Unit')
                'type'    => 'string',
            ),
            'quantity' => array(
                'label' => 'Quantity', //_('Quantity')
                'type'  => 'float',
                'summaryType' => 'sum',
            ),
        )
    );
}
