<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Johannes Nohl <lab@nohl.eu>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a eM Client 7 (beta) vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_EMClient7 extends Addressbook_Convert_Contact_VCard_EMClient
{
    // eM Client 7 (beta) user agent is "MailClient/7.0.25432.0"
    const HEADER_MATCH = '/MailClient\/(?P<version>.*)/';

}
