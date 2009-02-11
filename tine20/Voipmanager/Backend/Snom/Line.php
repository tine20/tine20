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
        parent::__construct(Tinebase_Core::get('voipdbTablePrefix') . 'snom_lines', 'Voipmanager_Model_Snom_Line', $_db);
    }
    
    /**
     * delete lines(s) identified by phone id
     *
     * @param string|Voipmanager_Model_Snom_Phone $_id
     */
    public function deletePhoneLines($_id)
    {
        $phoneId = Voipmanager_Model_Snom_Phone::convertSnomPhoneIdToInt($_id);
        $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('snomphone_id') . ' = ?', $phoneId);

        $this->_db->delete(Tinebase_Core::get('voipdbTablePrefix') . 'snom_lines', $where);
    }    
}
