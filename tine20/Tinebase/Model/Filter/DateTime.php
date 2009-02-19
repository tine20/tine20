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
     * 
     * @todo    finish implementation
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($_valueToJson == true) {
            // @todo use _convertISO8601ToZendDate and then setTimezone(Tinebase_Core::get('userTimeZone')
        }
        return $result;
    }
    
    /**
     * calculates the date filter values
     *
     * @param string $_operator
     * @param string $_value
     * @param string $_dateFormat
     * @return array|string date value
     */
    protected function _getDateValues($_operator, $_value, $_dateFormat = 'yyyy-MM-dd HH:mm:ss')
    {        
        if ($_operator === 'within') {
            $value = parent::_getDateValues($_operator, $_value, $_dateFormat);
        } else  {            
            $value = $_value;
        }
        
        return $value;
    }
}
