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
 * defines the datatype for a set of rights for one tag and one account
 * 
 * @package     Tinebase
 * @subpackage  Tags
 */
class Tinebase_Tags_Model_Right extends Tinebase_Record_Abstract
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
        'tag_id'       => array('Alnum', 'presence' => 'required', 'allowEmpty' => false),
        'account_type' => array('InArray' => array('user', 'group', 'anyone'), 'presence' => 'required', 'allowEmpty' => false),
        'account_id'   => array('Alnum', 'presence' => 'required', 'allowEmpty' => false),
        'view_right'   => array('presence' => 'required', 'default' => false, 'InArray' => array(true, false), 'allowEmpty' => true),
        'use_right'    => array('presence' => 'required', 'default' => false, 'InArray' => array(true, false), 'allowEmpty' => true),
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
    public static function applyAclSql($_select, $_right, $_idProperty='id')
    {
        $db = Zend_Registry::get('dbAdapter');
        $currentAccountId = Zend_Registry::get('currentAccount')->getId();
        $currentGroupIds = Tinebase_Group::getInstance()->getGroupMemberships($currentAccountId);
        $groupCondition = ( !empty($currentGroupIds) ) ? ' OR (' . $db->quoteInto('acl.account_type = ?', 'group') . 
            ' AND ' . $db->quoteInto('acl.account_id IN (?)', $currentGroupIds, Zend_Db::INT_TYPE) . ' )' : '';
        
        $where = $db->quoteInto('acl.account_type = ?', 'anyone') . ' OR (' .
            $db->quoteInto('acl.account_type = ?', 'user') . ' AND ' . 
            $db->quoteInto('acl.account_id = ?', $currentAccountId, Zend_Db::INT_TYPE) . ' ) ' .
            $groupCondition;
        
        $_select->join(array('acl' => SQL_TABLE_PREFIX . 'tags_acl'), $_idProperty . ' = acl.tag_id', array() )
            ->where($where)
            ->where('acl.account_right = ?', $_right);
    }
}