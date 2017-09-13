<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Addressbook_Model_ListFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
 */
class Addressbook_Model_ListFilter extends Tinebase_Model_Filter_FilterGroup
{
    protected $_configuredModel = 'Addressbook_Model_List';

    
    /**
     * @var array filter model fieldName => definition
     *
    protected $_filterModel = array(

        'path'                => array(
            'filter' => 'Tinebase_Model_Filter_Path',
            'options' => array()
        ),

        'container_id'         => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Addressbook')),


        'showHidden'           => array('filter' => 'Addressbook_Model_ListHiddenFilter'),
        'contact'              => array('filter' => 'Addressbook_Model_ListMemberFilter'),
        'customfield'          => array('filter' => 'Tinebase_Model_Filter_CustomField', 'options' => array(
            'idProperty' => 'addressbook_lists.id'
        )),
    );*/
}
