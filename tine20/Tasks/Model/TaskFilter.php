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
class Tasks_Model_TaskFilter extends Tinebase_Record_AbstractFilter
{    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tasks';

    /**
     * the constructor
     * it is needed because we have more validation fields in Tasks
     * 
     * @param mixed $_data
     * @param bool $bypassFilters sets {@see this->bypassFilters}
     * @param bool $convertDates sets {@see $this->convertDates}
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // add more filters
        $this->_validators = array_merge($this->_validators, array(
            'organizer'            => array('allowEmpty' => true           ),
            'status'               => array('allowEmpty' => true           ),
            'due'                  => array('allowEmpty' => true           ),
            'tag'                  => array('allowEmpty' => true           ),
            'description'          => array('allowEmpty' => true           ),              
            'summary'              => array('allowEmpty' => true           ),              
        
        // 'special' defines a filter rule that doesn't fit into the normal operator/opSqlMap model 
            'showClosed'           => array('allowEmpty' => true, 'InArray' => array(true,false), 'special' => TRUE),
        ));
        
        // define query fields
        $this->_queryFields = array(
            'description',
            'summary',
        );
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
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
        
        if(isset($this->showClosed) && $this->showClosed){
            // nothing to filter
        } else {
            $_select->where($db->quoteIdentifier('status.status_is_open') . ' = TRUE OR ' . 
                    $db->quoteIdentifier('tasks.status_id') . ' IS NULL');
        }
        
        /*
        if(!empty($_filter->status)){
            $_select->where($this->_db->quoteInto($db->quoteIdentifier('tasks.status_id') . ' = ?',$_filter->status));
        }
        if(!empty($_filter->organizer)){
            $_select->where($this->_db->quoteInto($db->quoteIdentifier('tasks.organizer') . ' = ?', (int)$_filter->organizer));
        }
        */
        
        parent::appendFilterSql($_select);
    }
}
