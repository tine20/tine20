<?php
/**
 * class to hold contract data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contract data
 *
 * @package     Sales
 *
 * @property Tinebase_DateTime      $end_date
 */
class Sales_Model_Contract extends Tinebase_Record_Abstract
{
    /**
     * relation type: customer
     *
     */
    const RELATION_TYPE_CUSTOMER = 'CUSTOMER';
    
    /**
     * relation type: responsible
     *
     */
    const RELATION_TYPE_RESPONSIBLE = 'RESPONSIBLE';
    
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
        'recordName'        => 'Contract',
        'recordsName'       => 'Contracts', // ngettext('Contract', 'Contracts', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        
        'containerProperty' => 'container_id',

        'containerName'    => 'Contracts',
        'containersName'    => 'Contracts',
        'containerUsesFilter' => FALSE,

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],
        
        'titleProperty'     => 'fulltext',//array('%s - %s', array('number', 'title')),
        'appName'           => 'Sales',
        'modelName'         => 'Contract',
        
        'filterModel' => array(
            'customer' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Customer', // _('Customer')
                'options' => array(
                    'controller' => 'Sales_Controller_Customer',
                    'filtergroup' => 'Sales_Model_CustomerFilter',
                    'own_filtergroup' => 'Sales_Model_ContractFilter',
                    'own_controller' => 'Sales_Controller_Contract',
                    'related_model' => 'Sales_Model_Customer',
                ),
                'jsConfig' => array('filtertype' => 'sales.contractcustomer')
            ),
            'costcenter' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Cost Center', // _('Cost Center')
                'options' => array(
                    'controller' => 'Sales_Controller_CostCenter',
                    'filtergroup' => 'Sales_Model_CostCenterFilter',
                    'own_filtergroup' => 'Sales_Model_ContractFilter',
                    'own_controller' => 'Sales_Controller_Contract',
                    'related_model' => 'Sales_Model_CostCenter',
                ),
                'jsConfig' => array('filtertype' => 'sales.contractcostcenter')
            ),
            'contact_internal' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Contact Person (internal)', // _('Contact Person (internal)')
                'options' => array(
                    'controller' => 'Addressbook_Controller_Contact',
                    'filtergroup' => 'Addressbook_Model_ContactFilter',
                    'own_filtergroup' => 'Sales_Model_ContractFilter',
                    'own_controller' => 'Sales_Controller_Contract',
                    'related_model' => 'Addressbook_Model_Contact',
                ),
                'jsConfig' => array('filtertype' => 'sales.contract-contact-internal')
            ),
            'contact_external' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Contact Person (external)', // _('Contact Person (external)')
                'options' => array(
                    'controller' => 'Addressbook_Controller_Contact',
                    'filtergroup' => 'Addressbook_Model_ContactFilter',
                    'own_filtergroup' => 'Sales_Model_ContractFilter',
                    'own_controller' => 'Sales_Controller_Contract',
                    'related_model' => 'Addressbook_Model_Contact',
                ),
                'jsConfig' => array('filtertype' => 'sales.contract-contact-external')
            ),
            'products' => array(
                // TODO generalize this for "records" type (Tinebase_Model_Filter_ForeignRecords?)
                'filter' => 'Sales_Model_Filter_ContractProductAggregateFilter',
                'label' => 'Products', // _('Products')
                'jsConfig' => array('filtertype' => 'sales.contract-product')
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
            'description' => array(
                'label'   => 'Description', // _('Description')
                'type'    => 'fulltext',
                'queryFilter' => TRUE,
            ),
            'parent_id'       => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Contract',
                    'idProperty'  => 'id',
                )
            ),
            'billing_address_id' => array(
                'label'      => 'Billing Address', // _('Billing Address')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Address',
                    'idProperty'  => 'id',
                )
            ),
            'start_date' => array(
                'type' => 'datetime',
                'label'      => 'Start Date',    // _('Start Date')
            ),
            'end_date' => array(
                'type' => 'datetime',
                'label'      => 'End Date',    // _('End Date')
            ),
            'customer' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Customer',    // _('Customer')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Customer',
                        'type' => 'CUSTOMER'
                    )
                )
            ),
            'contact_external' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Contact Person (external)',    // _('Contact Person (external)')
                    'config' => array(
                        'appName'   => 'Addressbook',
                        'modelName' => 'Contact',
                        'type' => 'CUSTOMER' // yes, it's the same name of type, but another model than the field before
                    )
                )
            ),
            'contact_internal' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Contact Person (internal)',    // _('Contact Person (internal)')
                    'config' => array(
                        'appName'   => 'Addressbook',
                        'modelName' => 'Contact',
                        'type' => 'RESPONSIBLE'
                    )
                )
            ),
            'costcenter' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Lead Cost Center',    // _('Lead Cost Center')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'CostCenter',
                        'type' => 'LEAD_COST_CENTER'
                    )
                )
            ),
            'products' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label'      => 'Products', // _('Products')
                'type'       => 'records', // be careful: records type has no automatic filter definition!
                'config'     => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'ProductAggregate',
                    'refIdField'  => 'contract_id',
                    'dependentRecords' => TRUE
                ),
            ),

            'fulltext' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'string',
                    'sortable' => false
                )            
            ),

            // TODO what is the purpose of this field? it is not persisted in the db and does not appear in the client
            // TODO can't we just remove it?
            'merge_invoices' => array(
                'type'    => 'boolean',
                'label'   => 'Merge', // _('Merge')
                'default' => false,
                'sortable' => false,
                'shy' => TRUE,
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
        $this->fulltext = $this->number . ' - ' . $this->title;
    }
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        // a contract may have one responsible and one customer but many partners
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'RESPONSIBLE', 'degree' => 'sibling', 'text' => 'Responsible', 'max' => '1:0'), // _('Responsible')
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '1:0'),  // _('Customer')
            array('type' => 'PARTNER', 'degree' => 'sibling', 'text' => 'Partner', 'max' => '0:0'),  // _('Partner')
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Tasks', 'relatedModel' => 'Task', 'config' => array(
            array('type' => 'TASK', 'degree' => 'sibling', 'text' => 'Task', 'max' => '0:0'),
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Product', 'config' => array(
            array('type' => 'PRODUCT', 'degree' => 'sibling', 'text' => 'Product', 'max' => '0:0'),
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'CostCenter', 'config' => array(
            array('type' => 'LEAD_COST_CENTER', 'degree' => 'sibling', 'text' => 'Lead Cost Center', 'max' => '1:0'), // _('Lead Cost Center')
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Timetracker', 'relatedModel' => 'Timeaccount', 'config' => array(
            array('type' => 'TIME_ACCOUNT', 'degree' => 'sibling', 'text' => 'Time Account', 'max' => '0:1'), // _('Time Account')
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Customer', 'config' => array(
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '1:0'), // _('Customer')
            ), 'defaultType' => ''
        ),
    );
    
    /**
     * returns the product aggregate for a given accountable
     * 
     * @param Sales_Model_Accountable_Interface $record
     */
    public function findProductAggregate(Sales_Model_Accountable_Interface $record) {
        
        $accountableClassName = get_class($record);
        $filter = new Sales_Model_ProductFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'accountable', 'operator' => 'equals', 'value' => $accountableClassName)));
        $products = Sales_Controller_Product::getInstance()->search($filter);
        
        $filter = new Sales_Model_ProductAggregateFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'product_id', 'operator' => 'in', 'value' => $products->getId())));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'contract_id', 'operator' => 'equals', 'value' => $this->getId())));

        $pas = Sales_Controller_ProductAggregate::getInstance()->search($filter);
        
        if ($pas->count() < 1) {
            throw new Tinebase_Exception_Data('A contract aggregate could not be found!');
        } elseif ($pas->count() > 1) {
            throw new Tinebase_Exception_Data('At the moment a contract may have only one product aggregate for the same product, not more!');
        }
        
        return $pas->getFirstRecord();
    }
}
