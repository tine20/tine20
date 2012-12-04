<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_ContainerOwner
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters by container owner
 */
class Tinebase_Model_Filter_ContainerOwner extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
    );
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();
        $correlationName = Tinebase_Record_Abstract::generateUID() . $this->_value . 'owner';
        // use only last 30 chars (oracle column name limit)
        $correlationName = substr($correlationName, -30);
        
        $_select->joinLeft(
            /* table  */ array($correlationName => SQL_TABLE_PREFIX . 'container_acl'), 
            /* on     */ $db->quoteIdentifier("{$correlationName}.container_id") . " = " . $db->quoteIdentifier("container.id"),
            /* select */ array()
        );
        
        // only personal containers have an owner!
        $_select->where("{$db->quoteIdentifier('container.type')} = ?", Tinebase_Model_Container::TYPE_PERSONAL);
        
        // assure admin grant
        $_select->where($db->quoteIdentifier("{$correlationName}.account_id") . " = " . $db->quote($this->_value) . ' AND ' .
            $db->quoteIdentifier("{$correlationName}.account_grant") . " = ?", Tinebase_Model_Grants::GRANT_ADMIN);
    }
}
