<?php
/**
 * backend factory class for Voipmanager Management
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $ *
 */

/**
 * backend factory class for  Voipmanager Management
 * 
 * an instance of the Voipmanager backendclass should be created using this class
 * 
 * 
 * currently implemented backend classes: Voipmanager_Backend::Sql
 * 
 * @package  Voipmanager Management
 */
class Voipmanager_Backend_Phone_Factory
{
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'Sql';
    
  /**
     * factory function to return a selected contacts backend class
     *
     * @param string $type
     * @return object
     */
    static public function factory($type)
    {
        switch($type) {
            case self::SQL:
                $className = 'Voipmanager_Backend_Phone_' . ucfirst($type);
                $instance = new $className();
                break;
                
            default:
                throw new Exception('unknown type');
        }

        return $instance;
    }
    
}    
