<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filters for events with the given attendee
 * 
 * @package     Calendar
 * @subpackage  Model
 */
class Calendar_Model_AttenderFilter extends Tinebase_Model_Filter_Abstract 
{
    const USERTYPE_MEMBEROF = 'memberOf';
    
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'not',
        2 => 'in',
        3 => 'notin',
        4 => 'specialNode', // one of {allResources}
        5 => 'hasSomeExcept',
        6 => 'notHasSomeExcept',
        7 => 'hasSomeExceptIn',
        8 => 'notHasSomeExceptIn',
    );

    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        switch ($this->_operator) {
            case 'equals':
            case 'not':
            case 'hasSomeExcept':
            case 'notHasSomeExcept':
                $this->_value = array($_value);
                break;
            case 'in':
            case 'notin':
            case 'hasSomeExceptIn':
            case 'notHasSomeExceptIn':
                $this->_value = $_value;
                break;
            case 'specialNode' :
                switch ($_value) {
                    case 'all':
                        $this->_value = $_value;
                        break;
                    case 'allResources':
                        $this->_value = array();
                        $resources = Calendar_Controller_Resource::getInstance()->getAll();
                        foreach ($resources as $resource) {
                            $this->_value[] = array(
                                'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
                                'user_id'   => $resource->getId()
                            );
                        }
                        break;
                    default:
                        throw new Tinebase_Exception_UnexpectedValue('specialNode not supported.');
                        break;
                }
        }
        
        if ($this->_value !== 'all' && ! $this->_value instanceof Tinebase_Record_RecordSet) {
            $this->_value = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $this->_value, TRUE);
        }
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if ($this->_value === 'all') {
            $_select->where('1=1');
            return;
        }
        
        $gs = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
        $adapter = $_backend->getAdapter();
        $isExcept = strpos($this->_operator, 'Except') !== false;
        $sign = $isExcept ? '<>' : '=';
        
        foreach ($this->_value as $attenderValue) {
            if (! isset($attenderValue['user_id']) || empty($attenderValue['user_id'])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' Skipping invalid attender: ' . print_r($attenderValue, true));
                continue;
            }

            if (in_array($attenderValue['user_type'], array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))) {
                
                // @todo user_id might contain filter in the future -> get userids from addressbook controller with contact filter
                
                // transform CURRENTCONTACT
                $attenderValue['user_id'] = $attenderValue['user_id'] == Addressbook_Model_Contact::CURRENTCONTACT ? 
                    Tinebase_Core::getUser()->contact_id : 
                    $attenderValue['user_id'];
                
                $attendee = array(
                    array(
                        'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                        'user_id'   => $attenderValue['user_id']
                    )
                );
                if (!$isExcept) {
                    $attendee[] = array(
                        'user_type' => Calendar_Model_Attender::USERTYPE_GROUPMEMBER,
                        'user_id' => $attenderValue['user_id']
                    );
                }
            } else if ($attenderValue['user_type'] == self::USERTYPE_MEMBEROF) {
                // resolve group members
                $group = Tinebase_Group::getInstance()->getGroupById($attenderValue['user_id']);
                
                $attendee = array();
                
                // fetch list only if list_id is not NULL, otherwise we get back an empty list object
                if (!empty($group->list_id)) {
                    $contactList = Addressbook_Controller_List::getInstance()->get($group->list_id);
                    
                    foreach ($contactList->members as $member) {
                        $attendee[] = array(
                            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                            'user_id'   => $member
                        );
                        if (!$isExcept) {
                            $attendee[] = array(
                                'user_type' => Calendar_Model_Attender::USERTYPE_GROUPMEMBER,
                                'user_id' => $member
                            );
                        }
                    }
                }
            } else {
                if (is_object($attenderValue->user_id)) {
                    $attenderValue = array(
                        'user_type' => $attenderValue->user_type,
                        'user_id'   => $attenderValue->user_id->getId()
                    );
                } else if ($attenderValue->user_type === Calendar_Model_Attender::USERTYPE_ANY && $attenderValue->user_id) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 'Unsupported user type ANY given - switching to USER');
                    // TODO support other types as well, we could check for existing user_id in contact/list/resource/.. backends
                    $attenderValue->user_type = Calendar_Model_Attender::USERTYPE_USER;
                }
                $attendee = array($attenderValue);
            }
            
            foreach ($attendee as $attender) {
                $gs->orWhere(
                    ($isExcept ? '' : $adapter->quoteInto($adapter->quoteIdentifier('attendee.user_type') . ' = ?', $attender['user_type']) . ' AND ') .
                    $adapter->quoteInto($adapter->quoteIdentifier('attendee.user_id') .   ' ' . $sign . ' ?', $attender['user_id'])
                );
            }
        }

        if (substr($this->_operator, 0, 3) === 'not') {
            // join attendee to be excluded as a new column. records having this column NULL don't have the attendee
            $dname = 'attendee-not-' . Tinebase_Record_Abstract::generateUID(5);
            $_select->joinLeft(
            /* table  */
                array($dname => $_backend->getTablePrefix() . 'cal_attendee'),
                /* on     */
                $adapter->quoteIdentifier($dname . '.cal_event_id') . ' = ' . $adapter->quoteIdentifier($_backend->getTableName() . '.id') .
                ' AND ' . $gs->getSQL(),
                /* select */
                array($dname => $_backend->getDbCommand()->getAggregate($dname . '.id')));
            $_select->having($_backend->getDbCommand()->getAggregate($dname . '.id') . ' IS NULL');
        } else {
            if ($isExcept) {
                $dname = 'attendee-hasSome-' . Tinebase_Record_Abstract::generateUID(5);
                $_select->joinLeft(
                /* table  */
                    array($dname => $_backend->getTablePrefix() . 'cal_attendee'),
                    /* on     */
                    $adapter->quoteIdentifier($dname . '.cal_event_id') . ' = ' . $adapter->quoteIdentifier($_backend->getTableName() . '.id') .
                    ' AND ' . $gs->getSQL(),
                    /* select */
                    array($dname => $_backend->getDbCommand()->getAggregate($dname . '.id')));
                $_select->having($_backend->getDbCommand()->getAggregate($dname . '.id') . ' IS NOT NULL');
            } else {
                $gs->appendWhere(Zend_Db_Select::SQL_OR);
            }
        }
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($_valueToJson) {
            try {
                Calendar_Model_Attender::resolveAttendee($this->_value, true, null, true);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                Tinebase_Exception::log($teia);
            }
        }
        
        $result['value'] = $this->_operator == 'equals' ? $this->_value[0]->toArray($_valueToJson) : $this->_value->toArray($_valueToJson);
        
        return $result;
    }
}
