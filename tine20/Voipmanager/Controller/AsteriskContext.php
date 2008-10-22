<?php
/**
 * controller for Voipmanager Management application
 * 
 * the main logic of the Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * asterisk context controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_AsteriskContext extends Tinebase_Application_Controller_Abstract
{
    /**
     * Voipmanager backend class
     *
     * @var Voipmanager_Backend_Asterisk_Context
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        if(isset(Zend_Registry::get('configFile')->voipmanager) && isset(Zend_Registry::get('configFile')->voipmanager->database)) {
            $this->_dbBbackend = $this->_getDatabaseBackend(Zend_Registry::get('configFile')->voipmanager->database);
        } else {
            $this->_dbBbackend = Zend_Registry::get('dbAdapter');
        }
        
        $this->_backend      = new Voipmanager_Backend_Asterisk_Context($this->_dbBbackend);          
    }
    
    /**
     * holdes the instance of the singleton
     *
     * @var Voipmanager_Controller_AsteriskContext
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Voipmanager_Controller_AsteriskContext
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_AsteriskContext;
        }
        
        return self::$_instance;
    }
    
    /**
     * get asterisk_context by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskContext
     */
    public function getAsteriskContext($_id)
    {
        $context = $this->_backend->get($_id);
        
        return $context;    
    }


    /**
     * get asterisk_contexts
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskContext
     */
    public function getAsteriskContexts($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_AsteriskContextFilter(array(
            'query' => $_query
        ));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'  => $_sort,
            'dir'   => $_dir
        ));

        $result = $this->_backend->search($filter, $pagination);
        
        return $result;    
    }


    /**
     * add one context
     *
     * @param Voipmanager_Model_AsteriskContext $_context
     * @return  Voipmanager_Model_AsteriskContext
     */
    public function createAsteriskContext(Voipmanager_Model_AsteriskContext $_context)
    {        
        $context = $this->_backend->create($_context);
      
        return $this->getAsteriskContext($context);
    }
    
    /**
     * update one context
     *
     * @param Voipmanager_Model_AsteriskContext $_context
     * @return  Voipmanager_Model_AsteriskContext
     */
    public function updateAsteriskContext(Voipmanager_Model_AsteriskContext $_context)
    {
        $context = $this->_backend->update($_context);
        
        return $this->getAsteriskContext($context);
    }    
    
    /**
     * Deletes a set of contexts.
     * 
     * If one of the contexts could not be deleted, no context is deleted
     * 
     * @throws Exception
     * @param array array of context identifiers
     * @return void
     */
    public function deleteAsteriskContexts($_identifiers)
    {
        $this->_backend->delete($_identifiers);
    }    
}
