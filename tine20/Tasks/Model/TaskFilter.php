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
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('summary', 'description'))),
        'organizer'            => array('filter' => 'Tinebase_Model_Filter_User'),
        'status'               => array('filter' => 'Tinebase_Model_Filter_Int'),
        'due'                  => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'description'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'summary'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'                  => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array('idProperty' => 'tasks.id')),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_Date'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'container_id'         => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Tasks')),
        'showClosed'           => array('custom' => true),
    );
    
    /**
     * appends current filters to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     * 
     * @todo    add status & organizer filters
     */
    public function appendFilterSql($_select)
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
                     ' OR ' . $db->quoteIdentifier('status_id') . ' IS NULL';
            $_select->where($where);
        }
        
        parent::appendFilterSql($_select);
    }

}
