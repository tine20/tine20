<?php
/**
 * controller for CRM application
 * 
 * the main logic of the CRM application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Controller.php 273 2007-11-08 22:51:16Z lkneschke $
 *
 */
class Crm_Controller
{
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
     * @var Crm_Controller
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Crm_Controller;
        }
        
        return self::$instance;
    }

    /**
     * get lead sources
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getLeadsources($_sort, $_dir)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);       
        $result = $backend->getLeadsources($_sort, $_dir);

        return $result;    
    }

    /**
     * save leadsources
     *
     * if $_Id is -1 the options element gets added, otherwise it gets updated
     * this function handles insert and updates as well as deleting vanished items
     *
     * @return array
     */ 
    public function saveLeadsources(Egwbase_Record_Recordset $_leadSources)
    {
          $daten = $_leadSources->toArray();
          
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        $backend->saveLeadsources($_leadSources);
    }  
    
    /**
     * get lead types
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getLeadtypes($_sort, $_dir)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);       
        $result = $backend->getLeadtypes($_sort, $_dir);

        return $result;    
    }    
    
    /**
     * get products available
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getProductsAvailable($_sort, $_dir)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);       
        $result = $backend->getProductsAvailable($_sort, $_dir);

        return $result;    
    }     

    /**
     * get project states
     *
     * @param string $_sort
     * @param string $_dir
     * @return array
     */
    public function getProjectstates($_sort, $_dir)
    {
        $backend = Crm_Backend::factory(Crm_Backend::SQL);       
        $result = $backend->getProjectstates($_sort, $_dir);

        return $result;    
    }     

    
}
