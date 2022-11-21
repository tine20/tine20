<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application uninitialization
 *
 * @package     Felamimail
 * @subpackage  Setup
 */
class Felamimail_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninit scheduler tasks
     */
    protected function _uninitializeSchedulerTasks()
    {
        Tinebase_Core::getScheduler()->removeTask('FelamimailPruneAttachmentCache');
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task FelamimailPruneAttachmentCache from scheduler.');
    }

    /**
     * uninit record observers
     */
    protected function _uninitializeRecordObservers()
    {
        Tinebase_Record_PersistentObserver::getInstance()->removeObserverByIdentifier('DeleteMessageFileLocation');
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed persistent observer DeleteMessageFileLocation.');
    }
}
