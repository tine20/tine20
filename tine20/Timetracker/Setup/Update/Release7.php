<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Timetracker_Setup_Update_Release7 extends Setup_Update_Abstract
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
            'Timetracker_Model_Timeaccount'    => array('name' => 'timetracker_timeaccount',    'version' => 8),
            'Timetracker_Model_Timesheet'      => array('name' => 'timetracker_timesheet',      'version' => 3),
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
        
        $this->setApplicationVersion('Timetracker', '7.1');
    }
    
    /**
     * update to 7.1
     * 
     *  - add cleared_at field to timeaccount
     */
    
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cleared_at</name>
                <type>datetime</type>
            </field>
        ');

        $this->_backend->addCol('timetracker_timeaccount', $declaration);
        
        $this->setTableVersion('timetracker_timeaccount', 9);
        $this->setApplicationVersion('Timetracker', '7.2');
    }

    /**
     * update to 8.0
     *
     * @return void
     */
    public function update_2()
    {
        $this->setApplicationVersion('Timetracker', '8.0');
    }
}
