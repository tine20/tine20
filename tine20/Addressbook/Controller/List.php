<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_List extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Addressbook_Model_List';

    
    /**
	 * @todo why is this needed???
     */
    protected $_omitModLog = true;
    
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = new Addressbook_Backend_List();
        $this->_currentAccount = Tinebase_Core::getUser();
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
     * @var Addressbook_Controller_List
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_List
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_List();
        }
        
        return self::$_instance;
    }
    
    /**
     * add new members to list
     * 
     * @param  mixed  $_listId
     * @param  mixed  $_newMembers
     * @return Addressbook_Model_List
     */
    public function addListMember($_listId, $_newMembers)
    {
        $list = $this->get($_listId);
        
        $this->_checkGrant($list, 'update', TRUE, 'No permission to update record.');
        
        $list = $this->_backend->addListMember($_listId, $_newMembers);
        
        return $list;
    }
    
    /**
     * remove members from list
     * 
     * @param  mixed  $_listId
     * @param  mixed  $_newMembers
     * @return Addressbook_Model_List
     */
    public function removeListMember($_listId, $_newMembers)
    {
        $list = $this->get($_listId);
        
        $this->_checkGrant($list, 'update', TRUE, 'No permission to update record.');
        
        $list = $this->_backend->removeListMember($_listId, $_newMembers);
        
        return $list;
    }
    
    /**
     * delete one record
     * - don't delete if it belongs to an user account
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Addressbook_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        #if (!empty($_record->account_id)) {
        #    throw new Addressbook_Exception_AccessDenied('It is not allowed to delete a contact linked to an user account!');
        #}
        
        parent::_deleteRecord($_record);
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (isset($record->type) &&  $record->type == Addressbook_Model_List::LISTTYPE_GROUP) {
            throw new Addressbook_Exception_InvalidArgument('can not add list of type ' . Addressbook_Model_List::LISTTYPE_GROUP);
        }
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * 
     * @todo    check if address changes before setting new geodata
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (isset($record->type) &&  $record->type == Addressbook_Model_List::LISTTYPE_GROUP) {
            throw new Addressbook_Exception_InvalidArgument('can not add list of type ' . Addressbook_Model_List::LISTTYPE_GROUP);
        }
    }    
}
