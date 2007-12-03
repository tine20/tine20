<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Accounts
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Controller.php 273 2007-11-08 22:51:16Z lkneschke $
 */
 
/**
 * controller for Admin
 */
class Egwbase_Account_Factory
{
    const SQL = 'sql';
    
    const LDAP = 'ldap';
    
    /**
     * this function implements the factory pattern
     *
     * @param string $type the type of accounts class to return
     * @return Egwbase_Account_Sql|Egwbase_Account_Ldap
     */
    static public function factory($type)
    {
        switch($type) {
            case self::LDAP:
                $result = Egwbase_Account_Ldap::getInstance();
                break;
                
            case self::SQL:
                $result = Egwbase_Account_Sql::getInstance();
                break;
            
            default:
                throw new Exception('backend type not implemented');
        }
        
        return $result;
    }
}