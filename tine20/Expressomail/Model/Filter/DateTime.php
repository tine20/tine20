<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 *
 */

/**
 * Expressomail_Model_Filter_DateTime
 *
 * @package     Expressomail
 * @subpackage  Filter
 *
 * filters date in one property
 */
class Expressomail_Model_Filter_DateTime extends Tinebase_Model_Filter_DateTime
{
     /**
     *
     * @return type
     */
    public function getFilterImap()
    {
        $format = "d-M-Y";


        // prepare value
        $value = (array) $this->_getDateValues($this->_operator, $this->_value);
        $timezone = Tinebase_Helper::array_value('timezone', $this->_options);
        $timezone = $timezone ? $timezone : Tinebase_Core::get('userTimeZone');
        foreach ($value as &$date)
        {
            $date = new Tinebase_DateTime($date); // should be in user timezone
            $date->setTimezone(new DateTimeZone($timezone));
        }

        switch ($this->_operator)
        {
            case 'within' :
            case 'inweek' :
                $value[1]->add(new DateInterval('P1D')); // before is not inclusive, so we have to add a day
                $return = "SINCE {$value[0]->format($format)} BEFORE {$value[1]->format($format)}";
                break;
            case 'before' :
                $return = "BEFORE {$value[0]->format($format)}";
                break;
            case 'after' :
                $return = "SINCE {$value[0]->format($format)}";
                break;
            case 'equals' :
                $return = "ON {$value[0]->format($format)}";
        }

        return $return;
    }

}
