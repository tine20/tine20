<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle
 * */

/**
 * xprops facade for accessing email user plugins via records with email userid xprops
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_XpropsFacade
{
    /**
     * record xprops for email user ids (for example in dovecot/postfix sql)
     */
    const XPROP_EMAIL_USERID_IMAP = 'emailUserIdImap';
    const XPROP_EMAIL_USERID_SMTP = 'emailUserIdSmtp';

    /**
     * @param $records
     * @param $controller
     *
     * TODO finish implementation
     */
    public function convertExistingUsers($records, $controller)
    {
        foreach ($records as $record) {
            //-- get imap user
            //-- get smtp user
            //-- save in record
        }
    }
}
