<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar Resource Filter
 * 
 * @package Calendar
 */
class Calendar_Model_ResourceFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Calendar';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                 => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', 'hierarchy', 'email'))),
        'container_id'          => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('modelName' => Calendar_Model_Resource::class)),
        'id'                    => array('filter' => 'Tinebase_Model_Filter_Id'  ),
        'name'                  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'hierarchy'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'email'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'max_number_of_people'  => array('filter' => 'Tinebase_Model_Filter_Int'),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
        'customfield'          => array('filter' => 'Tinebase_Model_Filter_CustomField', 'options' => array(
            'idProperty' => 'cal_resources.id'
        )),
        'tag'                  => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'cal_resources.id',
            'applicationName' => 'Calendar',
        )),
    );
}
