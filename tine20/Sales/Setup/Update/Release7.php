<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Sales_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * - add seq
     * 
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function update_0()
    {
        $seqModels = array(
            'Sales_Model_Contract'     => array('name' => 'sales_contracts',    'version' => 5),
            'Sales_Model_Product'      => array('name' => 'sales_products',     'version' => 3),
        );
        
        $declaration = Tinebase_Setup_Update_Release7::getRecordSeqDeclaration();
        foreach ($seqModels as $model => $tableInfo) {
            try {
                $this->_backend->addCol($tableInfo['name'], $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                // ignore
            }
            $this->setTableVersion($tableInfo['name'], $tableInfo['version']);
            Tinebase_Setup_Update_Release7::updateModlogSeq($model, $tableInfo['name']);
        }
        
        $this->setApplicationVersion('Sales', '7.1');
    }
    
    /**
     * update to 7.2
     * 
     * get all contracts with linked Sales_Model_CostCenter and set type for all to LEAD_COST_CENTER
     * 
     * @see 0008532: Fix relations constrains handling
     */
    public function update_1()
    {
        $where = '(' 
            . $this->_db->quoteInto($this->_db->quoteIdentifier('related_model') . ' = ?', 'Sales_Model_CostCenter')
            . ' AND '
            . $this->_db->quoteInto($this->_db->quoteIdentifier('own_model') . ' = ?', 'Sales_Model_Contract') 
        . ') OR ('
            . $this->_db->quoteInto($this->_db->quoteIdentifier('own_model') . ' = ?', 'Sales_Model_CostCenter')
            . ' AND '
            . $this->_db->quoteInto($this->_db->quoteIdentifier('related_model') . ' = ?', 'Sales_Model_Contract') 
        . ')';
        $this->_db->update(SQL_TABLE_PREFIX . 'relations', array('type' => 'LEAD_COST_CENTER'), $where);
        
        $this->setApplicationVersion('Sales', '7.2');
    }

    /**
     * update to 8.0
     *
     * @return void
     */
    public function update_2()
    {
        $this->setApplicationVersion('Sales', '8.0');
    }
}
