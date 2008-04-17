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
 */
class Tinebase_Tags
{
    /**
     * Type of a shared tag
     */
    const TYPE_SHARED = 'shared';
    /**
     * Type of a personal tag
     */
    const TYPE_PERSONAL = 'personal';
    
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
     * 
     * @param  Tinebase_Tags_Model_Filter $_filter
     * @param  Tinebase_Model_Pagination  $_paging
     * @param  bool                       $ignoreAcl
     * @return Tinebase_Record_RecordSet  Set of Tinebase_Tags_Model_Tag
     */
    public function searchTags($_filter, $_paging, $_ignoreAcl=false)
    {
        $select = $_filter->getSelect();
        $_paging->appendPagination($select);
        
        return new Tinebase_Record_RecordSet('Tinebase_Tags_Model_Tag', $this->_db->fetchAssoc($select));
    }
    
    /**
     * Returns tags count of a tag search
     * @todo automate the count query if paging is active!
     * 
     * @param  Tinebase_Tags_Model_Filter $_filter
     * @param  bool                       $ignoreAcl
     * @return int
     */
    public function getSearchTagsCount($_filter, $_ignoreAcl=false)
    {
        $select = $_filter->getSelect();
        $tags = new Tinebase_Record_RecordSet('Tinebase_Tags_Model_Tag', $this->_db->fetchAssoc($select));
        return count($tags);
    }
    
    /**
     * Returns a tag identified by its id
     * 
     * @param  string $_id
     * @return Tinebase_Tags_Model_Tag
     */
    public function getTagById($_id)
    {
        $tag = new Tinebase_Tags_Model_Tag($this->_db->fetchRow(
            $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'tags')
                ->where($this->_db->quoteInto('id = ?', $_id),
            Zend_Db::FETCH_ASSOC
        )), true);
        
        if ($tag->getId() != $_id) {
            throw new Exception("Tag not found");
        }
        return $tag;
    }
    
    /**
     * Creates a single tag
     * 
     * @param  Tinebase_Tags_Model_Tag
     * @return Tinebase_Tags_Model_Tag
     */
    public function createTag(Tinebase_Tags_Model_Tag $_tag)
    {
        $newId = $_tag->generateUID();
        $_tag->setId($newId);
        $_tag->occurrence = 0;
        $_tag->created_by = Zend_Registry::get('currentAccount')->getId();
        $_tag->creation_time = Zend_Date::now()->getIso();
        
        switch ($_tag->type) {
            case self::TYPE_PERSONAL:
                // tags_acl (personal => NULL)
                // tags_context (personal => NULL)
                $_tag->owner = Zend_Registry::get('currentAccount')->getId();
                break;
            case self::TYPE_SHARED:
                $_tag->owner = 0;
                break;
            default:
                throw new Exception('No such tag type');
        }
        $this->_db->insert(SQL_TABLE_PREFIX . 'tags', $_tag->toArray());
        return $this->getTagById($newId);
    }
    
    /**
     * Returns all tags of a given record
     * 
     * @param int|string|Tinebase_Model_Application $_application   application of record
     * @param string                                $_recordId      id of record
     * @param string                                $_recordBackend backend the record is in
     * @param bool                                  $_ignoreAcl     ignore acl restricions
     */
    public function getTagsOfRecord($_application, $_recordId, $_recordBackend, $_ignoreAcl)
    {
        
    }
    
    /**
     * Attaches Tgas to Record
     * NOTE: Only touches Tags in the users scope, unless $_ignoreAcl is true
     * NOTE: Non existing personal tags will be created on the fly
     * 
     * @param int|string|Tinebase_Model_Application $_application   application of record
     * @param string                                $_recordId      id of record
     * @param string                                $_recordBackend backend the record is in
     * @param Tinebase_Record_RecordSet             $_tags          tags to set
     * @param bool                                  $_ignoreAcl     ignore acl restricions and reset all tags of the record
     */
    public function attachTagsToRecord($_application, $_recordId, $_recordBackend, $_tags, $_ignoreAcl)
    {
        
    }
    
}