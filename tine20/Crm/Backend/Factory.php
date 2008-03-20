<?php
/**
 * backend factory class for the Crm
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ *
 */

/**
 * backend factory class for the Crm
 * 
 * an instance of the Crm backendclass should be created using this class
 * 
 * $contacts = Crm_Backend::factory(Crm_Backend::$type);
 * 
 * currently implemented backend classes: Crm_Backend::Sql
 * 
 * @package     Crm
 */
class Crm_Backend_Factory
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
                $className = Crm_Backend_ . ucfirst($type);
                $instance = new $className();
                break;
                
            default:
                throw new Exception('unknown type');
        }

        return $instance;
    }
    
}    
