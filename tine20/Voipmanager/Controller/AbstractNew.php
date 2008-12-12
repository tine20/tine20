<?php
/**
 * Abstract controller for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        rename Voipmanager_Controller_AbstractNew to Voipmanager_Controller_Abstract later
 */

/**
 * abstract controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
abstract class Voipmanager_Controller_AbstractNew extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;
    
    /**
     * the central caching object
     *
     * @var Zend_Cache_Core
     */
    protected $_cache;
    
    /**
     * initialize the database backend
     *
     * @return Zend_Db_Adapter_Abstract
     * @throws  Voipmanager_Exception_UnexpectedValue
     */
    protected function _getDatabaseBackend() 
    {
        if(isset(Zend_Registry::get('configFile')->voipmanager) && isset(Zend_Registry::get('configFile')->voipmanager->database)) {
            $dbConfig = Zend_Registry::get('configFile')->voipmanager->database;
        
            $dbBackend = constant('Tinebase_Core::' . strtoupper($dbConfig->get('backend', Tinebase_Core::PDO_MYSQL)));
            
            switch($dbBackend) {
                case Tinebase_Core::PDO_MYSQL:
                    $db = Zend_Db::factory('Pdo_Mysql', $dbConfig->toArray());
                    break;
                case Tinebase_Core::PDO_OCI:
                    $db = Zend_Db::factory('Pdo_Oci', $dbConfig->toArray());
                    break;
                default:
                    throw new Voipmanager_Exception_UnexpectedValue('Invalid database backend type defined. Please set backend to ' . Tinebase_Core::PDO_MYSQL . ' or ' . Tinebase_Core::PDO_OCI . ' in config file.');
                    break;
            }
        } else {
            $db = Zend_Registry::get('dbAdapter');
        }
        
        return $db;
    }
}
