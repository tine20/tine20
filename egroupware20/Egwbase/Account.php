<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Accounts
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Account Class
 *
 */
class Egwbase_Account
{
    const SQL = 'sql';
    
    const LDAP = 'ldap';
    
    /**
     * return a instance of the current accounts backend
     *
     * @return Egwbase_Account_Sql
     */
    public static function getBackend() 
    {
        // to be read from config backend later
        $type = self::SQL;
        
        switch($type) {
            case self::LDAP:
                $result = Egwbase_Account_Ldap::getInstance();
                break;
                
            case self::SQL:
                $result = Egwbase_Account_Sql::getInstance();
                break;
            
            default:
                throw new Exception('accounts backend type not implemented');
        }
        
        return $result;
    }
}