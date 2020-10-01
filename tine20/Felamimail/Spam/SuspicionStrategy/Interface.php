<?php
/**
 * spam suspicion strategy interface for the felamimail
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * spam suspicion strategy interface  for the felamimail
 *
 * @package     Felamimail
 */
Interface Felamimail_Spam_SuspicionStrategy_Interface
{
    /**
     * @param Felamimail_Model_Message $message
     * @return mixed
     */

    public function apply(Felamimail_Model_Message $message);
}