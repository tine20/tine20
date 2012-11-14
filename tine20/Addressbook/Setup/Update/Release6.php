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
class Addressbook_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update to 6.1
     * - remove fritzbox export
     * 
     * @see 0006948: Export a contact as adb_fritzbox
     */
    public function update_0()
    {
        try {
            $fritzex = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_fritzbox');
            Tinebase_ImportExportDefinition::getInstance()->delete($fritzex->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            // does not exist
        }
        
        $this->setApplicationVersion('Addressbook', '6.1');
    }
    
    /**
    * update to 7.0
    *
    * @return void
    */
    public function update_1()
    {
        $this->setApplicationVersion('Addressbook', '7.0');
    }
}
