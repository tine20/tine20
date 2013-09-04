<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tasks Filter Class
 * @package Tasks
 */
class Tasks_Model_TaskFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tasks';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tasks_Model_Task';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tasks_Model_TaskFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                   => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'Tasks_Model_Task')),
        'uid'                  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'query'                => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('summary', 'description'))),
        'organizer'            => array('filter' => 'Tinebase_Model_Filter_User'),
        'status'               => array('filter' => 'Tinebase_Model_Filter_Text'),
        'due'                  => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'description'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'summary'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'                  => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'tasks.id',
            'applicationName' => 'Tasks',
        )),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'container_id'         => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Tasks')),
    );
}
