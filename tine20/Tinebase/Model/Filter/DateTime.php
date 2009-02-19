<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Tinebase_Model_Filter_DateTime
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters date in one property
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
            $date = new Zend_Date($result['value'], Tinebase_Record_Abstract::ISO8601LONG);
            $date->setTimezone(Tinebase_Core::get('userTimezone'));
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
            date_default_timezone_set($this->_options['timezone']);
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
            $value = $_value;
        }
        
        return $value;
    }
    
    /**
     * convert string in user time to UTC
     *
     * @param string $_string
     * @return string
     */
    protected function _convertStringToUTC($_string)
    {
        if (isset($this->_options['timezone']) && $this->_options['timezone'] !== 'UTC') {
            date_default_timezone_set($this->_options['timezone']);
            $date = new Zend_Date($_string, Tinebase_Record_Abstract::ISO8601LONG);
            $date->setTimezone('UTC');
            $result = $date->toString(Tinebase_Record_Abstract::ISO8601LONG);
            date_default_timezone_set('UTC');
        } else {
            $result = $_string;
        }
        
        return $result;
    }
}
