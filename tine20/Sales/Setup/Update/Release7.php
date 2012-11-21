<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
}
