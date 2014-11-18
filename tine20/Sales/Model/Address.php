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
        'modlogActive'    => TRUE,
        'containerProperty' => NULL,
        'createModule'    => FALSE,
        'isDependent'     => TRUE,
        'titleProperty'   => 'fulltext',
        'appName'         => 'Sales',
        'modelName'       => 'Address',
        'resolveRelated'  => TRUE,
        'defaultFilter'   => 'query',
        'resolveVFGlobally' => TRUE,
        
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
            'street' => array(
                'label' => 'Street', //_('Street')
                'queryFilter' => TRUE
            ),
            'pobox' => array(
                'label' => 'Postbox', //_('Postbox')
                'queryFilter' => TRUE
            ),
            'postalcode' => array(
                'label' => 'Postalcode', //_('Postalcode')
                'queryFilter' => TRUE
            ),
            'locality' => array(
                'label' => 'Locality', //_('Locality')
                'queryFilter' => TRUE
            ),
            'region' => array(
                'label' => 'Region', //_('Region')
                'queryFilter' => TRUE
            ),
            'countryname' => array(
                'label'   => 'Country', //_('Country')
                'default' => 'DE',
                'queryFilter' => TRUE
            ),
            'prefix1' => array(
                'label'   => 'Prefix', //_('Prefix')
                'queryFilter' => TRUE
            ),
            'prefix2' => array(
                'label'   => 'Additional Prefix', //_('Additional Prefix')
                'queryFilter' => TRUE
            ),
            'custom1' => array(
                'label' => 'Number Debit', //_('Number Debit')
                'queryFilter' => TRUE
            ),
            'type' => array(
                'label' => NULL,
                'default' => 'postal',
                'queryFilter' => TRUE
            ),
            
            'fulltext' => array(
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'   => NULL
                ),
                'type' => 'virtual',
            ),
        )
    );
}
