<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * defines the datatype for a set of rights for one tag and one account
 * 
 * @package     Tinebase
 * @subpackage  Tags
 */
class Tinebase_Model_TagRight extends Tinebase_Record_Abstract
{
    /**
     * Right to view/see/read the tag
     */
    const VIEW_RIGHT = 'view';
    /**
     * Right to attach the tag to a record
     */
    const USE_RIGHT = 'use';
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'           => array('Alnum', 'allowEmpty' => true),
        'tag_id'       => array('Alnum', 'allowEmpty' => true),
        'account_type' => array(array('InArray', array(
            Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, 
            Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, 
            Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
        )), 'presence' => 'required', 'allowEmpty' => false),
        'account_id'   => array('presence' => 'required', 'allowEmpty' => TRUE, 'default' => '0'),
        'view_right'   => array('presence' => 'required', 'default' => false, 
            array('InArray', array(true, false)), 'allowEmpty' => true),
        'use_right'    => array('presence' => 'required', 'default' => false, 
            array('InArray', array(true, false)), 'allowEmpty' => true),
    );
    
    /**
     * overwrite default constructor as convinience for data from database
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        if (is_array($_data) && isset($_data['account_right'])) {
            $rights = explode(',', $_data['account_right']);
            $_data['view_right'] = in_array(self::VIEW_RIGHT, $rights);
            $_data['use_right']  = in_array(self::USE_RIGHT, $rights);
        }
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * Applies the requierd params for tags acl to the given select object
     * 
     * @param  Zend_Db_Select $_select
     * @param  string         $_right      required right
     * @param  string         $_idProperty property of tag id in select statement
     * @return void
     */
    public static function applyAclSql($_select, $_right = self::VIEW_RIGHT, $_idProperty = 'id')
    {
        if (empty($_right)) {
            throw new Tinebase_Exception_InvalidArgument('right is empty');
        }
        
        $db = Tinebase_Core::getDb();
        if($_idProperty == 'id'){
            $_idProperty = $db->quoteIdentifier('id');
        }
        
        if (! is_object(Tinebase_Core::getUser())) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                ' Cannot apply ACL, no user object found. This might happen during setup/update.');
            return;
        }
        
        $currentAccountId = Tinebase_Core::getUser()->getId();
        $manageSharedTagsRight = Tinebase_Acl_Roles::getInstance()
            ->hasRight('Admin', $currentAccountId, Admin_Acl_Rights::MANAGE_SHARED_TAGS);
        $currentGroupIds = Tinebase_Group::getInstance()->getGroupMemberships($currentAccountId);
        $groupCondition = ( !empty($currentGroupIds) ) ? ' OR (' . $db->quoteInto($db->quoteIdentifier('acl.account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) .
            ' AND ' . $db->quoteInto($db->quoteIdentifier('acl.account_id') . ' IN (?)', $currentGroupIds) . ' )' : '';
        
        $where = '((' . $db->quoteInto($db->quoteIdentifier('acl.account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE) . ' OR (' .
            $db->quoteInto($db->quoteIdentifier('acl.account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) . ' AND ' .
            $db->quoteInto($db->quoteIdentifier('acl.account_id')   . ' = ?', $currentAccountId) . ' ) ' .
            $groupCondition . ') AND ' . $db->quoteInto($db->quoteIdentifier('acl.account_right') . ' = ?', $_right) .
            ')' . ($manageSharedTagsRight ? $db->quoteInto(' OR tags.type = ?', Tinebase_Model_Tag::TYPE_SHARED) : '');
        
        $_select->join(array('acl' => SQL_TABLE_PREFIX . 'tags_acl'), $_idProperty . ' = '. $db->quoteIdentifier('acl.tag_id'), array() )
            ->where($where);
    }
}
