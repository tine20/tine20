<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Group factory class
 * 
 * this class is responsible for returning the right group backend
 *
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_Factory
{
    const SQL = 'Sql';
    
    const LDAP = 'Ldap';
    
    /**
     * return an instance of the current accounts backend
     *
     * @return Tinebase_Group_Interface
     */
    public static function getBackend($_backendType) 
    {
        switch($_backendType) {
            case self::LDAP:
                $options = Zend_Registry::get('configFile')->accounts->get('ldap')->toArray();
                unset($options['userDn']);
                unset($options['groupsDn']);
                
                $result = Tinebase_Group_Ldap::getInstance($options);
                break;
                
            case self::SQL:
                $result = Tinebase_Group_Sql::getInstance();
                break;
            
            default:
                throw new Exception("groups backend type $_backendType not implemented");
        }
        
        return $result;
    }
}