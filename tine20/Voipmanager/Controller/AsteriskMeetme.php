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
 * AsteriskMeetme controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_AsteriskMeetme extends Tinebase_Application_Controller_Abstract
{
    /**
     * Voipmanager backend class
     *
     * @var Voipmanager_Backend_Asterisk_Meetme
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
        
		$this->_backend		= new Voipmanager_Backend_Asterisk_Meetme($this->_dbBbackend);
    }
        
    /**
     * holdes the instance of the singleton
     *
     * @var Voipmanager_Controller_AsteriskMeetme
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Voipmanager_Controller_AsteriskMeetme
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_AsteriskMeetme;
        }
        
        return self::$_instance;
    }

    /**
     * get asterisk_meetme by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskMeetme
     */
    public function getAsteriskMeetme($_id)
    {
        $meetme = $this->_backend->get($_id);
        
        return $meetme;    
    }


    /**
     * get asterisk_meetmes
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskMeetme
     */
    public function getAsteriskMeetmes($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $filter = new Voipmanager_Model_AsteriskMeetmeFilter(array(
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
     * add one meetme
     *
     * @param Voipmanager_Model_AsteriskMeetme $_meetme
     * @return  Voipmanager_Model_AsteriskMeetme
     */
    public function createAsteriskMeetme(Voipmanager_Model_AsteriskMeetme $_meetme)
    {        
        $meetme = $this->_backend->create($_meetme);
      
        return $meetme;
    }
    

    /**
     * update one meetme
     *
     * @param Voipmanager_Model_AsteriskMeetme $_meetme
     * @return  Voipmanager_Model_AsteriskMeetme
     */
    public function updateAsteriskMeetme(Voipmanager_Model_AsteriskMeetme $_meetme)
    {
        $meetme = $this->_backend->update($_meetme);
        
        return $this->getAsteriskMeetme($meetme);
    }    
    
  
    /**
     * Deletes a set of meetmes.
     * 
     * If one of the meetmes could not be deleted, no meetme is deleted
     * 
     * @throws Exception
     * @param array array of meetme identifiers
     * @return void
     */
    public function deleteAsteriskMeetmes($_identifiers)
    {
        $this->_backend->delete($_identifiers);
    }        
}
