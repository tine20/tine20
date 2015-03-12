<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * plugin to handle imap settings for univentionMail ldap schema
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Imap_LdapUniventionMailSchema extends Tinebase_EmailUser_Ldap implements Tinebase_EmailUser_Imap_Interface
{
    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailUsername' => 'mailprimaryaddress',
        'emailHost'     => 'univentionmailhomeserver'
    );
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'univentionMail'
    );
    
    protected $_defaults = array(
        'emailPort'   => 143,
        'emailSecure' => Felamimail_Model_Account::SECURE_TLS
    );
}
