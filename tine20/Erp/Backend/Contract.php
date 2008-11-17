<?php
/**
 * Tine 2.0
 *
 * @package     Erp
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Leads.php 5174 2008-10-31 14:09:45Z p.schuele@metaways.de $
 */


/**
 * backend for contracts
 *
 * @package     Erp
 * @subpackage  Backend
 */
class Erp_Backend_Contract extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'erp_contracts', 'Erp_Model_Contract');
    }

    /************************ helper functions ************************/

    /**
     * add the fields to search for to the query
     *
     * @param   Zend_Db_Select           $_select current where filter
     * @param   Erp_Model_ContractFilter  $_filter the string to search for
     * 
     * @todo    add container filter later
     */
    protected function _addFilter(Zend_Db_Select $_select, Erp_Model_ContractFilter $_filter)
    {
        //$_select->where($this->_db->quoteInto('container_id IN (?)', $_filter->container));
                        
        if (!empty($_filter->query)) {
            $_select->where($this->_db->quoteInto('(' . $this->_db->quoteIdentifier('title') . ' LIKE ? OR ' .
                    $this->_db->quoteIdentifier('description') . ' LIKE ? OR ' .
                    $this->_db->quoteIdentifier('number') . ' LIKE ?)', '%' . $_filter->query . '%'));
        }
    }
}
