<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        create factory for the controllers?
 */

/**
 * abstract controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller_Abstract
{
    /**
     * @var Tinebase_Model_User
     */
    protected $_currentAccount;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_currentAccount = Zend_Registry::get('currentAccount');        
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holdes the instance of the singleton
     *
     * @var Admin_Controller_Abstract
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Admin_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller;
        }
        
        return self::$_instance;
    }
    
    /**
     * generic check admin rights function
     * rules: 
     * - ADMIN right includes all other rights
     * - MANAGE_* right includes VIEW_* right 
     * 
     * @param   string  $_right to check
     * @todo    think about moving that to Tinebase_Acl or Tinebase_Application
     */    
    protected function checkRight( $_right ) {
        
        // array with the rights that should be checked, ADMIN is in it per default
        $rightsToCheck = array ( Tinebase_Acl_Rights::ADMIN );
        
        if ( preg_match("/MANAGE_/", $_right) ) {
            $rightsToCheck[] = constant('Admin_Acl_Rights::' . $_right);
        }

        if ( preg_match("/VIEW_([A-Z_]*)/", $_right, $matches) ) {
            $rightsToCheck[] = constant('Admin_Acl_Rights::' . $_right);
            // manage right includes view right
            $rightsToCheck[] = constant('Admin_Acl_Rights::MANAGE_' . $matches[1]);
        }
        
        $hasRight = FALSE;
        
        foreach ( $rightsToCheck as $rightToCheck ) {
            if ( Tinebase_Acl_Roles::getInstance()->hasRight('Admin', $this->_currentAccount->getId(), $rightToCheck) ) {
                $hasRight = TRUE;
                break;    
            }
        }
        
        if ( !$hasRight ) {
            throw new Exception("You are not allowed to $_right !");
        }        
                
    }
    
}
