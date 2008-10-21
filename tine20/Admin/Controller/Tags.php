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
 * @todo        change exceptions to PermissionDeniedException?
 */

/**
 * (Shared) Tags Controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller_Tags extends Admin_Controller_Abstract
{
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
    public function getTags($query, $sort, $dir, $start, $limit)
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
    public function getTag($_tagId)
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
    public function addTag(Tinebase_Model_FullTag $_tag)
    {
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        $_tag->type = Tinebase_Model_Tag::TYPE_SHARED;
        $newTag = Tinebase_Tags::getInstance()->createTag(new Tinebase_Model_Tag($_tag->toArray(), true));

        $_tag->rights->tag_id = $newTag->getId();
        Tinebase_Tags::getInstance()->setRights($_tag->rights);
        Tinebase_Tags::getInstance()->setContexts($_tag->contexts, $newTag->getId());
        
        return $this->getTag($newTag->getId());
    }  

   /**
     * update existing tag
     *
     * @param  Tinebase_Model_FullTag $_tag
     * @return Tinebase_Model_FullTag
     */
    public function updateTag(Tinebase_Model_FullTag $_tag)
    {
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        Tinebase_Tags::getInstance()->updateTag(new Tinebase_Model_Tag($_tag->toArray(), true));
        
        $_tag->rights->tag_id = $_tag->getId();
        Tinebase_Tags::getInstance()->purgeRights($_tag->getId());
        Tinebase_Tags::getInstance()->setRights($_tag->rights);
        
        Tinebase_Tags::getInstance()->purgeContexts($_tag->getId());
        Tinebase_Tags::getInstance()->setContexts($_tag->contexts, $_tag->getId());
        
        return $this->getTag($_tag->getId());
    }  
    
    /**
     * delete multiple tags
     *
     * @param   array $_tagIds
     * @void
     */
    public function deleteTags($_tagIds)
    {        
        $this->checkRight('MANAGE_SHARED_TAGS');
        
        Tinebase_Tags::getInstance()->deleteTags($_tagIds);
    }

}
