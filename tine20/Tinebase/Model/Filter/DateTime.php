<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * Tinebase_Model_Filter_DateTime
 * 
 * filters date in one property
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_DateTime extends Tinebase_Model_Filter_Date
{
    /**
     * calculates the date filter values
     *
     * @param string $_operator
     * @param string $_value
     * @return array|string date value
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _getDateValues($_operator, $_value)
    {
        if ($_operator === 'within') {
            if (! is_array($_value)) {
                // get beginning / end date and add 00:00:00 / 23:59:59
                date_default_timezone_set((isset($this->_options['timezone']) || array_key_exists('timezone', $this->_options)) && !empty($this->_options['timezone']) ? $this->_options['timezone'] : Tinebase_Core::getUserTimezone());
                $value = parent::_getDateValues($_operator, $_value);
                $value[0] .= ' 00:00:00';
                $value[1] .= ' 23:59:59';
                date_default_timezone_set('UTC');

            } else {
                if (isset($_value['from']) && isset($_value['until'])) {
                    $value[0] = $_value['from'] instanceof Tinebase_DateTime
                        ? $_value['from']->toString() : $_value['from'];
                    $value[1] = $_value['until'] instanceof Tinebase_DateTime
                        ? $_value['until']->toString() : $_value['until'];
                } else {
                    throw new Tinebase_Exception_UnexpectedValue('did expect from and until in value');
                }
            }

            // convert to utc
            $value[0] = $this->_convertStringToUTC($value[0]);
            $value[1] = $this->_convertStringToUTC($value[1]);

        } elseif ($_operator === 'inweek') {
            // get beginning / end date and add 00:00:00 / 23:59:59
            date_default_timezone_set((isset($this->_options['timezone']) || array_key_exists('timezone', $this->_options)) && !empty($this->_options['timezone']) ? $this->_options['timezone'] : Tinebase_Core::getUserTimezone());
            $value = parent::_getDateValues($_operator, $_value);
            $value[0] .= ' 00:00:00';
            $value[1] .= ' 23:59:59';
            date_default_timezone_set('UTC');
        } else {
            $value = ($_value instanceof DateTime) ? $_value->toString(Tinebase_Record_Abstract::ISO8601LONG) : $_value;
        }
        
        return $value;
    }

    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        $this->_orgValue = $_value;

        if ($this->_operator !== 'within' && $_value) {
            $_value = $this->_convertStringToUTC($_value);
        }

        $this->_value = $_value;
    }

    /**
     * convert string in user time to UTC
     *
     * @param string $_string
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _convertStringToUTC($_string)
    {
        if (preg_match('/^(day|week|month|year)/', $_string, $matches)) {
            if ($matches[1] === 'day') {
                $date = $this->_getDate(null);
            } else {
                throw new Tinebase_Exception_InvalidArgument('date string not recognized / not supported: ' . $_string);
            }
            switch ($this->getOperator()) {
                case 'before':
                case 'after_or_equals':
                    $date->setTime(0, 0, 0);
                    break;
                case 'after':
                case 'before_or_equals':
                    $date->setTime(23, 59, 59);
                    break;
            }
            switch ($_string) {
                case Tinebase_Model_Filter_Date::DAY_THIS:
                    $string = $date->toString();
                    break;
                case Tinebase_Model_Filter_Date::DAY_LAST:
                    $string = $date->subDay(1)->toString();
                    break;
                case Tinebase_Model_Filter_Date::DAY_NEXT:
                    $string = $date->addDay(1)->toString();
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument('date string not recognized / not supported: ' . $_string);
            }
            return parent::_convertStringToUTC($string);
        }
        $this->_orgValue = parent::_convertStringToUTC($_string);
        return $this->_orgValue;
    }

    /**
     * returns array with the filter settings of this filter
     * - convert value to user timezone
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);

        if ($this->_operator != 'within' && $_valueToJson == true && $result['value']
                && preg_match('/^\d\d\d\d-\d\d-\d\d/', $result['value'])) {
            $date = new Tinebase_DateTime($result['value'],
                isset($this->_options['timezone']) ? $this->_options['timezone'] : null);
            $date->setTimezone(Tinebase_Core::getUserTimezone());
            $result['value'] = $date->toString(Tinebase_Record_Abstract::ISO8601LONG);
        }

        return $result;
    }
}
