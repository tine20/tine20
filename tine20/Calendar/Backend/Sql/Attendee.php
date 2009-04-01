<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * native tine 2.0 events sql backend attendee class
 *
 * @package Calendar
 */
class Calendar_Backend_Sql_Attendee extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'cal_attendee';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Calendar_Model_Attendee';
    
    /**
     * Appends foreign records managed by this backend to given recordSet into given property
     *
     * @param Tinebase_Record_RecordSet $_recordSet          record set where foreign records should be appended to
     * @param string                    $_rsKeyProperty      property in $_recordSet where the keys are in
     * @param string                    $_rsValueProperty    property in $_recordSet where foreign records should be stored under
     * @param string                    $_foreignKeyProperty foreign key property of this backend
     */
    public function appendForeignRecords($_recordSet, $_rsKeyProperty, $_rsValueProperty, $_foreignKeyProperty)
    {
        $keyValues = $_recordSet->$_rsKeyProperty;
        if (empty($keyValues)) {
            $keyValues = array('');
        }
        
        $select = $this->_getSelect();
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.' . $_keyColumn) . '= ?', $keyValues);
        
        $rows = $this->_db->fetchAll($select);
        foreach ((array)$rows as $row) {
            //$_recordSet->
        }
    }
    
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     *
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        return $select;
    }
    */
    
}