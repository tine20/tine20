<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * returns array with the filter settings of this filter
     * - convert value to user timezone
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
       
        if ($this->_operator != 'within' && $_valueToJson == true) {
            $date = new Tinebase_DateTime($result['value']);
            $date->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
            $result['value'] = $date->toString(Tinebase_Record_Abstract::ISO8601LONG);
        }
        
        return $result;
    }
    
    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        if ($this->_operator != 'within') {
            $_value = $this->_convertStringToUTC($_value);
        }
        
        $this->_value = $_value;
    }
    
    /**
     * calculates the date filter values
     *
     * @param string $_operator
     * @param string $_value
     * @param string $_dateFormat
     * @return array|string date value
     */
    protected function _getDateValues($_operator, $_value)
    {
        if ($_operator === 'within') {
            // get beginning / end date and add 00:00:00 / 23:59:59
            date_default_timezone_set(array_key_exists('timezone', $this->_options) && ! empty($this->_options['timezone']) ? $this->_options['timezone'] : Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
            $value = parent::_getDateValues(
                $_operator, 
                $_value
            );
            $value[0] .= ' 00:00:00';
            $value[1] .= ' 23:59:59';
            date_default_timezone_set('UTC');
            
            // convert to utc
            $value[0] = $this->_convertStringToUTC($value[0]);
            $value[1] = $this->_convertStringToUTC($value[1]);
            
        } else  {
            $value = ($_value instanceof DateTime) ? $_value->toString(Tinebase_Record_Abstract::ISO8601LONG) : $_value;
        }
        
        return $value;
    }
}
