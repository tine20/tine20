<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * filters for events with the given attendee
 * 
 * 
 * @package     Calendar
 */
class Calendar_Model_AttenderFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'in',
        3 => 'specialNode' // one of {allResources}
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
                $this->_value = array($_value);
                break;
            case 'in':
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
            $this->_value = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $this->_value);
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
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') value: ' . print_r($this->_value, true));
        foreach ($this->_value as $attenderValue) {
            if (in_array($attenderValue['user_type'], array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))) {
                
                // transform CURRENTCONTACT
                $attenderValue['user_id'] = $attenderValue['user_id'] == Addressbook_Model_Contact::CURRENTCONTACT ? 
                    Tinebase_Core::getUser()->contact_id : 
                    $attenderValue['user_id'];
                
                $attendee = array(
                    array(
                        'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                        'user_id'   => $attenderValue['user_id']
                    ),
                    array(
                        'user_type' => Calendar_Model_Attender::USERTYPE_GROUPMEMBER,
                        'user_id'   => $attenderValue['user_id']
                    )
                );
            } else {
                $attendee = array($attenderValue);
            }
            
            foreach ($attendee as $attender) {
            	$gs->orWhere(
            	    $adapter->quoteInto($adapter->quoteIdentifier('attendee.user_type') . ' = ?', $attender['user_type']) . ' AND ' .
                    $adapter->quoteInto($adapter->quoteIdentifier('attendee.user_id') .   ' = ?', $attender['user_id'])
            	);
            }
        }
        $gs->appendWhere(Zend_Db_Select::SQL_OR);
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        if ($_valueToJson) {
            Calendar_Model_Attender::resolveAttendee($this->_value);
        }
        
        $result = array(
            'field'     => $this->_field,
            'operator'  => $this->_operator,
            'value'     => $this->_operator == 'equals' ? $this->_value[0]->toArray($_valueToJson) : $this->_value->toArray($_valueToJson)
        );
        
        return $result;
    }
}