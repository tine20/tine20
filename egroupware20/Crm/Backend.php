<?php
/**
 * backend factory class for the Crm
 * 
 * a instance of the Crm backendclass should be created using this class
 * 
 * $contacts = Crm_Backend::factory(Crm_Backend::$type);
 * 
 * currently implemented backend classes: Crm_Backend::Sql
 * 
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Backend.php 121 2007-10-15 16:30:00Z twadewitz $ *
 */
class Crm_Backend
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
