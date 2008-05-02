<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Class for handling tags and tagging.
 * 
 * NOTE: Tags for a record are and Setting of Tags
 * NOTE: History loging of tags 
 * @todo work out /apply transaction concept!
 * @todo check/manage contexts
 */
class Tinebase_Tags
{
    /**
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $_db;
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Tags
     */
    private static $_instance = NULL;
    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Tags
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Tags;
        }
        
        return self::$_instance;
    }
    
    private function __construct()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    
    /**
     * Searches tags according to filter and paging
     * The Current user needs to have the given right, unless $_ignoreAcl is true
     * 
     * @param  Tinebase_Tags_Model_Filter $_filter
     * @param  Tinebase_Model_Pagination  $_paging
     * @param  string                     $_right   the required right current user must have on the tags
     * @return Tinebase_Record_RecordSet  Set of Tinebase_Tags_Model_Tag
     */
    public function searchTags($_filter, $_paging, $_right=Tinebase_Tags_Model_Right::VIEW_RIGHT, $_ignoreAcl=false)
    {
        $select = $_filter->getSelect();
        
        if ($_ignoreAcl !== true) {
            Tinebase_Tags_Model_Right::applyAclSql($select, $_right);
        }
        $_paging->appendPagination($select);
        
        return new Tinebase_Record_RecordSet('Tinebase_Tags_Model_Tag', $this->_db->fetchAssoc($select));
    }
    
    /**
     * Returns tags count of a tag search
     * @todo automate the count query if paging is active!
     * 
     * @param  Tinebase_Tags_Model_Filter $_filter
     * @param  string                     $_right   the required right current user must have on the tags
     * @return int
     */
    public function getSearchTagsCount($_filter, $_right=Tinebase_Tags_Model_Right::VIEW_RIGHT)
    {
        $select = $_filter->getSelect();
        Tinebase_Tags_Model_Right::applyAclSql($select, $_right);
        
        $tags = new Tinebase_Record_RecordSet('Tinebase_Tags_Model_Tag', $this->_db->fetchAssoc($select));
        return count($tags);
    }
    
    /**
     * Returns tags identified by its id(s)
     * @todo check view acl and attach rights + context
     * 
     * @param  string|array|Tinebase_Record_RecordSet  $_id
     * @param  string                                  $_right the required right current user must have on the tags
     * @return Tinebase_Record_RecordSet               Set of Tinebase_Tags_Model_Tag
     */
    public function getTagsById($_id, $_right=Tinebase_Tags_Model_Right::VIEW_RIGHT)
    {
        $tags = new Tinebase_Record_RecordSet('Tinebase_Tags_Model_Tag');
        
        if (!empty($_id)) {
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'tags')
                ->where('is_deleted = 0')
                ->where($this->_db->quoteInto('id IN (?)', $_id));
            Tinebase_Tags_Model_Right::applyAclSql($select, $_right);
            
            foreach ($this->_db->fetchAssoc($select) as $tagArray){
                $tags->addRecord(new Tinebase_Tags_Model_Tag($tagArray, true));
            }
        }        
        if (is_string($_id) && empty($tags)) {
            throw new Exception("Tag with id '$_id'' not found");
        }
        return $tags;
    }
    
    /**
     * Creates a single tag
     * 
     * @param  Tinebase_Tags_Model_Tag
     * @return Tinebase_Tags_Model_Tag
     */
    public function createTag(Tinebase_Tags_Model_Tag $_tag)
    {
        $currentAccountId = Zend_Registry::get('currentAccount')->getId();
        
        $newId = $_tag->generateUID();
        $_tag->setId($newId);
        $_tag->occurrence = 0;
        $_tag->created_by = Zend_Registry::get('currentAccount')->getId();
        $_tag->creation_time = Zend_Date::now()->getIso();
        
        switch ($_tag->type) {
            case Tinebase_Tags_Model_Tag::TYPE_PERSONAL:
                $_tag->owner = $currentAccountId;
                $this->_db->insert(SQL_TABLE_PREFIX . 'tags', $_tag->toArray());
                $right = new Tinebase_Tags_Model_Right(array(
                    'tag_id'        => $newId,
                    'account_type'  => 'user',
                    'account_id'    => $currentAccountId,
                    'view_right'    => true,
                    'use_right'     => true,
                ));
                $this->setRights($right);
                break;
            case Tinebase_Tags_Model_Tag::TYPE_SHARED:
                if (! Tinebase_Acl_Rights::getInstance()->hasRight('Tinebase', 
                    $currentAccountId, Tinebase_Acl_Rights::MANAGE_SHARED_TAGS)) {
                        throw new Exception('Your are not allowed to create a shared tag!');
                }
                
                $_tag->owner = 0;
                $this->_db->insert(SQL_TABLE_PREFIX . 'tags', $_tag->toArray());
                $right = new Tinebase_Tags_Model_Right(array(
                    'tag_id'        => $newId,
                    'account_type'  => 'anyone',
                    'account_id'    => 0,
                    'view_right'    => true,
                    'use_right'     => true,
                ));
                $this->setRights($right);
                break;
            default:
                throw new Exception('No such tag type');
        }
        
        // any context temporary
        $this->_db->insert(SQL_TABLE_PREFIX . 'tags_context', array(
            'tag_id'         => $newId,
            'application_id' => 0
        ));
        $tags = $this->getTagsById($newId);
        return $tags[0];
    }
    
    /**
     * updates a single tag
     * 
     * @param  Tinebase_Tags_Model_Tag
     * @return Tinebase_Tags_Model_Tag
     */
    public function updateTag(Tinebase_Tags_Model_Tag $_tag)
    {
        $currentAccountId = Zend_Registry::get('currentAccount')->getId();
        $manageSharedTagsRight = Tinebase_Acl_Rights::getInstance()
            ->hasRight('Tinebase', $currentAccountId, Tinebase_Acl_Rights::MANAGE_SHARED_TAGS);
        
        if ( ($_tag->type == Tinebase_Tags_Model_Tag::TYPE_PERSONAL && $_tag->owner == $currentAccountId) ||
             ($_tag->type == Tinebase_Tags_Model_Tag::TYPE_SHARED && $manageSharedTagsRight) ) {
                 
            $tagId = $_tag->getId();
            if (strlen($tagId) != 40) {
                throw new Exception('Could not update non-existing tag');
            }
            
            $this->_db->update(SQL_TABLE_PREFIX . 'tags', array(
                'type'               => $_tag->type,
                'owner'              => $_tag->owner,
                'name'               => $_tag->name,
                'description'        => $_tag->description,
                'color'              => $_tag->color,
                'last_modified_by'   => $currentAccountId,
                'last_modified_time' => Zend_Date::now()->getIso()
            ), $this->_db->quoteInto('id = ?', $tagId));
            
            $tags = $this->getTagsById($tagId);
            return $tags[0];
        } else {
            throw new Exception('Your are not allowed to update this tag');
        }
    }
    
    /**
     * Deletes tags identified by their identifiers
     * @todo remove all taggings -> history log of records!
     * 
     * @param  string|array id(s) to delete
     * @return void
     */
    public function deleteTags($_ids)
    {
        $currentAccountId = Zend_Registry::get('currentAccount')->getId();
        $manageSharedTagsRight = Tinebase_Acl_Rights::getInstance()
            ->hasRight('Tinebase', $currentAccountId, Tinebase_Acl_Rights::MANAGE_SHARED_TAGS);
        $tags = $this->getTagsById($_ids);
        if (count($tags) != count((array)$_ids)) {
            throw new Exception('You are not allowed to delete this tags');
        }
        
        foreach ($tags as $tag) {
            if ( ($tag->type == Tinebase_Tags_Model_Tag::TYPE_PERSONAL && $tag->owner == $currentAccountId) ||
                 ($tag->type == Tinebase_Tags_Model_Tag::TYPE_SHARED && $manageSharedTagsRight) ) {
                continue;      
            } else {
                throw new Exception('You are not allowed to delete this tags');
            }
        }
        $this->_db->update(SQL_TABLE_PREFIX . 'tags', array(
            'is_deleted'   => true,
            'deleted_by'   => $currentAccountId,
            'deleted_time' => Zend_Date::now()->getIso()
        ), $this->_db->quoteInto('id IN (?)', $tags->getArrayOfIds()));
    }
    
    /**
     * Apends sql to a given select object to filter by the given tagId
     * 
     * @param  Zend_Db_Select $_select
     * @param  string         $_tagId
     * $param  string         $_idProperty id property of records
     * @return void
     */
    public static function appendSqlFilter(Zend_Db_Select $_select, $_tagId, $_idProperty='id')
    {
        $db = Zend_Registry::get('dbAdapter');
        $idProperty = $db->quoteIdentifier($_idProperty);
        
        $_select->join(array('tagging' => SQL_TABLE_PREFIX . 'tagging'), "tagging.record_id = $idProperty");
        $_select->where($db->quoteInto('tagging.tag_id = ?', $_tagId));
        Tinebase_Tags_Model_Right::applyAclSql($_select, Tinebase_Tags_Model_Right::VIEW_RIGHT, 'tagging.tag_id');
    }
    
    /**
     * Gets tags of a given record where user has the required right to
     * The tags are stored in the records $_tagsProperty.
     * 
     * @param Tinebase_Record_Abstract  $_record        the record object
     * @param string                    $_tagsProperty  the property in the record where the tags are in (defaults: 'tags')
     * @param string                    $_right         the required right current user must have on the tags
     * return Tinebase_Record_RecordSet tags of record
     */
    public function getTagsOfRecord($_record, $_tagsProperty='tags', $_right=Tinebase_Tags_Model_Right::VIEW_RIGHT)
    {
        $recordId = $_record->getId();
        $tags = new Tinebase_Record_RecordSet('Tinebase_Tags_Model_Tag');
        if (!empty($recordId)) {
            $select = $this->_db->select()
                ->from(array('tagging' => SQL_TABLE_PREFIX . 'tagging'))
                ->join(array('tags'    => SQL_TABLE_PREFIX . 'tags'), 'tagging.tag_id = tags.id')
                ->where('application_id = ?', Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId())
                ->where('record_id = ? ', $recordId)
                ->where('is_deleted = 0');
            Tinebase_Tags_Model_Right::applyAclSql($select, $_right, 'tagging.tag_id');
            foreach ($this->_db->fetchAssoc($select) as $tagArray){
                $tags->addRecord(new Tinebase_Tags_Model_Tag($tagArray, true));
            }
        }
        
        $_record[$_tagsProperty] = $tags;
        return $tags;
    }
    
    /**
     * sets (attachs and detaches) tags of a record
     * NOTE: Only touches tags the user has use right for
     * NOTE: Non existing personal tags will be created on the fly
     * 
     * @param Tinebase_Record_Abstract  $_record        the record object
     * @param string                    $_tagsProperty  the property in the record where the tags are in (defaults: 'tags')
     */
    public function setTagsOfRecord($_record, $_tagsProperty='tags')
    {
        $tagsToSet = $this->CreateTagsFly($_record[$_tagsProperty])->getArrayOfIds();
        $currentTags = $this->getTagsOfRecord($_record, 'tags', Tinebase_Tags_Model_Right::USE_RIGHT)->getArrayOfIds();
        
        
        $toAttach = array_diff($tagsToSet, $currentTags);
        $toDetach = array_diff($currentTags, $tagsToSet);
        
        // manage tags
        $appId = Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId();
        $recordId = $_record->getId();
        foreach ($toAttach as $tagId) {
            $this->_db->insert(SQL_TABLE_PREFIX . 'tagging', array(
                'tag_id'         => $tagId,
                'application_id' => $appId,
                'record_id'      => $recordId
                // backend property not supported by record yet
            ));
            $this->addOccurrence($tagId, +1);
        }
        foreach ($toDetach as $tagId) {
            $this->_db->delete(SQL_TABLE_PREFIX . 'tagging', array(
                $this->_db->quoteInto('tag_id = ?',         $tagId), 
                $this->_db->quoteInto('application_id = ?', $appId), 
                $this->_db->quoteInto('record_id = ?',      $recordId), 
                // backend property not supported by record yet
            ));
            $this->addOccurrence($tagId, -1);
        }
        
        // todo: history log
    }
    
    /**
     * Creates missing tags on the fly and returns complete list of tags the current
     * user has use rights for.
     * Allways respects the current acl of the current user!
     * 
     * @param   array|Tinebase_Record_RecordSet set of string|array|Tinebase_Tags_Model_Tag with existing and non-existing tags
     * @return  Tinebase_Record_RecordSet       set of all tags
     */
    protected function CreateTagsFly($_mixedTags)
    {
        $tagIds = array();
        foreach ($_mixedTags as $tag) {
            if (is_string($tag)) {
                $tagIds[] = $tag;
                continue;
            } else {
                if (is_array($tag)) {
                    $tag = new Tinebase_Tags_Model_Tag($tag);
                } elseif (!$tag instanceof Tinebase_Tags_Model_Tag) {
                    throw new Exception('Tag could not be identified');
                }
                if (!$tag->getId()) {
                    $tag->type = Tinebase_Tags_Model_Tag::TYPE_PERSONAL;
                    $tag = $this->createTag($tag);
                }
                $tagIds[] = $tag->getId();
            }
        }
        return($this->getTagsById($tagIds, Tinebase_Tags_Model_Right::USE_RIGHT));
    }
    
    /**
     * adds given number to the persistent occurrence property of a given tag
     * 
     * @param  Tinbebase_Tags_Model_Tag|string $_tag
     * @param  int                             $_toAdd
     * @return void
     */
    protected function addOccurrence($_tag, $_toAdd)
    {
        $tagId = $_tag instanceof Tinebase_Tags_Model_Tag ? $_tag->getId() : $_tag;
        
        $this->_db->update(SQL_TABLE_PREFIX . 'tags', array(
            'occurrence' => new Zend_Db_Expr('occurrence+' . (int)$_toAdd)
        ), $this->_db->quoteInto('id = ?', $tagId));
    }
    
    /**
     * Sets all given tag rights
     * 
     * @param Tinebase_Record_RecordSet|Tinebase_Tags_Model_Right
     * @return void
     * @throws Exception
     */
    protected function setRights($_rights)
    {
        $rights = $_rights instanceof Tinebase_Tags_Model_Right ? array($_rights) : $_rights;
        foreach ($rights as $right) {
            if (! ($right instanceof Tinebase_Tags_Model_Right && $right->isValid())) {
                throw new Exception ('The given right is not valid!');
            }
            $this->_db->delete(SQL_TABLE_PREFIX . 'tags_acl', array(
                $this->_db->quoteInto('tag_id = ?', $right->tag_id),
                $this->_db->quoteInto('account_type = ?', $right->account_type),
                $this->_db->quoteInto('account_id = ?', $right->account_id)
            ));
            foreach (array('view', 'use' ) as $availableRight) {
                $rightField = $availableRight . '_right';
            	if ($right->$rightField === true) {
            	    $this->_db->insert(SQL_TABLE_PREFIX . 'tags_acl', array(
                        'tag_id'        => $right->tag_id,
                        'account_type'  => $right->account_type,
                        'account_id'    => $right->account_id,
            	        'account_right' => $availableRight
                    ));
            	}
            }
        } 
    }
}