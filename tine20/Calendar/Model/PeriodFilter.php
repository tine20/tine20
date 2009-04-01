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
 * filters for events in the given period
 * 
 * 
 * @package     Calendar
 */
class Calendar_Model_PeriodFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * const for direct events
     * 
     * Direct events are those, which duration (events dtstart -> dtend)
     *   reaches in the seached period.
     */
    const TYPE_DIRECT = 'Direct';

    /**
     * const for recur base events
     *
     * Recur Base events are those recuring events which potentially could have
     *   recurances in the searched period
     */
    const TYPE_RECURBASE = 'RecurBase';
    
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'within',
    );
    
    /**
     * Event type to filter for. 
     *
     * @var string
     *
    private static $_type = NULL;
    */
    
    /**
     * @var string
     */
    protected $_from = NULL;
    
    /**
     * @var string
     */
    protected $_until = NULL;
    
    /**
     * Sets the event type to filter for
     *
     * @param string $_type
     *
    public static function setType($_type)
    {
        if (! in_array($_type, array(self::TYPE_DIRECT, self::TYPE_RECURBASE))) {
            throw new Tinebase_Exception_UnexpectedValue("Event type '$_type' is not known");
        }
        
        self::$_type = $_type;
    }
    */
    
    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        if (is_array($_value) && (isset($_value['from']) && isset($_value['until']))) {
            $this->_from = $this->_convertStringToUTC($_value['from']);
            $this->_until = $this->_convertStringToUTC($_value['until']);
        } else {
            throw new Tinebase_Exception_UnexpectedValue('Period must be an array with from and until properties');
        }
    }
    
    /*
    public function appendDirectEventFilterSql($_select)
    {
        $filter = new Calendar_Model_EventFilter(array(
           array('condition' => 'AND', 'filters' => array(
               array('field' => 'dtstart', 'operator' => 'after',  'value' => $this->_from),
               array('field' => 'dtstart', 'operator' => 'before', 'value' => $this->_until),
           )),
           array('condition' => 'AND', 'filters' => array(
               array('field' => 'dtend', 'operator' => 'after',  'value' => $this->_from),
               array('field' => 'dtend', 'operator' => 'before', 'value' => $this->_until),
           )),
        ), Calendar_Model_EventFilter::CONDITION_OR);
        
        return $filter->appendFilterSql($_select);
    }
    
    public function appendRecurBaseEventFilterSql($_select)
    {
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'rrule',        'operator' => 'not',    'value' => NULL),
            array('field' => 'dtstart',      'operator' => 'before', 'value' => $this->_until),
            array('condition' => 'OR', 'filters' => array(
                array('field' => 'rrule_until',  'operator' => 'after',   'value' => $this->_from),
                array('field' => 'rrule_until',  'operator' => 'equals',  'value' => NULL),
            )),
        ));
        
        return $filter->appendFilterSql($_select);
    }
    */
    
    /**
     * appeds sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $filter = new Calendar_Model_EventFilter(array(
            array('condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_AND, 'filters' => array(
               array('field' => 'rrule', 'operator' => 'isnull',  'value' => NULL),
               array('field' => 'dtstart', 'operator' => 'before',  'value' => $this->_until),
               array('field' => 'dtend',   'operator' => 'after',   'value' => $this->_from),
            )),
            array('condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_AND, 'filters' => array(
                array('field' => 'rrule',        'operator' => 'notnull', 'value' => NULL),
                array('field' => 'dtstart',      'operator' => 'before',  'value' => $this->_until),
                array('condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR, 'filters' => array(
                    array('field' => 'rrule_until',  'operator' => 'after',   'value' => $this->_from),
                    array('field' => 'rrule_until',  'operator' => 'isnull',  'value' => NULL),
                )),
            ))
        ), Tinebase_Model_Filter_FilterGroup::CONDITION_OR);
        
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $filter, $_backend);
    }
}