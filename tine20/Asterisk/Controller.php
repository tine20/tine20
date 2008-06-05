<?php
/**
 * controller for Asterisk Management application
 * 
 * the main logic of the Asterisk Management application
 *
 * @package     Asterisk Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */

/**
 * controller class for Asterisk Management application
 * 
 * @package     Asterisk Management
 */
class Asterisk_Controller
{
    /**
     * Asterisk backend class
     *
     * @var Asterisk_Backend_Sql
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Asterisk_Backend_Factory::factory(Asterisk_Backend_Factory::SQL);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Asterisk_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Asterisk_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Asterisk_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * get snom_phone by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Phone
     */
    public function getPhoneById($_id)
    {
        $result = $this->_backend->getPhoneById($_id);

        return $result;    
    }

    /**
     * add one phone
     *
     * @param Asterisk_Model_Phone $_phone
     * @return  Asterisk_Model_Phone
     */
    public function addPhone(Asterisk_Model_Phone $_phone)
    {        
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_ADD)) {
            throw new Exception('add access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
        $phone = $this->_backend->addPhone($_phone);
      
        return $phone;
    }
    
    
    /**
     * delete one or multiple phones
     *
     * @param mixed $_phoneId
     * @throws Exception 
     */
    public function deletePhone($_phoneId)
    {
        if (is_array($_phoneId) or $_phoneId instanceof Tinebase_Record_RecordSet) {
            foreach ($_phoneId as $phoneId) {
                $this->deletePhone($phoneId);
            }
        } else {
#            $phone = $this->_backend->getPhoneById($_phoneId);
#            if (Zend_Registry::get('currentAccount')->hasGrant($phone->owner, Tinebase_Container::GRANT_DELETE)) {
                $this->_backend->deletePhone($_phoneId);
#            } else {
#                throw new Exception('delete access to contact denied');
#            }
        }
    }    
    
    
    /**
     * update one phone
     *
     * @param Asterisk_Model_Phone $_phone
     * @return  Asterisk_Model_Phone
     */
    public function updatePhone(Asterisk_Model_Phone $_phone)
    {
        /*
        if (!Zend_Registry::get('currentAccount')->hasGrant($_contact->owner, Tinebase_Container::GRANT_EDIT)) {
            throw new Exception('edit access to contacts in container ' . $_contact->owner . ' denied');
        }
        */
       
        $phone = $this->_backend->updatePhone($_phone);
        
        return $phone;
    }    
    


    /**
     * get snom_phones
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Asterisk_Model_Phone
     */
    public function getPhones($_sort = 'id', $_dir = 'ASC', $_query = NULL)
    {
        $result = $this->_backend->getPhones($_sort, $_dir, $_query);

        return $result;    
    }

}