<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Asterisk peer sql backend
 *
 * @package     Voipmanager
 * @subpackage  Backend
 */
class Voipmanager_Backend_Asterisk_SipPeer extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'asterisk_sip_peers';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Voipmanager_Model_Asterisk_SipPeer';
    
    /**
     * use subselect in searchCount fn
     * -> can't use subselect because of the regseconds 
     *    (Zend_Db_Statement_Exception: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'regseconds')
     *
     * @var boolean
     */
    protected $_useSubselectForCount = FALSE;
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        // add join only if needed and allowed
        if (($_cols == '*') || (is_array($_cols) && isset($_cols['context']))) {
            $select->joinLeft(
                array('contexts'  => $this->_tablePrefix . 'asterisk_context'),
                'context_id = contexts.id',
                array('context' => 'name')
            );
        }
        
        // add regseconds only if needed and allowed
        if (($_cols == '*') || (is_array($_cols) && isset($_cols['regseconds']))) {
            $select->columns('regseconds');
        }
        
        return $select;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        // special handling, convert to UNIX timestamp
        if(isset($_record['regseconds']) && $_record['regseconds'] instanceof DateTime) {
            $_record['regseconds'] = $_record['regseconds']->getTimestamp();
        }
        $result = parent::_recordToRawData($_record);
        
        // context is joined from the asterisk_context table and can not be set here
        unset($result['context']);
        
        return $result;
    }
}
