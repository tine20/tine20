<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Notification
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * notifications interface
 *
 * @package     Tinebase
 * @subpackage  Notification
 */
interface Tinebase_Notification_Interface
{
   /**
     * send a notification
     *
     * @param Tinebase_Model_FullUser   $_updater
     * @param Addressbook_Model_Contact $_recipient
     * @param string                    $_subject the subject
     * @param string                    $_messagePlain the message as plain text
     * @param string                    $_messageHtml the message as html
     * @param string|array              $_attachements
     */
    public function send($_updater, Addressbook_Model_Contact $_recipient, $_subject, $_messagePlain, $_messageHtml = NULL, $_attachements = NULL);
}
