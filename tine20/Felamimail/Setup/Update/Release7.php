<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * Rename table felamimail_cache_message_flag
     * 
     */
    public function update_0()
    {
        try {
            $this->renameTable('felamimail_cache_message_flag', 'felamimail_cache_msg_flag');
            $this->setTableVersion('felamimail_cache_msg_flag', '2', TRUE, 'Felamimail');
        } catch (Exception $e) {
            // already renamed
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }
        
        $this->setApplicationVersion('Felamimail', '7.1');
    }
    
    /**
     * update to 7.2
     * - add seq
     * 
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function update_1()
    {
        $declaration = Tinebase_Setup_Update_Release7::getRecordSeqDeclaration();
        try {
            $this->_backend->addCol('felamimail_account', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // ignore
        }
        $this->setTableVersion('felamimail_account', 19);
        
        Tinebase_Setup_Update_Release7::updateModlogSeq('Felamimail_Model_Account', 'felamimail_account');
        
        $this->setApplicationVersion('Felamimail', '7.2');
    }
    
    /**
     * update to 7.3
     * 
     * - rule id needs to be an integer
     * 
     * @see 0007240: order of sieve rules changes when vacation message is saved 
     */
    public function update_2()
    {
        if ($this->getTableVersion('felamimail_sieve_rule') != 2) {
            $release6 = new Felamimail_Setup_Update_Release6($this->_backend);
            $release6->update_3();
        }
        
        $this->setApplicationVersion('Felamimail', '7.3');
    }
}
