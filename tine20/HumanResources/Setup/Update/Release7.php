<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class HumanResources_Setup_Update_Release7 extends Setup_Update_Abstract
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
            'HumanResources_Model_Contract'     => array('name' => 'humanresources_contract',    'version' => 3),
            'HumanResources_Model_Employee'     => array('name' => 'humanresources_employee',    'version' => 6),
            'HumanResources_Model_FreeTime'     => array('name' => 'humanresources_freetime',    'version' => 3),
            'HumanResources_Model_WorkingTime'  => array('name' => 'humanresources_workingtime', 'version' => 2),
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
        
        $this->setApplicationVersion('HumanResources', '7.1');
    }
}
