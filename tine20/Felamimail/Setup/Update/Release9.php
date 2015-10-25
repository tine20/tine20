<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * 
     * add account_id-folder_id index to felamimail_cache_message
     */
    public function update_0()
    {
        $update8 = new Felamimail_Setup_Update_Release8($this->_backend);
        $update8->update_4();
        $this->setApplicationVersion('Felamimail', '9.1');
    }
}
