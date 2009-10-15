<?php
/**
 * backend factory class for the Crm
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ *
 * 
 * @deprecated
 * @todo        remove this
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
     * backend object instances
     */
	private static $_backends = array();
	
	
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
    
    const LEAD_PRODUCTS = 'LeadProducts';
    
    /**
     * factory function to return a selected contacts backend class
     *
     * @param string $type
     * @return object
     * @throws  Crm_Exception_InvalidArgument
     */
    static public function factory($_type)
    {
        switch($_type) {
            case self::SQL:
            	if (!isset(self::$_backends[$_type])) {
            		self::$_backends[$_type] = new Crm_Backend_Sql();
            	}
            	$instance = self::$_backends[$_type];
                break;
                           
            case self::LEADS:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Crm_Backend_Leads();
                }
                $instance = self::$_backends[$_type];
                break;
                           
            case self::PRODUCTS:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Crm_Backend_Products();
                }
                $instance = self::$_backends[$_type];
                break;
                           
            case self::LEAD_TYPES:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Crm_Backend_LeadTypes();
                }
                $instance = self::$_backends[$_type];
                break;
                           
            case self::LEAD_SOURCES:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Crm_Backend_LeadSources();
                }
                $instance = self::$_backends[$_type];
                break;
                
            case self::LEAD_STATES:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Crm_Backend_LeadStates();
                }
                $instance = self::$_backends[$_type];
                break;
            
            case self::LEAD_PRODUCTS:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Crm_Backend_LeadProducts();
                }
                $instance = self::$_backends[$_type];
                break;
                
            default:
                throw new Crm_Exception_InvalidArgument('Unknown type (' . $_type . ').');
        }

        return $instance;
    }
}    
