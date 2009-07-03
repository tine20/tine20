<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * get list of tags
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Tag
     */
    public function search($query, $sort, $dir, $start, $limit)
    {
        $filter = new Tinebase_Model_TagFilter(array(
            'name'        => '%' . $query . '%',
            'description' => '%' . $query . '%',
            'type'        => Tinebase_Model_Tag::TYPE_SHARED
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        return Tinebase_Tags::getInstance()->searchTags($filter, $paging);
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

        $_tag->rights->tag_id = $newTag->getId();
        Tinebase_Tags::getInstance()->setRights($_tag->rights);
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
        
        $_tag->rights->tag_id = $_tag->getId();
        Tinebase_Tags::getInstance()->purgeRights($_tag->getId());
        Tinebase_Tags::getInstance()->setRights($_tag->rights);
        
        Tinebase_Tags::getInstance()->purgeContexts($_tag->getId());
        Tinebase_Tags::getInstance()->setContexts($_tag->contexts, $_tag->getId());
        
        return $this->get($_tag->getId());
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
