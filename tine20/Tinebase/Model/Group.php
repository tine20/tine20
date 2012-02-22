<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for the group object
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @property    string  id
 * @property    string  name
 * @property    array   members
 * @property	string  visibility
 */
class Tinebase_Model_Group extends Tinebase_Record_Abstract
{
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
    
   /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * @see Tinebase_Record_Abstract
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators = array(
            'id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            'container_id'  => array('allowEmpty' => true),
            'list_id'       => array('allowEmpty' => true),
            'name'          => array('presence' => 'required'),
            'description'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            'members'       => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
            'email'         => array('allowEmpty' => true),
            'visibility'    => array(new Zend_Validate_InArray(array('hidden', 'displayed')), Zend_Filter_Input::DEFAULT_VALUE => 'displayed')
        );
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
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
        if($_groupId instanceof Tinebase_Model_Group) {
            if(empty($_groupId->id)) {
                throw new Tinebase_Exception_InvalidArgument('groupId can not be empty');
            }
            $groupId = (string) $_groupId->id;
        } else {
            $groupId = (string) $_groupId;
        }
        
        if(empty($groupId)) {
            throw new Tinebase_Exception_InvalidArgument('groupId can not be empty');
        }
        
        return $groupId;
    }
}
