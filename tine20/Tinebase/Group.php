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
    
    const TYPO3 = 'Typo3';

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
     * holds the instance of the singleton
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
        $backendType = Tinebase_User::getConfiguredBackend();
        if (self::$_instance === NULL) {
            $backendType = Tinebase_User::getConfiguredBackend();
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' groups backend: ' . $backendType);

            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }
        
    /**
     * return an instance of the current groups backend
     *
     * @param   string $_backendType name of the groups backend
     * @return  Tinebase_Group_Abstract
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function factory($_backendType) 
    {
        switch($_backendType) {
            case self::LDAP:
                $options = Tinebase_User::getBackendConfiguration();
                $result = new Tinebase_Group_Ldap($options);
                break;
                
            case self::SQL:
                $result = new Tinebase_Group_Sql();
                break;
            
            case self::TYPO3:
                $result = new Tinebase_Group_Typo3();
                break;
                
            default:
                throw new Tinebase_Exception_InvalidArgument("Groups backend type $_backendType not implemented.");
        }
        
        return $result;
    }
}
