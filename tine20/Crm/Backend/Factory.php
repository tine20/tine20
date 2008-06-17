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
 * @package     Crm
 */
class Crm_Backend_Factory
{
	/**
	 * object instance
	 *
	 * @var Crm_Backend_Factory
	 */
	private static $_instance = NULL;
	
	
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'Sql';
    
    const LEADS = 'Leads';
    
    const PRODUCTS = 'Products';
    
    const LEAD_TYPES = 'LeadTypes';
    
    const LEAD_SOURCES = 'LeadSources';
    
    const LEAD_STATES = 'LeadStates';
    
    
    /**
     * Constructor
     * 
     * Declared as protected to prevent instantiating from outside.
     */
    protected function __construct() {}
    
    public static function getInstance() {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Backend_Factory();
        }
        
        return self::$_instance;
    }
    

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
                $instance = Crm_Backend_Sql::getInstance();
                break;
                           
            case self::LEADS:
                $instance = Crm_Backend_Leads::getInstance();
                break;
                           
            case self::PRODUCTS:
            	$instance = Crm_Backend_Products::getInstance();
                break;
                           
            case self::LEAD_TYPES:
            	$instance = Crm_Backend_LeadTypes::getInstance();
                break;
                           
            case self::LEAD_SOURCES:
            	$instance = Crm_Backend_LeadSources::getInstance();
                break;
                
            case self::LEAD_STATES:
                $instance = Crm_Backend_LeadStates::getInstance();
                break;
            
            default:
                throw new Exception('unknown type');
        }

        return $instance;
    }
    
}    
