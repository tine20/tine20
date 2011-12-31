<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 *
 * @todo        this should implement Tinebase_Backend_Sql_Interface or use standard sql backend + refactor this
 */

/**
 * Class for handling tags and tagging.
 *
 * NOTE: Functions in the 'tagging' chain check acl of the actions,
 *       tag housekeeper functions do their acl in the admin controller
 *       
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
     * holds the instance of the singleton
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

    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_db = Tinebase_Core::getDb();
    }

    /**
     * Searches tags according to filter and paging
     * The Current user needs to have the given right, unless $_ignoreAcl is true
     *
     * @param  Tinebase_Model_TagFilter $_filter
     * @param  Tinebase_Model_Pagination  $_paging
     * @return Tinebase_Record_RecordSet  Set of Tinebase_Model_Tag
     */
    public function searchTags($_filter, $_paging)
    {
        $select = $_filter->getSelect();

        Tinebase_Model_TagRight::applyAclSql($select, $_filter->grant);
        $_paging->appendPaginationSql($select);

        return new Tinebase_Record_RecordSet('Tinebase_Model_Tag', $this->_db->fetchAssoc($select));
    }

    /**
    * Searches tags according to foreign filter
    * -> returns the count of tag occurrences in the result set
    *
    * @param  Tinebase_Model_Filter_FilterGroup $_filter
    * @return Tinebase_Record_RecordSet  Set of Tinebase_Model_Tag
    */
    public function searchTagsByForeignFilter($_filter)
    {
        $controller = Tinebase_Core::getApplicationInstance($_filter->getApplicationName(), $_filter->getModelName());
        $recordIds = $controller->search($_filter, NULL, FALSE, TRUE);
        
        if (! empty($recordIds)) { 
            $app = Tinebase_Application::getInstance()->getApplicationByName($_filter->getApplicationName());
            $select = $this->_getSelect($recordIds, $app->getId());
            Tinebase_Model_TagRight::applyAclSql($select);
            $tags = $this->_db->fetchAll($select);
            $tagData = $this->_getDistinctTagsAndComputeOccurrence($tags);
        } else {
            $tagData = array();
        }
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_Tag', $tagData);
    }
    
    /**
     * get distinct tags from result array and compute occurrence of tag in selection
     * 
     * @param array $_tags
     * @return array
     */
    protected function _getDistinctTagsAndComputeOccurrence(array $_tags)
    {
        $tagData = array();
        
        foreach ($_tags as $tag) {
            if (array_key_exists($tag['id'], $tagData)) {
                $tagData[$tag['id']]['selection_occurrence']++;
            } else {
                $tag['selection_occurrence'] = 1;
                $tagData[$tag['id']] = $tag;
            }
        }
        
        return $tagData;
    }
    
    /**
     * Returns tags count of a tag search
     * @todo automate the count query if paging is active!
     *
     * @param  Tinebase_Model_TagFilter $_filter
     * @return int
     */
    public function getSearchTagsCount($_filter)
    {
        $select = $_filter->getSelect();
        Tinebase_Model_TagRight::applyAclSql($select, $_filter->grant);

        $tags = new Tinebase_Record_RecordSet('Tinebase_Model_Tag', $this->_db->fetchAssoc($select));
        return count($tags);
    }

    /**
     * Return a single record
     *
     * @param string|Tinebase_Model_Tag $_id
     * @param $_getDeleted get deleted records
     * @return Tinebase_Model_FullTag
     *
     * @todo support $_getDeleted
     */
    public function get($_id, $_getDeleted = FALSE)
    {
        $tagId = ($_id instanceof Tinebase_Model_Tag) ? $_id->getId() : $_id;

        $tag = Tinebase_Tags::getInstance()->getTagsById($tagId);

        if (count($tag) == 0) {
            throw new Tinebase_Exception_NotFound("Tag $_id not found or insufficient rights.");
        }

        $fullTag = new Tinebase_Model_FullTag($tag[0]->toArray(), true);

        return $fullTag;
    }

    /**
     * Returns (bare) tags identified by its id(s)
     *
     * @param   string|array|Tinebase_Record_RecordSet  $_id
     * @param   string                                  $_right the required right current user must have on the tags
     * @param   bool                                    $_ignoreAcl
     * @return  Tinebase_Record_RecordSet               Set of Tinebase_Model_Tag
     * @throws  Tinebase_Exception_NotFound
     *
     * @todo    check context
     */
    public function getTagsById($_id, $_right = Tinebase_Model_TagRight::VIEW_RIGHT, $_ignoreAcl = false)
    {
        $tags = new Tinebase_Record_RecordSet('Tinebase_Model_Tag');

        if (!empty($_id)) {
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'tags')
                ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0')
                ->where($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $_id));
            if ($_ignoreAcl !== true) {
                Tinebase_Model_TagRight::applyAclSql($select, $_right);
            }

            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

            foreach ($this->_db->fetchAssoc($select) as $tagArray){
                $tags->addRecord(new Tinebase_Model_Tag($tagArray, true));
            }
        }
        if (count($tags) === 0 && ! empty($_id)) {
            //if (is_string($_id)) {
            //    throw new Tinebase_Exception_NotFound("Tag $_id not found or insufficient rights.");
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Tag(s) not found: ' . print_r($_id, true));
        }
        return $tags;
    }

    /**
     * Returns tags identified by its names
     *
     * @param   string  $_name name of the tag to search for
     * @param   string  $_right the required right current user must have on the tags
     * @param   string  $_application the required right current user must have on the tags
     * @param   bool    $_ignoreAcl
     * @return  Tinebase_Model_Tag
     * @throws  Tinebase_Exception_NotFound
     *
     * @todo    check context
     */
    public function getTagByName($_name, $_right = Tinebase_Model_TagRight::VIEW_RIGHT, $_application = NULL, $_ignoreAcl = false)
    {
        $select = $this->_db->select()
        ->from(SQL_TABLE_PREFIX . 'tags')
        ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0')
        ->where($this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = (?)', $_name));
        if ($_ignoreAcl !== true) {
            Tinebase_Model_TagRight::applyAclSql($select, $_right);
        }

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();

        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound("Tag with name $_name not found!");
        }

        $result = new Tinebase_Model_Tag($queryResult);

        return $result;
    }

    /**
     * Creates a single tag
     *
     * @param   Tinebase_Model_Tag
     * @param   boolean $_ignoreACL
     * @return  Tinebase_Model_Tag
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function createTag(Tinebase_Model_Tag $_tag, $_ignoreACL = FALSE)
    {
        if ($_tag instanceof Tinebase_Model_FullTag) {
            $_tag = new Tinebase_Model_Tag($_tag->toArray(), TRUE);
        }

        $currentAccountId = Tinebase_Core::getUser()->getId();

        $newId = $_tag->generateUID();
        $_tag->setId($newId);
        $_tag->occurrence = 0;
        $_tag->created_by = Tinebase_Core::getUser()->getId();
        $_tag->creation_time = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);

        switch ($_tag->type) {
            case Tinebase_Model_Tag::TYPE_PERSONAL:
                $_tag->owner = $currentAccountId;
                $this->_db->insert(SQL_TABLE_PREFIX . 'tags', $_tag->toArray());
                // for personal tags we set rights and scope temprary here,
                // this needs to be moved into Tinebase Controller later
                $right = new Tinebase_Model_TagRight(array(
                    'tag_id'        => $newId,
                    'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
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
                if (! $_ignoreACL && ! Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_SHARED_TAGS) ) {
                    throw new Tinebase_Exception_AccessDenied('Your are not allowed to create this tag');
                }
                $_tag->owner = 0;
                $this->_db->insert(SQL_TABLE_PREFIX . 'tags', $_tag->toArray());
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('No such tag type.');
                break;
        }

        // any context temporary

        $tags = $this->getTagsById($newId, NULL, true);
        return $tags[0];
    }

    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        return $this->createTag($_record);
    }

    /**
     * updates a single tag
     *
     * @param   Tinebase_Model_Tag
     * @return  Tinebase_Model_Tag
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function updateTag(Tinebase_Model_Tag $_tag)
    {
        if ($_tag instanceof Tinebase_Model_FullTag) {
            $_tag = new Tinebase_Model_Tag($_tag->toArray(), TRUE);
        }

        $currentAccountId = Tinebase_Core::getUser()->getId();
        $manageSharedTagsRight = Tinebase_Acl_Roles::getInstance()
        ->hasRight('Admin', $currentAccountId, Admin_Acl_Rights::MANAGE_SHARED_TAGS);

        if ( ($_tag->type == Tinebase_Model_Tag::TYPE_PERSONAL && $_tag->owner == $currentAccountId) ||
        ($_tag->type == Tinebase_Model_Tag::TYPE_SHARED && $manageSharedTagsRight) ) {

            $tagId = $_tag->getId();
            if (strlen($tagId) != 40) {
                throw new Tinebase_Exception_AccessDenied('Could not update non-existing tag.');
            }

            $this->_db->update(SQL_TABLE_PREFIX . 'tags', array(
                'type'               => $_tag->type,
                'owner'              => $_tag->owner,
                'name'               => $_tag->name,
                'description'        => $_tag->description,
                'color'              => $_tag->color,
                'last_modified_by'   => $currentAccountId,
                'last_modified_time' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            ), $this->_db->quoteInto('id = ?', $tagId));

            $tags = $this->getTagsById($tagId);
            return $tags[0];
        } else {
            throw new Tinebase_Exception_AccessDenied('Your are not allowed to update this tag.');
        }
    }

    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        return $this->updateTag($_record);
    }

    /**
     * Deletes (set stated deleted) tags identified by their identifiers
     *
     * @param  string|array id(s) to delete
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function deleteTags($_ids)
    {
        $tags = $this->getTagsById($_ids);
        if (count($tags) != count((array)$_ids)) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to delete the tag(s).');
        }

        $currentAccountId = Tinebase_Core::getUser()->getId();
        $manageSharedTagsRight = Tinebase_Acl_Roles::getInstance()->hasRight('Admin', $currentAccountId, Admin_Acl_Rights::MANAGE_SHARED_TAGS);
        foreach ($tags as $tag) {
            if ( ($tag->type == Tinebase_Model_Tag::TYPE_PERSONAL && $tag->owner == $currentAccountId) ||
            ($tag->type == Tinebase_Model_Tag::TYPE_SHARED && $manageSharedTagsRight) ) {
                continue;
            } else {
                throw new Tinebase_Exception_AccessDenied('You are not allowed to delete this tags');
            }
        }
        $this->_db->update(SQL_TABLE_PREFIX . 'tags', array(
            'is_deleted'   => true,
            'deleted_by'   => $currentAccountId,
            'deleted_time' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
        ), $this->_db->quoteInto('id IN (?)', $tags->getArrayOfIds()));
    }

    /**
     * Gets tags of a given record where user has the required right to
     * The tags are stored in the records $_tagsProperty.
     *
     * @param Tinebase_Record_Abstract  $_record        the record object
     * @param string                    $_tagsProperty  the property in the record where the tags are in (defaults: 'tags')
     * @param string                    $_right         the required right current user must have on the tags
     * @return Tinebase_Record_RecordSet tags of record
     */
    public function getTagsOfRecord($_record, $_tagsProperty='tags', $_right=Tinebase_Model_TagRight::VIEW_RIGHT)
    {
        $recordId = $_record->getId();
        $tags = new Tinebase_Record_RecordSet('Tinebase_Model_Tag');
        if (!empty($recordId)) {
            $select = $this->_getSelect($recordId, Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId());
            Tinebase_Model_TagRight::applyAclSql($select, $_right, 'tagging.tag_id');
            foreach ($this->_db->fetchAssoc($select) as $tagArray){
                $tags->addRecord(new Tinebase_Model_Tag($tagArray, true));
            }
        }

        $_record[$_tagsProperty] = $tags;
        return $tags;
    }

    /**
     * Gets tags of a given records where user has the required right to
     * The tags are stored in the records $_tagsProperty.
     *
     * @param Tinebase_Record_RecordSet  $_records       the recordSet
     * @param string                     $_tagsProperty  the property in the record where the tags are in (defaults: 'tags')
     * @param string                     $_right         the required right current user must have on the tags
     * @return Tinebase_Record_RecordSet tags of record
     */
    public function getMultipleTagsOfRecords($_records, $_tagsProperty='tags', $_right=Tinebase_Model_TagRight::VIEW_RIGHT)
    {
        if (count($_records) == 0) {
            // do nothing
            return;
        }

        $recordIds = $_records->getArrayOfIds();
        if (count($recordIds) == 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Can\'t get tags for records without ids');
            // do nothing
            return;
        }

        // get first record to determine application
        $first = $_records->getFirstRecord();
        $appId = Tinebase_Application::getInstance()->getApplicationByName($first->getApplication())->getId();

        $select = $this->_getSelect($recordIds, $appId);
        $select->group(array('tagging.tag_id', 'tagging.record_id'));
        Tinebase_Model_TagRight::applyAclSql($select, $_right, 'tagging.tag_id');

        $queryResult = $this->_db->fetchAll($select);
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));

        // argh: Tinebase_Model_Tag has no record_id
        /*
        $tagsOfRecords = new Tinebase_Record_RecordSet('Tinebase_Model_Tag', $queryResult);
        $tagsOfRecords->addIndices(array('record_id'));
        */

        // build array with tags (record_id => array of Tinebase_Model_Tag)
        $tagsOfRecords = array();
        foreach ($queryResult as $result) {
            $tagsOfRecords[$result['record_id']][] = new Tinebase_Model_Tag($result, true);
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Getting ' . count($tagsOfRecords) . ' tags for ' . count($_records) . ' records.');
        foreach($_records as $record) {
            //$record->{$_tagsProperty} = $tagsOfRecords->filter('record_id', $record->getId());
            $record->{$_tagsProperty} = new Tinebase_Record_RecordSet(
                'Tinebase_Model_Tag', 
            (isset($tagsOfRecords[$record->getId()])) ? $tagsOfRecords[$record->getId()] : array()
            );
        }
    }

    /**
     * sets (attachs and detaches) tags of a record
     * NOTE: Only touches tags the user has use right for
     * NOTE: Non existing personal tags will be created on the fly
     *
     * @param Tinebase_Record_Abstract  $_record        the record object
     * @param string                    $_tagsProperty  the property in the record where the tags are in (defaults: 'tags')
     */
    public function setTagsOfRecord($_record, $_tagsProperty = 'tags')
    {
        $tagsToSet = $this->_createTagsOnTheFly($_record[$_tagsProperty])->getArrayOfIds();
        $currentTags = $this->getTagsOfRecord($_record, 'tags', Tinebase_Model_TagRight::USE_RIGHT)->getArrayOfIds();

        $toAttach = array_diff($tagsToSet, $currentTags);
        $toDetach = array_diff($currentTags, $tagsToSet);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Attaching tags: ' . print_r($toAttach, true));

        $appId = Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId();
        $recordId = $_record->getId();
        foreach ($toAttach as $tagId) {
            $this->_db->insert(SQL_TABLE_PREFIX . 'tagging', array(
                'tag_id'         => $tagId,
                'application_id' => $appId,
                'record_id'      => $recordId,
            // backend property not supported by record yet
                'record_backend_id' => ''
            ));
            $this->_addOccurrence($tagId, +1);
        }
        foreach ($toDetach as $tagId) {
            $this->_db->delete(SQL_TABLE_PREFIX . 'tagging', array(
                $this->_db->quoteInto('tag_id = ?',         $tagId),
                $this->_db->quoteInto('application_id = ?', $appId),
                $this->_db->quoteInto('record_id = ?',      $recordId),
            ));
            $this->_addOccurrence($tagId, -1);
        }
    }

    /**
     * attach tag to multiple records identified by a filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param mixed                             $_tag       string|array|Tinebase_Model_Tag with existing and non-existing tag
     * @return Tinebase_Model_Tag
     */
    public function attachTagToMultipleRecords($_filter, $_tag)
    {
        // check/create tag on the fly
        $tags = $this->_createTagsOnTheFly(array($_tag));
        if (empty($tags) || count($tags) == 0) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No tags created.');
            return;
        }
        $tag = $tags->getFirstRecord();
        $tagId = $tag->getId();

        list($appName, $i, $modelName) = explode('_', $_filter->getModelName());
        $appId = Tinebase_Application::getInstance()->getApplicationByName($appName)->getId();
        $controller = Tinebase_Core::getApplicationInstance($appName, $modelName);

        // only get records user has update rights to
        $controller->checkFilterACL($_filter, 'update');
        $recordIds = $controller->search($_filter, NULL, FALSE, TRUE);

        if (empty($recordIds)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' There are no records we could attach the tag to');
            return;
        }

        // fetch ids of records already having the tag
        $alreadyAttachedIds = array();
        $select = $this->_db->select()
            ->from(array('tagging' => SQL_TABLE_PREFIX . 'tagging'), 'record_id')
            ->where($this->_db->quoteIdentifier('application_id') . ' = ?', $appId)
            ->where($this->_db->quoteIdentifier('tag_id') . ' = ? ', $tagId);

        foreach ($this->_db->fetchAssoc($select) as $tagArray) {
            $alreadyAttachedIds[] = $tagArray['record_id'];
        }

        $toAttachIds = array_diff($recordIds, $alreadyAttachedIds);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Attaching 1 Tag to ' . count($toAttachIds) . ' records.');
        foreach ($toAttachIds as $recordId) {
            $this->_db->insert(SQL_TABLE_PREFIX . 'tagging', array(
                'tag_id'         => $tagId,
                'application_id' => $appId,
                'record_id'      => $recordId,
            // backend property not supported by record yet
                'record_backend_id' => ''
                )
            );
        }
        
        $controller->concurrencyManagementAndModlogMultiple(
            $toAttachIds, 
            array('tags' => array()), 
            array('tags' => array($tag->toArray()))
        );
        
        $this->_addOccurrence($tagId, count($toAttachIds));
        
        return $tags->getFirstRecord();
    }

    /**
     * detach tag from multiple records identified by a filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param mixed                             $_tag       string|array|Tinebase_Model_Tag with existing and non-existing tag
     * @return void
     */
    public function detachTagsFromMultipleRecords($_filter, $_tag)
    {
        list($appName, $i, $modelName) = explode('_', $_filter->getModelName());
        $appId = Tinebase_Application::getInstance()->getApplicationByName($appName)->getId();
        $controller = Tinebase_Core::getApplicationInstance($appName, $modelName);
        
        if(!is_array($_tag)) $_tag = array($_tag);

        foreach($_tag as $dirtyTagId) {
            $tag = $this->getTagsById($dirtyTagId, Tinebase_Model_TagRight::USE_RIGHT)->getFirstRecord();

            if (empty($tag)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No use right for tag, detaching not possible.');
                return;
            }
            $tagId = $tag->getId();

            // only get records user has update rights to
            $controller->checkFilterACL($_filter, 'update');

            $recordIds = $controller->search($_filter, NULL, FALSE, TRUE);
            $recordIdList = '\'' . implode('\',\'',$recordIds) . '\'';

            $attachedIds = array();
            $select = $this->_db->select()
                ->from(array('tagging' => SQL_TABLE_PREFIX . 'tagging'), 'record_id')
                ->where($this->_db->quoteIdentifier('application_id') . ' = ?', $appId)
                ->where($this->_db->quoteIdentifier('tag_id') . ' = ? ', $tagId)
                ->where('record_id IN ( ' . $recordIdList . ' ) ');

            foreach ($this->_db->fetchAssoc($select) as $tagArray){
                $attachedIds[] = $tagArray['record_id'];
            }

            if (empty($attachedIds)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' There are no records we could detach the tag(s) from');
                return;
            }

            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Detaching 1 Tag from ' . count($attachedIds) . ' records.');
            foreach ($attachedIds as $recordId) {
                $this->_db->delete(SQL_TABLE_PREFIX . 'tagging', 'tag_id=\'' . $tagId . '\' AND record_id=\'' . $recordId. '\' AND application_id=\'' . $appId . '\'');
            }

            $controller->concurrencyManagementAndModlogMultiple(
                $attachedIds,
                array('tags' => array($tag->toArray())),
                array('tags' => array())
            );
            
            $this->_deleteOccurrence($tagId, count($attachedIds));
        }
    }

    /**
     * Creates missing tags on the fly and returns complete list of tags the current
     * user has use rights for.
     * Allways respects the current acl of the current user!
     *
     * @param   array|Tinebase_Record_RecordSet set of string|array|Tinebase_Model_Tag with existing and non-existing tags
     * @return  Tinebase_Record_RecordSet       set of all tags
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    protected function _createTagsOnTheFly($_mixedTags)
    {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' creating tags on the fly: ' . print_r($_mixedTags, true));

        $tagIds = array();
        foreach ($_mixedTags as $tag) {
            if (is_string($tag)) {
                $tagIds[] = $tag;
                continue;
            } else {
                if (is_array($tag)) {
                    $tag = new Tinebase_Model_Tag($tag);
                } elseif (!$tag instanceof Tinebase_Model_Tag) {
                    throw new Tinebase_Exception_UnexpectedValue('Tag could not be identified.');
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
    protected function _addOccurrence($_tag, $_toAdd)
    {
        $tagId = $_tag instanceof Tinebase_Model_Tag ? $_tag->getId() : $_tag;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " inc/decreasing tag occurrence of $tagId by $_toAdd");

        $quotedIdentifier = $this->_db->quoteIdentifier('occurrence');
        $data = array(
            'occurrence' => new Zend_Db_Expr('IF((' . $quotedIdentifier . ' + ' . (int)$_toAdd . ') >= 0,' . $quotedIdentifier . ' + ' . (int)$_toAdd . ', 0)')
        );

        $this->_db->update(SQL_TABLE_PREFIX . 'tags', $data, $this->_db->quoteInto('id = ?', $tagId));
    }

    /**
     * deletes given number from the persistent occurrence property of a given tag
     *
     * @param  Tinbebase_Tags_Model_Tag|string $_tag
     * @param  int                             $_toDel
     * @return void
     */
    protected function _deleteOccurrence($_tag, $_toDel)
    {
        $tagId = $_tag instanceof Tinebase_Model_Tag ? $_tag->getId() : $_tag;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " decreasing tag occurrence of $tagId by $_toDel");

        $quotedIdentifier = $this->_db->quoteIdentifier('occurrence');
        $data = array(
            'occurrence' => new Zend_Db_Expr('IF((' . $quotedIdentifier . ' - ' . (int)$_toDel . ') >= 0,' . $quotedIdentifier . ' - ' . (int)$_toDel . ', 0)')
        );

        $this->_db->update(SQL_TABLE_PREFIX . 'tags', $data, $this->_db->quoteInto('id = ?', $tagId));
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
        ->where($this->_db->quoteInto($this->_db->quoteIdentifier('tag_id') . ' = ?', $_tagId))
        ->group(array('tag_id', 'account_type', 'account_id'));
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        $rights = new Tinebase_Record_RecordSet('Tinebase_Model_TagRight', $rows, true);

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
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('tag_id') . ' = ?', $_tagId);
        $this->_db->delete(SQL_TABLE_PREFIX . 'tags_acl', $where);
    }

    /**
     * Sets all given tag rights
     *
     * @param Tinebase_Record_RecordSet|Tinebase_Model_TagRight
     * @return void
     * @throws Tinebase_Exception_Record_Validation
     */
    public function setRights($_rights)
    {
        $rights = $_rights instanceof Tinebase_Model_TagRight ? array($_rights) : $_rights;

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Setting ' . count($rights) . ' tag right(s).');

        foreach ($rights as $right) {
            if (! ($right instanceof Tinebase_Model_TagRight && $right->isValid())) {
                throw new Tinebase_Exception_Record_Validation('The given right is not valid!');
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
        ->where($this->_db->quoteInto($this->_db->quoteIdentifier('tag_id') . ' = ?', $_tagId))
        ->group('tag_id');
        $apps = $this->_db->fetchOne($select);

        if ($apps === '0'){
            $apps = 'any';
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' got tag contexts: ' .$apps);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' removing contexts for tag ' . $_tagId);

        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('tag_id') . ' = ?', $_tagId);
        $this->_db->delete(SQL_TABLE_PREFIX . 'tags_context', $where);
    }

    /**
     * sets all given contexts for a given tag
     *
     * @param   array  $_contexts array of application ids (0 or 'any' for all apps)
     * @param   string $_tagId
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setContexts(array $_contexts, $_tagId)
    {
        if (!$_tagId) {
            throw new Tinebase_Exception_InvalidArgument('A $_tagId is mandentory.');
        }

        if (in_array('any', $_contexts, true) || in_array(0, $_contexts, true)) {
            $_contexts = array(0);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting tag contexts: ' . print_r($_contexts, true));

        foreach ($_contexts as $context) {
            $this->_db->insert(SQL_TABLE_PREFIX . 'tags_context', array(
                'tag_id'         => $_tagId,
                'application_id' => $context
            ));
        }
    }

    /**
     * get db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_db;
    }

    /**
     * get backend type
     *
     * @return string
     */
    public function getType()
    {
        return 'Sql';
    }

    /**
     * get select for tags query
     *
     * @param string|array $_recordId
     * @param string $_applicationId
     * @param mixed $_cols
     * @return Zend_Db_Select
     */
    protected function _getSelect($_recordId, $_applicationId, $_cols = '*')
    {
        $select = $this->_db->select()
            ->from(array('tagging' => SQL_TABLE_PREFIX . 'tagging'), $_cols)
            ->join(array('tags'    => SQL_TABLE_PREFIX . 'tags'), 'tagging.tag_id = tags.id')
            ->where($this->_db->quoteIdentifier('application_id') . ' = ?', $_applicationId)
            ->where($this->_db->quoteIdentifier('record_id') . ' IN (?) ', (array) $_recordId)
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');

        return $select;
    }
}
