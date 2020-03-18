<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filters for events in the given period
 * 
 * 
 * @package     Calendar
 * @todo: period should filter [start, end[ at the moment its ]start, end[
 */
class Calendar_Model_PeriodFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'within',
    );
    
    /**
     * @var string
     */
    protected $_from = NULL;
    
    /**
     * @var string
     */
    protected $_until = NULL;
    
    /**
     * @var string
     */
    protected $_disabled = FALSE;
    
    /**
     * returns from datetime
     *
     * @return Tinebase_DateTime
     */
    public function getFrom()
    {
        return new Tinebase_DateTime($this->_from);
    }
    
    /**
     * returns until datetime
     *
     * @return Tinebase_DateTime
     */
    public function getUntil()
    {
        return new Tinebase_DateTime($this->_until);
    }

    /**
     * returns combined value
     *
     * @return array
     */
    public function getValue()
    {
        return array(
            'from'  => $this->getFrom(),
            'until' => $this->getUntil(),
        );
    }
    /**
     * set this filter en/disabled
     * 
     * @param bool $_disabled
     */
    public function setDisabled($_disabled=TRUE)
    {
        $oldDisabled = $this->_disabled;
        $this->_disabled = $_disabled;
        
        return $oldDisabled;
    }
    
    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        if (is_array($_value) && (isset($_value['from']) && isset($_value['until']))) {
            if (is_string($_value['from']) && substr($_value['from'], 0, 1) === 'P') {
                $_value['from'] = $this->_calculatePeriod($_value['from']);
            }
            if (is_string($_value['until']) && substr($_value['until'], 0, 1) === 'P') {
                $_value['until'] = $this->_calculatePeriod($_value['until']);
            }

            // convert DateTime to Tinebase_DateTime
            if (is_object($_value['from']) && get_class($_value['from']) === 'DateTime') {
                $_value['from'] = new Tinebase_DateTime($_value['from']);
            }
            if (is_object($_value['until']) && get_class($_value['until']) === 'DateTime') {
                $_value['until'] = new Tinebase_DateTime($_value['until']);
            }
            
            $from = $_value['from'] instanceof Tinebase_DateTime ? $_value['from']->get(Tinebase_Record_Abstract::ISO8601LONG) : $_value['from'];
            $until = $_value['until'] instanceof Tinebase_DateTime ? $_value['until']->get(Tinebase_Record_Abstract::ISO8601LONG) : $_value['until'];
            
            $this->_from = $this->_convertStringToUTC($from);
            $this->_until = $this->_convertStringToUTC($until);
        } else {
            throw new Tinebase_Exception_UnexpectedValue('Period must be an array with from and until properties');
        }
    }

    protected function _calculatePeriod($_period)
    {
        if (isset($this->_options['timezone'])) {
            $tz = $this->_options['timezone'];
        } else {
            $tz = 'UTC';
        }

        $now = new Tinebase_DateTime('now', $tz);
        if (strlen($_period) > 1) {
            if (strpos($_period, '-') !== false) {
                $_period = str_replace('-', '', $_period);
                $now->sub(new DateInterval($_period));
            } else {
                $now->add(new DateInterval($_period));
            }
        }

        return $now;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if ($this->_disabled === TRUE) {
            return;
        }
        
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
                    array('field' => 'rrule_until',  'operator' => 'equals',  'value' => $this->_from),
                    array('field' => 'rrule_until',  'operator' => 'after',   'value' => $this->_from),
                    array('field' => 'rrule_until',  'operator' => 'isnull',  'value' => NULL),
                )),
            ))
        ), Tinebase_Model_Filter_FilterGroup::CONDITION_OR);

        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $filter, $_backend);
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
        if ($_valueToJson && Tinebase_Core::getUserTimezone() !== 'UTC') {
            $result['value'] = [
                'from' => (new Tinebase_DateTime($this->_from))->setTimezone(Tinebase_Core::getUserTimezone())
                    ->toString(),
                'until' => (new Tinebase_DateTime($this->_until))->setTimezone(Tinebase_Core::getUserTimezone())
                    ->toString(),
            ];
        } else {
            $result['value'] = [
                'from' => $this->_from,
                'until' => $this->_until,
            ];
        }
        
        return $result;
    }
}
