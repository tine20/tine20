<?php
/**
 * Tine 2.0
 *
 * @package     SimpleFAQ
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class SimpleFAQ_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * - add seq
     * 
     * @see 0008602: Hinzufügen von Tags in der FAQ mit Rechtsklick erzeugt Fehler
     */
    public function update_0()
    {
        $seqModels = array(
            'SimpleFAQ_Model_Faq'    => array('name' => 'simple_faq',    'version' => 2),
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
        
        $this->setApplicationVersion('SimpleFAQ', '7.1');
    }

    /**
     * update to 8.0
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('SimpleFAQ', '8.0');
    }
}
