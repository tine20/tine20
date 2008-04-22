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
 * primary class to handle groups
 *
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group
{
    const SQL = 'Sql';
    
    const LDAP = 'Ldap';

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Group
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Group_Abstract
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            if(isset(Zend_Registry::get('configFile')->accounts)) {
                $backendType = Zend_Registry::get('configFile')->accounts->get('backend', self::SQL);
                $backendType = ucfirst($backendType);
            } else {
                $backendType = self::SQL;
            }
            
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' groups backend: ' . $backendType);

            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }
        
    /**
     * return an instance of the current groups backend
     *
     * @param string $_backendType name of the groups backend
     * @return Tinebase_Group_Abstract
     */
    public static function factory($_backendType) 
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