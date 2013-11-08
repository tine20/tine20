<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * this class handles the rights for the crm application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * to add a new right you have to do these 3 steps:
 * - add a constant for the right
 * - add the constant to the $addRights in getAllApplicationRights() function
 * . add getText identifier in getTranslatedRightDescriptions() function
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Sales_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage products
     * @staticvar string
     */
    const MANAGE_PRODUCTS = 'manage_products';
    
    /**
     * the right to manage cost centers
     * @staticvar string
     */
    const MANAGE_COSTCENTERS = 'manage_costcenters';
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Acl_Rights
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() 
    {
    }
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        
    }    
    
    /**
     * the singleton pattern
     *
     * @return Sales_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        
        $allRights = parent::getAllApplicationRights();
        
        $addRights = array ( 
            self::MANAGE_PRODUCTS,
        );
        $allRights = array_merge($allRights, $addRights);
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Sales');
        
        $rightDescriptions = array(
            self::MANAGE_PRODUCTS => array(
                'text'          => $translate->_('manage products'),
                'description'   => $translate->_('add, edit and delete products'),
            ),
            self::MANAGE_COSTCENTERS => array(
                'text'          => $translate->_('manage cost centers'),
                'description'   => $translate->_('add, edit and delete cost centers'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }

    
}
