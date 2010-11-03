<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        refactoring: use functions from Tinebase_Controller_Record_Abstract
 */

/**
 * (Shared) Tags Controller for Admin application
 *
 * @package     Admin
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
        $this->_currentAccount = Tinebase_Core::getUser();        
        $this->_applicationName = 'Admin';
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
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        return Tinebase_Tags::getInstance()->searchTags($_filter, $_pagination);
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
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
        $tag = Tinebase_Tags::getInstance()->getTagsById($_tagId);
        $fullTag = new Tinebase_Model_FullTag($tag[0]->toArray(), true);
        $fullTag->rights =  Tinebase_Tags::getInstance()->getRights($_tagId);
        $fullTag->contexts = Tinebase_Tags::getInstance()->getContexts($_tagId);
        
        return $fullTag;
    }  

   /**
     * add new tag
     *
     * @param  Tinebase_Model_FullTag $_tag
     * @return Tinebase_Model_FullTag
     */
    public function create(Tinebase_Record_Interface $_tag)
    {
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        $_tag->type = Tinebase_Model_Tag::TYPE_SHARED;
        $newTag = Tinebase_Tags::getInstance()->createTag(new Tinebase_Model_Tag($_tag->toArray(), true));

        $this->_setTagRights($_tag, $newTag->getId());
        Tinebase_Tags::getInstance()->setContexts($_tag->contexts, $newTag->getId());
        
        return $this->get($newTag->getId());
    }  

   /**
     * update existing tag
     *
     * @param  Tinebase_Model_FullTag $_tag
     * @return Tinebase_Model_FullTag
     */
    public function update(Tinebase_Record_Interface $_tag)
    {
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        Tinebase_Tags::getInstance()->updateTag(new Tinebase_Model_Tag($_tag->toArray(), true));
        
        $this->_setTagRights($_tag, $_tag->getId(), TRUE);
        Tinebase_Tags::getInstance()->purgeContexts($_tag->getId());
        Tinebase_Tags::getInstance()->setContexts($_tag->contexts, $_tag->getId());
        
        return $this->get($_tag->getId());
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
        if (count($_tag->rights) == 0) {
            throw new Tinebase_Exception_InvalidArgument('Could not save tag without rights');
        } 
        
        // @todo tag needs at least 1 view_right to be usable
        
        
//        $viewRightFound = FALSE;
//        foreach ($_tag->rights as $right) {
//            
//        } 
        
        //&& $_tag->rights->getFirstRecord)
        
        $_tag->rights->tag_id = $_tagId;
        
        if ($_purgeRights) {
            Tinebase_Tags::getInstance()->purgeRights($_tag->getId());
        }
        Tinebase_Tags::getInstance()->setRights($_tag->rights);        
    }
    
    /**
     * delete multiple tags
     *
     * @param   array $_tagIds
     * @void
     */
    public function delete($_tagIds)
    {        
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        Tinebase_Tags::getInstance()->deleteTags($_tagIds);
    }
}
