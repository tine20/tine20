<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Addressbook_Setup_Update_Release7 extends Setup_Update_Abstract
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
            $this->_backend->addCol('addressbook', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // ignore
        }
        $this->setTableVersion('addressbook', 17);
        
        // update contacts: set max seq from modlog + update modlog
        Tinebase_Setup_Update_Release7::updateModlogSeq('Addressbook_Model_Contact', 'addressbook');
        
        $this->setApplicationVersion('Addressbook', '7.1');
    }
    
    /**
     * update to 8.0
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Addressbook', '8.0');
    }

    /**
     * update to 8.0
     * 
     * - it looks like we had a 7.2 version in the adb for a short time. this should fix it.
     * 
     * @see 0008952: Update von 7.2 auf 8.0 nicht möglich
     *
     * @return void
     */
    public function update_2()
    {
        $this->setApplicationVersion('Addressbook', '8.0');
    }
}
