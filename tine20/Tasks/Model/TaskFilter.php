<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                   => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'                => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('summary', 'description'))),
        'organizer'            => array('filter' => 'Tinebase_Model_Filter_User'),
        'status_id'            => array('filter' => 'Tinebase_Model_Filter_Int'),
        'due'                  => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'description'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'summary'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'                  => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array('idProperty' => 'tasks.id')),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'container_id'         => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Tasks')),
        //'showClosed'           => array('custom' => true),
    );
    
    /**
     * appends custom filters to a given select object
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @return void
     * 
     * @todo    add status & organizer filters
     *
    public function appendFilterSql($_select, $_backend)
    {
        $db = Tinebase_Core::getDb();
        
        $showClosed = false;
        foreach ($this->_customData as $customData) {
            if ($customData['field'] == 'showClosed' && $customData['value'] == true) {
                $showClosed = true;
            }
        }
        
        if($showClosed){
            // nothing to filter
        } else {
            $where = $db->quoteInto($db->quoteIdentifier('status.status_is_open') . ' = ?', 1, Zend_Db::INT_TYPE) .
                     ' OR ' . $db->quoteIdentifier('tasks.status_id') . ' IS NULL';
            $_select->where($where);
        }
    }
    */
}
