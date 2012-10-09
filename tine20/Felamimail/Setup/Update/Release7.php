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
            // allready renamed
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        $this->setApplicationVersion('Felamimail', '7.1');
    }
}  