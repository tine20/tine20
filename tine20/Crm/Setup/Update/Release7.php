<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Crm_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * - add seq
     * 
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function update_0()
    {
        $declaration = Tinebase_Setup_Update_Release7::getRecordSeqDeclaration();
        try {
            $this->_backend->addCol('metacrm_lead', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // ignore
        }
        $this->setTableVersion('metacrm_lead', 7);
        
        Tinebase_Setup_Update_Release7::updateModlogSeq('Crm_Model_Lead', 'metacrm_lead');
        
        $this->setApplicationVersion('Crm', '7.1');
    }
}
