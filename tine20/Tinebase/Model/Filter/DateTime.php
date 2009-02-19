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
        
        if ($_valueToJson == true) {
            $date = new Zend_Date($result['value'], Tinebase_Record_Abstract::ISO8601LONG);
            $date->setTimezone($this->_timezone);
            $result['value'] = $date->get(Tinebase_Record_Abstract::ISO8601LONG);
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
        if ($this->_timezone !== 'UTC') {
            date_default_timezone_set($this->_timezone);
            $date = new Zend_Date($_value, Tinebase_Record_Abstract::ISO8601LONG);
            $date->setTimezone('UTC');
            $_value = $date->get(Tinebase_Record_Abstract::ISO8601LONG);
            date_default_timezone_set('UTC');
        }
        
        $this->_value = $_value;
    }
    
    /**
     * sets timezone of this filter
     *
     * @param string $_timezone
     * @throws Tinebase_Exception_NotImplemented
     */
    public function setTimezone($_timezone)
    {
        if (!empty($this->_value) {
            throw new Tinebase_Exception_NotImplemented('Could not set timezone of existing filter!');
        }
        
        $this->_timezone = $_timezone;
    }

    /**
     * calculates the date filter values
     *
     * @param string $_operator
     * @param string $_value
     * @param string $_dateFormat
     * @return array|string date value
     */
    protected function _getDateValues($_operator, $_value, $_dateFormat = NULL)
    {        
        if ($_operator === 'within') {
            $value = parent::_getDateValues(
                $_operator, 
                $_value, 
                ($_dateFormat === NULL) ? Tinebase_Record_Abstract::ISO8601LONG : $_dateFormat
            );
        } else  {            
            $value = $_value;
        }
        
        return $value;
    }
}
