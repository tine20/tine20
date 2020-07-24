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
 * class to hold Supplier data
 *
 * @package     Sales
 * @subpackage  Model
 */

class Sales_Model_Supplier extends Tinebase_Record_Abstract
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
        'recordName'        => 'Supplier',
        'recordsName'       => 'Suppliers', // ngettext('Supplier', 'Suppliers', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
        'resolveVFGlobally' => TRUE,
        
        'titleProperty'     => 'fulltext',
        'appName'           => 'Sales',
        'modelName'         => 'Supplier',

        'exposeHttpApi'     => true,
        
        'fieldGroups'       => array(
            'core'       => 'Core Data',     // _('Core Data')
            'accounting' => 'Accounting',    // _('Accounting')
            'misc'       => 'Miscellaneous',    // _('Miscellaneous')
        ),

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],
        
        'fields'            => array(
            'number' => array(
                'label'       => 'Supplier Number', //_('Supplier Number')
                'group'       => 'core',
                'queryFilter' => TRUE,
                'type'        => 'integer'
            ),
            'name' => array(
                'label'       => 'Name', // _('Name')
                'type'        => 'text',
                'duplicateCheckGroup' => 'name',
                'group'       => 'core',
                'queryFilter' => TRUE,
            ),
            'url' => array(
                'label'       => 'Web', // _('Web')
                'type'        => 'text',
                'group'       => 'misc',
                'shy'         => TRUE
            ),
            'description' => array(
                'label'       => 'Description', // _('Description')
                'group'       => 'core',
                'type'        => 'fulltext',
                'queryFilter' => TRUE,
                'shy'         => TRUE
            ),
            'cpextern_id'       => array(
                'label'   => 'Contact Person (external)',    // _('Contact Person (external)')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'    => 'record',
                'group'   => 'core',
                'config'  => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                )
            ),
            'cpintern_id'    => array(
                'label'      => 'Contact Person (internal)',    // _('Contact Person (internal)')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'group'      => 'core',
                'config' => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                )
            ),
            'vatid' => array (
                'label'   => 'VAT ID', // _('VAT ID')
                'type'    => 'text',
                'group'   => 'accounting',
                'shy'     => TRUE
            ),
            'credit_term' => array (
                'label'   => 'Credit Term (days)', // _('Credit Term (days)')
                'type'    => 'integer',
                'group'   => 'accounting',
                'default' => 10,
                'shy'     => TRUE
            ),
            'currency' => array (
                'label'   => 'Currency', // _('Currency')
                'type'    => 'text',
                'group'   => 'accounting'
            ),
            'currency_trans_rate' => array (
                'label'   => 'Currency Translation Rate', // _('Currency Translation Rate')
                'type'    => 'float',
                'group'   => 'accounting',
                'default' => 1,
                'shy'     => TRUE
            ),
            'iban' => array (
                'label'   => 'IBAN',
                'group'   => 'accounting',
                'shy'     => TRUE
            ),
            'bic' => array (
                'label'   => 'BIC',
                'group'   => 'accounting',
                'shy'     => TRUE
            ),
            #'discount' => array(
            #    'label'   => 'Discount (%)', // _('Discount (%)')
            #    'type'  => 'float',
            #    'specialType' => 'percent',
            #    'default' => 0,
            #    //'inputFilters' => array('Zend_Filter_Empty' => 0),
            #    //'shy' => TRUE,
            #),
            // the postal address
            'postal_id' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => NULL,
                )
            ),
            'adr_prefix1' => array(
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Prefix', //_('Prefix')
                    'shy'           => TRUE
                ),
                'type'   => 'virtual',
            ),
            'adr_prefix2' => array(
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Additional Prefix', //_('Additional Prefix')
                    'shy'           => TRUE
                ),
                'type'   => 'virtual',
            ),
            'adr_street' => array(
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Street', //_('Street')
                    'shy'           => TRUE
                ),
                'type' => 'virtual',
            ),
            'adr_postalcode' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Postalcode', //_('Postalcode')
                    'shy'           => TRUE
                ),
            ),
            'adr_locality' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Locality', //_('Locality')
                    'shy'           => TRUE
                ),
            ),
            'adr_region' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Region', //_('Region')
                    'shy'           => TRUE
                ),
            ),
            'adr_countryname' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Country', //_('Country')
                    'shy'           => TRUE,
                    'default'       => 'DE'
                ),
            ),
            'adr_pobox' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Postbox', //_('Postbox')
                    'shy'           => TRUE
                ),
            ),
            'fulltext' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'string',
                    'sortable' => false
                )           
            ),
        )
    );
    
    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     *
     * @todo remove custom fields handling (use Tinebase_Record_RecordSet for them)
     */
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        $this->fulltext = $this->number . ' - ' . $this->name;
    }
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array(
            'relatedApp'   => 'Addressbook',
            'relatedModel' => 'Contact',
            'config'       => array(
                array('type' => 'SUPPLIER', 'degree' => 'sibling', 'text' => 'Supplier', 'max' => '0:0'), // _('Supplier')
            ),
            'defaultType'  => 'SUPPLIER'
        )
    );
}
