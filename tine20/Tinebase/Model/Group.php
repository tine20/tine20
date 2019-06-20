<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for the group object
 * 
 * @package     Tinebase
 * @subpackage  Group
 *
 * @property    string  id
 * @property    string  name
 * @property    string  description
 * @property    string  email
 * @property    array   members
 * @property    string  visibility
 * @property    string  list_id
 */
class Tinebase_Model_Group extends Tinebase_Record_Abstract
{
    /**
    * hidden from addressbook
    *
    * @var string
    */
    const VISIBILITY_HIDDEN    = 'hidden';
    
    /**
     * visible in addressbook
     *
     * @var string
     */
    const VISIBILITY_DISPLAYED = 'displayed';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'id'            => 'StringTrim',
        'name'          => 'StringTrim',
        'description'   => 'StringTrim',
    );

    protected $_validators = array(
        'id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container_id'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'list_id'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'          => array(Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED),
        'description'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'members'       => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'email'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'visibility'    => array(
            ['InArray', [self::VISIBILITY_HIDDEN, self::VISIBILITY_DISPLAYED]],
            Zend_Filter_Input::DEFAULT_VALUE => self::VISIBILITY_DISPLAYED
        ),
        'xprops'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'created_by'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * name of fields containing datetime or or an array of datetime
     * information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
    );

    protected static $_replicable = true;
    
    /**
     * converts a int, string or Tinebase_Model_Group to a groupid
     *
     * @param   int|string|Tinebase_Model_Group $_groupId the groupid to convert
     * @return  string
     * @throws  Tinebase_Exception_InvalidArgument
     * 
     * @todo rename this function because we now have string ids
     */
    static public function convertGroupIdToInt($_groupId)
    {
        return self::convertId($_groupId, 'Tinebase_Model_Group');
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::setFromArray()
     */
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        
        // sanitize members (could be an array of user arrays -> expecting to contain only ids)
        if (isset($this->members) && is_array($this->members) && count($this->members) > 0 && is_array($this->members[0])) {
            $memberIds = array();
            foreach ($this->members as $member) {
                $memberIds[] = $member['id'];
            }
            $this->members = $memberIds;
        }
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return static::$_replicable;
    }

    /**
     * @param boolean $isReplicable
     */
    public static function setReplicable($isReplicable)
    {
        static::$_replicable = (bool)$isReplicable;
    }

    /**
     * undoes the change stored in the diff
     *
     * will (re)load and populate members property if required
     *
     * @param Tinebase_Record_Diff $diff
     * @return void
     */
    public function undo(Tinebase_Record_Diff $diff)
    {
        $members = null;
        $oldMembers = null;
        // clone diff here to prevent accidental/unintended change
        $diffWithoutMembers = clone $diff;
        if (isset($diff->diff['members'])) {
            $members = $diff->diff['members'];
            unset($diffWithoutMembers->xprops('diff')['members']);
        }
        if (isset($diff->oldData['members'])) {
            $oldMembers = $diff->oldData['members'];
            unset($diffWithoutMembers->xprops('oldData')['members']);
        }

        parent::undo($diffWithoutMembers);

        if (null === $members || null === $oldMembers) {
            return;
        }

        $currentMembers = Tinebase_Group::getInstance()->getGroupMembers($this->getId());

        if (!empty($remove = array_diff($members, $oldMembers))) {
            $currentMembers = array_diff($currentMembers, $remove);
        }
        if (!empty($add = array_diff($oldMembers, $members))) {
            $currentMembers = array_merge($currentMembers, $add);
        }
        $this->members = $currentMembers;
    }

    // TODO remove the runConvert methods when migration to Modelconfig!
    public function runConvertToRecord()
    {
        if (isset($this->_properties['xprops'])) {
            $this->_properties['xprops'] = json_decode($this->_properties['xprops'], true);
        }
    }

    public function runConvertToData()
    {
        if (isset($this->_properties['xprops']) && is_array($this->_properties['xprops'])) {
            $this->_properties['xprops'] = json_encode($this->_properties['xprops']);
        }
    }
}
