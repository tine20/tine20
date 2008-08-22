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
 * NOTE: Functions in the 'tagging' chain check acl of the actions, 
 *       tag housekeeper functions do their acl in the admin controller
 * @package     Tinebase
 * @subpackage  Tags 
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
    private function __clone()
    {
        
    }

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
     * @param  Tinebase_Model_TagFilter $_filter
     * @param  Tinebase_Model_Pagination  $_paging
     * @param  string                     $_right   the required right current user must have on the tags
     * @return Tinebase_Record_RecordSet  Set of Tinebase_Model_Tag
     */
    public function searchTags($_filter, $_paging, $_right=Tinebase_Model_TagRight::VIEW_RIGHT, $_ignoreAcl=false)
    {
        $select = $_filter->getSelect();
        
        if ($_ignoreAcl !== true) {
            Tinebase_Model_TagRight::applyAclSql($select, $_right);
        }
        $_paging->appendPagination($select);
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_Tag', $this->_db->fetchAssoc($select));
    }
    
    /**
     * Returns tags count of a tag search
     * @todo automate the count query if paging is active!
     * 
     * @param  Tinebase_Model_TagFilter $_filter
     * @param  string                     $_right   the required right current user must have on the tags
     * @return int
     */
    public function getSearchTagsCount($_filter, $_right=Tinebase_Model_TagRight::VIEW_RIGHT)
    {
        $select = $_filter->getSelect();
        Tinebase_Model_TagRight::applyAclSql($select, $_right);
        
        $tags = new Tinebase_Record_RecordSet('Tinebase_Model_Tag', $this->_db->fetchAssoc($select));
        return count($tags);
    }
    
    /**
     * Returns (bare) tags identified by its id(s)
     * @todo check context ?
     * 
     * @param  string|array|Tinebase_Record_RecordSet  $_id
     * @param  string                                  $_right the required right current user must have on the tags
     * @param  bool                                    $_ignoreAcl
     * @return Tinebase_Record_RecordSet               Set of Tinebase_Model_Tag
     */
    public function getTagsById($_id, $_right=Tinebase_Model_TagRight::VIEW_RIGHT, $_ignoreAcl=false)
    {
        $tags = new Tinebase_Record_RecordSet('Tinebase_Model_Tag');
        
        if (!empty($_id)) {
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'tags')
                ->where('is_deleted = 0')
                ->where($this->_db->quoteInto('id IN (?)', $_id));
            if ($_ignoreAcl !== true) {
                Tinebase_Model_TagRight::applyAclSql($select, $_right);
            }
            
            foreach ($this->_db->fetchAssoc($select) as $tagArray){
                $tags->addRecord(new Tinebase_Model_Tag($tagArray, true));
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
     * @param  Tinebase_Model_Tag
     * @return Tinebase_Model_Tag
     */
    public function createTag(Tinebase_Model_Tag $_tag)
    {
        $currentAccountId = Zend_Registry::get('currentAccount')->getId();
        
        $newId = $_tag->generateUID();
        $_tag->setId($newId);
        $_tag->occurrence = 0;
        $_tag->created_by = Zend_Registry::get('currentAccount')->getId();
        $_tag->creation_time = Zend_Date::now()->getIso();
        
        switch ($_tag->type) {
            case Tinebase_Model_Tag::TYPE_PERSONAL:
                $_tag->owner = $currentAccountId;
                $this->_db->insert(SQL_TABLE_PREFIX . 'tags', $_tag->toArray());
                // for personal tags we set rights and scope temprary here, 
                // this needs to be moved into Tinebase Controller later
                $right = new Tinebase_Model_TagRight(array(
                    'tag_id'        => $newId,
                    'account_type'  => 'user',
                    'account_id'    => $currentAccountId,
                    'view_right'    => true,
                    'use_right'     => true,
                ));
                $this->setRights($right);
                $this->_db->insert(SQL_TABLE_PREFIX . 'tags_context', array(
                    'tag_id'         => $newId,
                    'application_id' => 0
                ));
                break;
            case Tinebase_Model_Tag::TYPE_SHARED:
                // @todo move to controller later?
                if ( !Tinebase_Acl_Roles::getInstance()
                        ->hasRight('Tinebase', $currentAccountId, Admin_Acl_Rights::MANAGE_SHARED_TAGS) ) {
                    throw new Exception('Your are not allowed to create this tag');
                }
                $_tag->owner = 0;
                $this->_db->insert(SQL_TABLE_PREFIX . 'tags', $_tag->toArray());
                break;
            default:
                throw new Exception('No such tag type');
                break;
        }
        
        // any context temporary
        
        $tags = $this->getTagsById($newId, NULL, true);
        return $tags[0];
    }
    
    /**
     * updates a single tag
     * 
     * @param  Tinebase_Model_Tag
     * @return Tinebase_Model_Tag
     */
    public function updateTag(Tinebase_Model_Tag $_tag)
    {
        $currentAccountId = Zend_Registry::get('currentAccount')->getId();
        $manageSharedTagsRight = Tinebase_Acl_Roles::getInstance()
            ->hasRight('Tinebase', $currentAccountId, Admin_Acl_Rights::MANAGE_SHARED_TAGS);
        
        if ( ($_tag->type == Tinebase_Model_Tag::TYPE_PERSONAL && $_tag->owner == $currentAccountId) ||
             ($_tag->type == Tinebase_Model_Tag::TYPE_SHARED && $manageSharedTagsRight) ) {
                 
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
     * Deletes (set stated deleted) tags identified by their identifiers
     * 
     * @param  string|array id(s) to delete
     * @return void
     */
    public function deleteTags($_ids)
    {
        $currentAccountId = Zend_Registry::get('currentAccount')->getId();
        $manageSharedTagsRight = Tinebase_Acl_Roles::getInstance()
            ->hasRight('Tinebase', $currentAccountId, Admin_Acl_Rights::MANAGE_SHARED_TAGS);
        $tags = $this->getTagsById($_ids);
        if (count($tags) != count((array)$_ids)) {
            throw new Exception('You are not allowed to delete this tags');
        }
        
        foreach ($tags as $tag) {
            if ( ($tag->type == Tinebase_Model_Tag::TYPE_PERSONAL && $tag->owner == $currentAccountId) ||
                 ($tag->type == Tinebase_Model_Tag::TYPE_SHARED && $manageSharedTagsRight) ) {
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
        
        $_select->join(array('tagging' => SQL_TABLE_PREFIX . 'tagging'), "tagging.record_id = $idProperty", array());
        $_select->where($db->quoteInto('tagging.tag_id = ?', $_tagId));
        Tinebase_Model_TagRight::applyAclSql($_select, Tinebase_Model_TagRight::VIEW_RIGHT, 'tagging.tag_id');
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
    public function getTagsOfRecord($_record, $_tagsProperty='tags', $_right=Tinebase_Model_TagRight::VIEW_RIGHT)
    {
        $recordId = $_record->getId();
        $tags = new Tinebase_Record_RecordSet('Tinebase_Model_Tag');
        if (!empty($recordId)) {
            $select = $this->_db->select()
                ->from(array('tagging' => SQL_TABLE_PREFIX . 'tagging'))
                ->join(array('tags'    => SQL_TABLE_PREFIX . 'tags'), 'tagging.tag_id = tags.id')
                ->where('application_id = ?', Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId())
                ->where('record_id = ? ', $recordId)
                ->where('is_deleted = 0');
            Tinebase_Model_TagRight::applyAclSql($select, $_right, 'tagging.tag_id');
            foreach ($this->_db->fetchAssoc($select) as $tagArray){
                $tags->addRecord(new Tinebase_Model_Tag($tagArray, true));
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
        $currentTags = $this->getTagsOfRecord($_record, 'tags', Tinebase_Model_TagRight::USE_RIGHT)->getArrayOfIds();
        
        
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
     * @param   array|Tinebase_Record_RecordSet set of string|array|Tinebase_Model_Tag with existing and non-existing tags
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
                    $tag = new Tinebase_Model_Tag($tag);
                } elseif (!$tag instanceof Tinebase_Model_Tag) {
                    throw new Exception('Tag could not be identified');
                }
                if (!$tag->getId()) {
                    $tag->type = Tinebase_Model_Tag::TYPE_PERSONAL;
                    $tag = $this->createTag($tag);
                }
                $tagIds[] = $tag->getId();
            }
        }
        return($this->getTagsById($tagIds, Tinebase_Model_TagRight::USE_RIGHT));
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
        $tagId = $_tag instanceof Tinebase_Model_Tag ? $_tag->getId() : $_tag;
        
        $this->_db->update(SQL_TABLE_PREFIX . 'tags', array(
            'occurrence' => new Zend_Db_Expr('occurrence+' . (int)$_toAdd)
        ), $this->_db->quoteInto('id = ?', $tagId));
    }
    
    /**
     * get all rights of a given tag
     * 
     * @param  string                    $_tagId 
     * @return Tinebase_Record_RecordSet Set of Tinebase_Model_TagRight
     */
    public function getRights($_tagId)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'tags_acl', array('tag_id', 'account_type', 'account_id',
                 'account_right' => 'GROUP_CONCAT(DISTINCT account_right)'))
            ->where($this->_db->quoteInto('tag_id = ?', $_tagId))
            ->group(array('tag_id', 'account_type', 'account_id'));
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $rights = new Tinebase_Record_RecordSet('Tinebase_Model_TagRight', $rows, true);
        
        //Zend_Registry::get('logger')->debug(print_r($rights->toArray(), true));
        return $rights;
    }
    
    /**
     * purges (removes from tabel) all rights of a given tag
     * 
     * @param  string $_tagId
     * @return void
     */
    public function purgeRights($_tagId)
    {
        $where = $this->_db->quoteInto('tag_id = ?', $_tagId);
        $this->_db->delete(SQL_TABLE_PREFIX . 'tags_acl', $where);
    }
    
    /**
     * Sets all given tag rights
     * 
     * @param Tinebase_Record_RecordSet|Tinebase_Model_TagRight
     * @return void
     * @throws Exception
     */
    public function setRights($_rights)
    {
        $rights = $_rights instanceof Tinebase_Model_TagRight ? array($_rights) : $_rights;
        foreach ($rights as $right) {
            if (! ($right instanceof Tinebase_Model_TagRight && $right->isValid())) {
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
    
    /**
     * returns all contexts of a given tag
     * 
     * @param  string $_tagId
     * @return array  array of application ids
     */
    public function getContexts($_tagId)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'tags_context', array('application_id' => 'GROUP_CONCAT(DISTINCT application_id)'))
            ->where($this->_db->quoteInto('tag_id = ?', $_tagId))
            ->group('tag_id');
        $apps = $this->_db->fetchOne($select);
        
        //Zend_Registry::get('logger')->debug($apps);
        if ($apps == 0){
            $apps = 'any';
        }
        //$stmt = $this->_db->query($select);
        //$rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC); 
        return explode(',', $apps);
        
        
    }
    
    /**
     * purges (removes from tabel) all contexts of a given tag
     * 
     * @param  string $_tagId
     * @return void
     */
    public function purgeContexts($_tagId)
    {
        $where = $this->_db->quoteInto('tag_id = ?', $_tagId);
        $this->_db->delete(SQL_TABLE_PREFIX . 'tags_context', $where);
    }
    
    /**
     * sets all given contexts for a given tag
     * 
     * @param  array  $_contexts
     * @param  string $_tagId
     * @return void
     */
    public function setContexts(array $_contexts, $_tagId)
    {
        if (!$_tagId) {
            throw new Exception('a $_tagId is mandentory');
        }
        
        if (in_array('any', $_contexts, true) || in_array(0, $_contexts, true)) {
            $_contexts = array(0);
        }
        
        foreach ($_contexts as $content) {
            $this->_db->insert(SQL_TABLE_PREFIX . 'tags_context', array(
                'tag_id'         => $_tagId,
                'application_id' => $content
            ));
        }
    }
}