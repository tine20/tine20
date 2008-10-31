<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend to handle phone lines
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Line extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     * 
     * @param Zend_Db_Adapter_Abstract $_db
     */
    public function __construct($_db = NULL)
    {
        parent::__construct(SQL_TABLE_PREFIX . 'snom_lines', 'Voipmanager_Model_SnomLine', $_db);
    }
    
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select $_select current where filter
     * @param  Voipmanager_Model_SnomLineFilter $_filter the filter values to search for
     */
    protected function _addFilter(Zend_Db_Select $_select, Voipmanager_Model_SnomLineFilter $_filter)
    {
        if(!empty($_filter->snomphone_id)) {
            $_select->where($this->_db->quoteInto('snomphone_id = ?', $_filter->snomphone_id));
        }
    }               

    /**
     * delete lines(s) identified by phone id
     *
     * @param string|Voipmanager_Model_SnomPhone $_id
     */
    public function deletePhoneLines($_id)
    {
        $phoneId = Voipmanager_Model_SnomPhone::convertSnomPhoneIdToInt($_id);
        $where[] = $this->_db->quoteInto('snomphone_id = ?', $phoneId);

        $this->_db->delete(SQL_TABLE_PREFIX . 'snom_lines', $where);
    }    
}
