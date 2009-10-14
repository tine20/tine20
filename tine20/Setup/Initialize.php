<?php
/**
 * Tine 2.0
  * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: TineInitial.php 9535 2009-07-20 10:30:05Z p.schuele@metaways.de $
 *
 */

/**
 * Class to handle application initialization
 * 
 * @package     Setup
 */
class Setup_Initialize
{
    
    /**
     * Call {@see _initialize} on an instance of the concrete Setup_Initialize class for the given {@param $_application}  
     * 
     * @param Tinebase_Model_Application $_application
     * @param array | optional $_options
     * @return void
     */
    public static function initialize(Tinebase_Model_Application $_application, $_options = null)
    {
    	$applicationName = $_application->name;
    	$classname = "{$applicationName}_Setup_Initialize"; 	
    	$instance = new $classname;
    	$instance->_initialize($_application, $_options);
    }
    
    /**
     * Call {@see _createInitialRights} on an instance of the concrete Setup_Initialize class for the given {@param $_application}  
     * 
     * @param Tinebase_Model_Application $_application
     * @param array | optional $_options
     * @return void
     */
    public static function initializeApplicationRights(Tinebase_Model_Application $_application, $_options = null)
    {
    	$applicationName = $_application->name;
    	$classname = "{$applicationName}_Setup_Initialize"; 	
    	$instance = new $classname;
    	$instance->_createInitialRights($_application);
    }
    
    /**
     * initialize application
     *
     * @param Tinebase_Model_Application $_application
     * @param array | optional $_options
     * @return void
     */
    protected function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        $this->_createInitialRights($_application);
    }
    
    /**
     * 
     * @todo make hard coded role names ('user role' and 'admin role') configurable
     * 
     * @param Tinebase_Application $application
     * @return void
     */
    protected function _createInitialRights(Tinebase_Model_Application $_application)
    {   
    	$roles = Tinebase_Acl_Roles::getInstance();
    	$userRole = $roles->getRoleByName('user role');
        //run right for user role
		$roles->addSingleRight(
		    $userRole->getId(), 
		    $_application->getId(), 
		    Tinebase_Acl_Rights::RUN
		);
		
		$adminRole = $roles->getRoleByName('admin role');
		$allRights = Tinebase_Application::getInstance()->getAllRights($_application->getId());
		foreach ( $allRights as $right ) {
		    $roles->addSingleRight(
		        $adminRole->getId(), 
		        $_application->getId(), 
		        $right
		    );
		}
    }

}
