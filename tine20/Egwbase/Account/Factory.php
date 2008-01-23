<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Accounts
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Account factory class
 *
 * @package     Egwbase
 * @subpackage  Accounts
 */
class Egwbase_Account_Factory
{
    const SQL = 'sql';
    
    const LDAP = 'ldap';
    
    /**
     * return a instance of the current accounts backend
     *
     * @return Egwbase_Account_Interface
     */
    public static function getBackend($_backendType) 
    {
        switch($_backendType) {
            case self::LDAP:
                $result = Egwbase_Account_Ldap::getInstance();
                break;
                
            case self::SQL:
                $result = Egwbase_Account_Sql::getInstance();
                break;
            
            default:
                throw new Exception("accounts backend type $_backendType not implemented");
        }
        
        return $result;
    }
}