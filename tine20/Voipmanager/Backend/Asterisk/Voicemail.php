<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * Asterisk voicemail sql backend
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Asterisk_Voicemail extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'asterisk_voicemail';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Voipmanager_Model_Asterisk_Voicemail';
    
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
        $result = parent::_recordToRawData($_record);
        
        // context is joined from the asterisk_context table and can not be set here
        unset($result['context']);
        
        return $result;
    }
}
