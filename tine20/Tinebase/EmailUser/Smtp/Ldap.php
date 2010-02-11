<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add domain check for aliases
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email User Settings Managing for dbmail (+ ...) postfix attributes in ldap backend
 * 
 * @package Tinebase
 * @subpackage Ldap
 */
class Tinebase_EmailUser_Smtp_Ldap extends Tinebase_EmailUser_Ldap
{
    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_userPropertyNameMapping = array(
        'emailAliases'          => 'mailalternateaddress', 
        'emailForwards'         => 'mailforwardingaddress', 
    );
}  
