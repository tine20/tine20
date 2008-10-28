<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * controller abstract for applications
 *
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Application_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = '';
    
    /**
     * the current account
     * 
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
     * the singleton pattern
     *
     * @return mixed
     */
    abstract public static function getInstance();

    /**
     * generic check admin rights function
     * rules: 
     * - ADMIN right includes all other rights
     * - MANAGE_* right includes VIEW_* right 
     * 
     * @param   string  $_right to check
     * @throws  Tinebase_Exception_UnexpectedValue
     * @throws  Tinebase_Exception_AccessDenied
     */    
    public function checkRight($_right) {
        
        if (empty($this->_applicationName)) {
            throw new Tinebase_Exception_UnexpectedValue('No application name defined!');
        }
        
        $applicationRightsClass = $this->_applicationName . '_Acl_Rights';
        
        // array with the rights that should be checked, ADMIN is in it per default
        $rightsToCheck = array ( Tinebase_Acl_Rights::ADMIN );
        
        if (preg_match("/MANAGE_/", $_right)) {
            $rightsToCheck[] = constant($applicationRightsClass. '::' . $_right);
        }

        if (preg_match("/VIEW_([A-Z_]*)/", $_right, $matches)) {
            $rightsToCheck[] = constant($applicationRightsClass. '::' . $_right);
            // manage right includes view right
            $rightsToCheck[] = constant($applicationRightsClass. '::MANAGE_' . $matches[1]);
        }
        
        $hasRight = FALSE;
        
        foreach ($rightsToCheck as $rightToCheck) {
            if (Tinebase_Acl_Roles::getInstance()->hasRight('Admin', $this->_currentAccount->getId(), $rightToCheck)) {
                $hasRight = TRUE;
                break;    
            }
        }
        
        if (!$hasRight) {
            throw new Tinebase_Exception_AccessDenied("You are not allowed to $_right in application $this->_applicationName !");
        }        
    }
}
