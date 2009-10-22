<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
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
            self::$_instance = new Sales_Acl_Rights;
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
    private function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Sales');
        
        $rightDescriptions = array(
            self::MANAGE_PRODUCTS  => array(
                'text'          => $translate->_('manage products'),
                'description'   => $translate->_('add, edit and delete products'),
            ),
        );
        
        return $rightDescriptions;
    }

    /**
     * get right description
     * 
     * @param   string right
     * @return  array with text + description
     */
    public function getRightDescription($_right)
    {        
        $result = parent::getRightDescription($_right);
        
        $rightDescriptions = self::getTranslatedRightDescriptions();
        
        if ( isset($rightDescriptions[$_right]) ) {
            $result = $rightDescriptions[$_right];
        }

        return $result;
    }
    
}
