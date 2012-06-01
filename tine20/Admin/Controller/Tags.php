<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        refactoring: use functions from Tinebase_Controller_Record_Abstract
 */

/**
 * (Shared) Tags Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Tags extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName = 'Admin';
        $this->_backend = Tinebase_Tags::getInstance();
        $this->_doContainerACLChecks = FALSE;
        $this->_omitModLog = TRUE;
        $this->_modelName = 'Tinebase_Model_FullTag';
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
     * @var Admin_Controller_Tags
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_Tags
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Tags;
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     * 
     * @todo remove this and use Tinebase_Controller_Record_Abstract::search()
     */
    public function search_($_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        return Tinebase_Tags::getInstance()->searchTags($_filter, $_pagination);
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     * 
     * @todo remove this and use Tinebase_Controller_Record_Abstract::searchCount()
     */
    public function searchCount_($_filter, $_action = 'get')
    {
        return Tinebase_Tags::getInstance()->getSearchTagsCount($_filter);
    }
   
    /**
     * fetch one tag identified by tagid
     *
     * @param int $_tagId
     * @return Tinebase_Model_FullTag
     */
    public function get($_tagId)
    {
        $fullTag = parent::get($_tagId);
        
        $fullTag->rights =  Tinebase_Tags::getInstance()->getRights($_tagId);
        $fullTag->contexts = Tinebase_Tags::getInstance()->getContexts($_tagId);
        
        return $fullTag;
    }  

    /**
     * inspect creation of one record (before create)
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $_record->type = Tinebase_Model_Tag::TYPE_SHARED;
    }
    
    /**
     * inspect creation of one record (after create)
     * 
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        $this->_setTagRights($_record, $_createdRecord->getId());
        Tinebase_Tags::getInstance()->setContexts($_record->contexts, $_createdRecord->getId());
    }

    /**
     * inspect update of one record (after update)
     * 
     * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $_record          the update record
     * @return  void
     */
    protected function _inspectAfterUpdate($_updatedRecord, $_record)
    {
        $this->_setTagRights($_record, $_record->getId(), TRUE);
        Tinebase_Tags::getInstance()->purgeContexts($_record->getId());
        Tinebase_Tags::getInstance()->setContexts($_record->contexts, $_record->getId());
    }
    
    /**
     * set tag rights
     * 
     * @param Tinebase_Model_FullTag $_tag
     * @param string $_tagId
     * @param boolean $_purgeRights
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setTagRights(Tinebase_Model_FullTag $_tag, $_tagId, $_purgeRights = FALSE)
    {
        if (count($_tag->rights) == 0 || count($_tag->rights->view_right) == 0 || $_tag->rights->view_right[0] !== TRUE) {
            throw new Tinebase_Exception_InvalidArgument('Could not save tag without (view-)rights');
        }
        
        if ($_purgeRights) {
            Tinebase_Tags::getInstance()->purgeRights($_tagId);
        }
        $_tag->rights->tag_id = $_tagId;
        Tinebase_Tags::getInstance()->setRights($_tag->rights);
    }
    
    /**
     * delete multiple tags
     *
     * @param   array $_tagIds
     * @void
     * 
     * @todo replace this by parent::delete()
     */
    public function delete($_tagIds)
    {
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        Tinebase_Tags::getInstance()->deleteTags($_tagIds);
    }
    
    /**
     * check if user has the right to manage tags
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'create':
            case 'update':
            case 'delete':
                $this->checkRight('MANAGE_SHARED_TAGS');
                break;
            default;
               break;
        }
    }
}
