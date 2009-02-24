<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Addressbook_Model_ContactFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
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
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                   => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array('n_family', 'n_given', 'org_name', 'email', 'adr_one_locality'))
        ),
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
        'n_given'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'n_family'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'org_name'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'title'                => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_one_street'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_one_postalcode'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_one_locality'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_two_street'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_two_postalcode'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'adr_two_locality'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'role'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'                  => array('filter' => 'Tinebase_Model_Filter_Tag'),
        //'bday'               => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'container_id'         => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Addressbook')),
    );
    
}
