<?php
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Address data
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Address extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;
    
    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'      => 'Address', // ngettext('Address', 'Addresss', n)
        'recordsName'     => 'Addresss',
        'hasRelations'    => TRUE,
        'hasCustomFields' => FALSE,
        'hasNotes'        => FALSE,
        'hasTags'         => FALSE,
        'modlogActive'    => FALSE,
        'containerProperty' => NULL,
        'createModule'    => FALSE,
        'isDependent'     => TRUE,
        'titleProperty'   => 'customer_id',
        'appName'         => 'Sales',
        'modelName'       => 'Address',
        'resolveRelated'  => TRUE,
        
        'fields'          => array(
            'customer_id'       => array(
                'label'      => 'Customer',    // _('Customer')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'sortable'   => FALSE,
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Customer',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'prefix1' => array(
                'label'   => 'Prefix', //_('Prefix')
            ),
            'prefix2' => array(
                'label'   => 'Additional Prefix', //_('Additional Prefix')
            ),
            'street' => array(
                'label' => 'Street', //_('Street')
            ),
            'postalcode' => array(
                'label' => 'Postalcode', //_('Postalcode')
            ),
            'locality' => array(
                'label' => 'Locality', //_('Locality')
            ),
            'region' => array(
                'label' => 'Region', //_('Region')
            ),
            'countryname' => array(
                'label'   => 'Country', //_('Country')
                'default' => 'Germany', // _('Germany')
            ),
            'pobox' => array(
                'label' => 'Postbox', //_('Postbox')
            ),
            'custom1' => array(
                'label' => 'Number Debit', //_('Number Debit')
            ),
            'type' => array(
                'label' => NULL,
                'default' => 'postal'
            ),
        )
    );
}
