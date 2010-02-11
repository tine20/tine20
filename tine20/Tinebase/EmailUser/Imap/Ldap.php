<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Ldap.php 10296 2009-09-02 14:12:35Z p.schuele@metaways.de $
 * 
 * @todo        how to get emailGID / dbmailGID?
 * @todo        add Tinebase_EmailUser_Smtp_Ldap with forward / alias
 * @todo        add other schemas (qmail, ...)?
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email User Settings Managing for dbmail (+ ...) attributes in ldap backend
 * 
 * @package Tinebase
 * @subpackage Ldap
 */
class Tinebase_EmailUser_Imap_Ldap extends Tinebase_EmailUser_Ldap
{

    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
        'emailUID'          => 'dbmailuid', 
        'emailGID'          => 'dbmailgid', 
        'emailMailQuota'    => 'mailquota',
    /*
        'emailUID'          => 'dbmailUID', 
        'emailGID'          => 'dbmailGID', 
        'emailMailQuota'    => 'mailQuota',
        */
    );
}
