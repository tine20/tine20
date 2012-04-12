<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Addressbook_Model_ContactFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
 * 
 * @todo add bday filter
 */
class Addressbook_Model_ContactFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Addressbook_Model_ContactFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Addressbook_Model_Contact';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                   => array('filter' => 'Addressbook_Model_ContactIdFilter', 'options' => array('modelName' => 'Addressbook_Model_Contact')),
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array('n_family', 'n_given', 'org_name', 'org_unit', 'email', 'email_home', 'adr_one_locality'))
        ),
        'list'                 => array('filter' => 'Addressbook_Model_ListMemberFilter'),
        'telephone'            => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array(
                'tel_assistent',
                'tel_car',
                'tel_cell',
                'tel_cell_private',
                'tel_fax',
                'tel_fax_home',
                'tel_home',
                'tel_other',
                'tel_pager',
                'tel_prefer',
                'tel_work'
            ))
        ),
        'email_query'          => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array(
                'email',
                'email_home',
            ))
        ),
        'n_given'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'n_family'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'n_fileas'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'n_prefix'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'n_suffix'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'org_name'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'org_unit'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'room'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'title'                => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_one_street'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_one_region'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_one_postalcode'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_one_locality'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_one_countryname'  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_two_street'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_two_region'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_two_postalcode'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_two_locality'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_two_countryname'  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'email'                => array('filter' => 'Tinebase_Model_Filter_Text'),
        'email_home'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_assistent'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_car'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_cell'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_cell_private'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_fax'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_fax_home'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_home'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_pager'            => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_work'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tel_prefer'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'note'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'role'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'pubkey'               => array('filter' => 'Tinebase_Model_Filter_Text'),
        'assistent'            => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'                  => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'addressbook.id',
            'applicationName' => 'Addressbook',
        )),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
        'container_id'         => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Addressbook')),
        'type'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'customfield'          => array('filter' => 'Tinebase_Model_Filter_CustomField', 'options' => array('idProperty' => 'addressbook.id')),
        'showDisabled'         => array('filter' => 'Addressbook_Model_ContactDisabledFilter', 'options' => array(
            'requiredCols'  => array('account_id' => 'accounts.id')
        )),
        
        //'bday'               => array('filter' => 'Tinebase_Model_Filter_Date'),
    );
}
