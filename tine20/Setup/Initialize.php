<?php
/**
 * Tine 2.0
  * 
 * @package     Setup
 * @subpackage  Initialize
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application initialization
 * 
 * @package     Setup
 * @subpackage  Initialize
 */
class Setup_Initialize
{
    /**
     * array with user role rights, overwrite this in your app to add more rights to user role
     * 
     * @var array
     */
    static protected $_userRoleRights = array(
        Tinebase_Acl_Rights::RUN
    );
    
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

        if (class_exists($classname)) {
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Initializing application: ' . $applicationName);

            $instance = new $classname;
            $instance->_initialize($_application, $_options);
        } else {
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Skipping custom init of application: '
                . $applicationName . '. Class ' . $classname . ' not found.');

            self::createInitialRights($_application);
        }
    }
    
    /**
     * Call {@see createInitialRights} on an instance of the concrete Setup_Initialize class for the given {@param $_application}
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
        $instance::createInitialRights($_application);
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
        self::initializeApplicationRights($_application);
        $initClasses = array($this);

        $customInitClass = $_application->name . '_Setup_Initialize_Custom';
        if (class_exists($customInitClass)) {
            $customInit = new $customInitClass();
            $initClasses[] = $customInit;
        }

        foreach ($initClasses as $initClass) {
            $reflectionClass = new ReflectionClass($initClass);
            $methods = $reflectionClass->getMethods();
            foreach ($methods as $method) {
                $methodName = $method->name;
                if ((strpos($methodName, '_initialize') === 0 && $methodName !== '_initialize')
                    || (get_class($initClass) === $customInitClass && (strpos($methodName, 'initialize') === 0))
                ) {
                    Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Calling init function '
                        . get_class($initClass) . '::' . $methodName);

                    $initClass->$methodName($_application, $_options);
                }
            }
        }
    }
    
    /**
     * create inital rights
     * 
     * @todo make hard coded role names ('user role' and 'admin role') configurable
     * 
     * @param Tinebase_Application $application
     * @return void
     */
    public static function createInitialRights(Tinebase_Model_Application $_application)
    {
        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating initial rights for application '
            . $_application->name);

        $allRights = Tinebase_Application::getInstance()->getAllRights($_application->getId());
        $userRights = static::$_userRoleRights;
        
        if (in_array(Tinebase_Acl_Rights::USE_PERSONAL_TAGS, $allRights)) {
            $userRights[] = Tinebase_Acl_Rights::USE_PERSONAL_TAGS;
        }
        
        $roleRights = array(
            'user role'     => $userRights,
            'admin role'    => $allRights
        );
        
        foreach ($roleRights as $roleName => $rights) {
            try {
                $role = Tinebase_Acl_Roles::getInstance()->getRoleByName($roleName);
            } catch(Tinebase_Exception_NotFound $tenf) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                continue;
            }
            
            foreach ($rights as $right) {
                try {
                    Tinebase_Acl_Roles::getInstance()->addSingleRight($role->getId(), $_application->getId(), $right);
                } catch(Exception $e) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                        . ' Cannot add right: ' . $right . ' for application: ' . $_application->name
                        . ' - ' . $roleName . ' - ' . print_r($e->getMessage(), true)
                    );
                }
            }
        }
    }
    
}
