<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Container Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Container extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_currentAccount = Tinebase_Core::getUser();        
        $this->_applicationName = 'Admin';
		$this->_modelName = 'Tinebase_Model_Container';
        
        $this->_backend = new Tinebase_Backend_Sql(array(
            'tableName' => 'container',
            'modelName' => $this->_modelName,
        ));
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_Container
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_Container
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Container;
        }
        
        return self::$_instance;
    }
    
    /**
     * set multiple container grants
     * 
     * @param Tinebase_Record_RecordSet $_containers
     * @param array|string              $_grants single or multiple grants
     * @param array|string              $_accountId single or multiple account ids
     * @param string                    $_accountType
     * @param boolean                   $_overwrite replace grants?
     * 
     * @todo need to invent a way to let applications (like the timetracker) hook into this fn + remove timetracker stuff afterwards
     */
    public function setGrantsForContainers($_containers, $_grants, $_accountId, $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, $_overwrite = FALSE)
    {
        $accountType = ($_accountId === '0') ? Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE : $_accountType;
        $accountIds = (array) $_accountId;
        $grantsArray = ($_overwrite) ? array() : (array) $_grants;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Changing grants of containers: ' . print_r($_containers->name, TRUE));
        
        $timetrackerApp = (Tinebase_Application::getInstance()->isInstalled('Timetracker')) 
            ? Tinebase_Application::getInstance()->getApplicationByName('Timetracker')
            : NULL;
        
        foreach($_containers as $container) {
            foreach ($accountIds as $accountId) {
                if ($_overwrite) {
                    foreach((array) $_grants as $grant) {
                        $grantsArray[] = array(
                            'account_id'    => $accountId,
                            'account_type'  => $accountType,
                            $grant          => TRUE,
                        );                        
                    }
                } else {
                    Tinebase_Container::getInstance()->addGrants($container->getId(), $accountType, $accountId, $grantsArray, TRUE);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Added grants to container "' . $container->name . '" for userid ' . $accountId . ' (' . $accountType . ').');
                }
            }
            
            if ($_overwrite) {
                if ($timetrackerApp !== NULL && $container->application_id === $timetrackerApp->getId()) {
                    // @todo allow to call app specific functions here
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Set grants for timeaccount "' . $container->name . '".');
                    $timeaccountGrants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', $grantsArray);
                    
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Set grants for container "' . $container->name . '".');
                    $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', $grantsArray);
                }
                
                Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, TRUE, FALSE);
            }
        }        
    }    
}
