<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend to handle phone lines
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Line extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'snom_lines';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Voipmanager_Model_Snom_Line';

    /**
     * delete lines(s) identified by phone id
     *
     * @param string|Voipmanager_Model_Snom_Phone $_id
     */
    public function deletePhoneLines($_id)
    {
        $phoneId = Voipmanager_Model_Snom_Phone::convertSnomPhoneIdToInt($_id);
        $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('snomphone_id') . ' = ?', $phoneId);

        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }    
}
