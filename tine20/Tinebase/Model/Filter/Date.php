<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        add year to 'inweek' filter?
 */

/**
 * Tinebase_Model_Filter_Date
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters date in one property
 */
class Tinebase_Model_Filter_Date extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'within',
        2 => 'before',
        3 => 'after',
        4 => 'isnull',
        5 => 'notnull',
        6 => 'inweek'
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' LIKE ?'),
        'within'     => array('sqlop' => array(' >= ? ', ' <= ?')),
        'before'     => array('sqlop' => ' < ?'),
        'after'      => array('sqlop' => ' > ?'),
        'isnull'     => array('sqlop' => ' IS NULL'),
        'notnull'    => array('sqlop' => ' IS NOT NULL'),
        'inweek'     => array('sqlop' => array(' >= ? ', ' <= ?')),
    );
    
    /**
     * date format string
     *
     * @var string
     */
    protected $_dateFormat = 'yyyy-MM-dd';
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
     public function appendFilterSql($_select, $_backend)
    {
        // prepare value
        $value = (array)$this->_getDateValues($this->_operator, $this->_value);
         
        // quote field identifier
        $field = $this->_getQuotedFieldName($_backend);
         
        // append query to select object
        foreach ((array)$this->_opSqlMap[$this->_operator]['sqlop'] as $num => $operator) {
            if (get_parent_class($this) === 'Tinebase_Model_Filter_Date') {
                $_select->where($field . $operator, $value[$num]);
            } else {
                $_select->where("DATE({$field})" . $operator, $value[$num]);
            }
        }
    }
    
    /**
     * calculates the date filter values
     *
     * @param string $_operator
     * @param string $_value
     * @return array|string date value
     * 
     * @todo fix problem with day of week in 'this week' / 'in week #' filter (sunday is first day of the week in english locales) 
     * --> get that info from locale
     */
    protected function _getDateValues($_operator, $_value)
    {
        if ($_operator === 'within') {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting "within" filter: ' . $_value);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Timezone: ' . date_default_timezone_get());
            
            $date = new Zend_Date();
            
            // special values like this week, ...
            switch($_value) {
                /******* week *********/
                case 'weekNext':
                    $date->add(21, Zend_Date::DAY);
                case 'weekBeforeLast':    
                    $date->sub(7, Zend_Date::DAY);
                case 'weekLast':    
                    $date->sub(7, Zend_Date::DAY);
                case 'weekThis':
                    $value = $this->_getFirstAndLastDayOfWeek($date);
                    break;
                /******* month *********/
                case 'monthNext':
                    $date->add(2, Zend_Date::MONTH);
                case 'monthLast':
                    $date->sub(1, Zend_Date::MONTH);
                case 'monthThis':
                    $dayOfMonth = $date->get(Zend_Date::DAY_SHORT);
                    $monthDays = $date->get(Zend_Date::MONTH_DAYS);
                    
                    $first = $date->toString('yyyy-MM') . '-01';
                    $date->add($monthDays-$dayOfMonth, Zend_Date::DAY);
                    $last = $date->toString($this->_dateFormat);
    
                    $value = array(
                        $first, 
                        $last,
                    );
                    break;
                /******* year *********/
                case 'yearNext':
                    $date->add(2, Zend_Date::YEAR);
                case 'yearLast':
                    $date->sub(1, Zend_Date::YEAR);
                case 'yearThis':
                    $value = array(
                        $date->toString('yyyy') . '-01-01', 
                        $date->toString('yyyy') . '-12-31',
                    );                
                    break;
                /******* quarter *********/
                case 'quarterNext':
                    $date->add(6, Zend_Date::MONTH);
                case 'quarterLast':
                    $date->sub(3, Zend_Date::MONTH);
                case 'quarterThis':
                    $month = $date->get(Zend_Date::MONTH);
                    if ($month < 4) {
                        $first = $date->toString('yyyy' . '-01-01');
                        $last = $date->toString('yyyy' . '-03-31');
                    } elseif ($month < 7) {
                        $first = $date->toString('yyyy' . '-04-01');
                        $last = $date->toString('yyyy' . '-06-30');
                    } elseif ($month < 10) {
                        $first = $date->toString('yyyy' . '-07-01');
                        $last = $date->toString('yyyy' . '-09-30');
                    } else {
                        $first = $date->toString('yyyy' . '-10-01');
                        $last = $date->toString('yyyy' . '-12-31');
                    }
                    $value = array(
                        $first, 
                        $last
                    );                
                    break;
                /******* day *********/
                case 'dayNext':
                    $date->add(2, Zend_Date::DAY);
                case 'dayLast':
                    $date->sub(1, Zend_Date::DAY);
                case 'dayThis':
                    $value = array(
                        $date->toString($this->_dateFormat), 
                        $date->toString($this->_dateFormat), 
                    );
                    break;
                /******* error *********/
                default:
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' value unknown: ' . $_value);
                    $value = '';
            }        
        } elseif ($_operator === 'inweek') {
            $date = new Zend_Date();
            
            if ($_value > 52) {
                $_value = 52;
            } elseif ($_value < 1) {
                $_value = $date->get(Zend_Date::WEEK);
            }
            $value = $this->_getFirstAndLastDayOfWeek($date, $_value);
            
        } else  {
            $value = substr($_value, 0, 10);
        }
        return $value;
    }
    
    /**
     * get string representation of first and last days of the week defined by date/week number
     * 
     * @param Zend_Date $_date
     * @param integer $_weekNumber optional
     * @return array
     */
    protected function _getFirstAndLastDayOfWeek(Zend_Date $_date, $_weekNumber = NULL)
    {
        $firstDayOfWeek = $this->_getFirstDayOfWeek();
        
        if ($_weekNumber !== NULL) {
            $_date->setWeek($_weekNumber);
        } 
        
        $dayOfWeek = $_date->get(Zend_Date::WEEKDAY_DIGIT);
        // in some locales sunday is last day of the week -> we need to init dayOfWeek with 7
        $dayOfWeek = ($firstDayOfWeek == 1 && $dayOfWeek == 0) ? 7 : $dayOfWeek;
        $_date->sub($dayOfWeek - $firstDayOfWeek, Zend_Date::DAY);
        
        $firstDay = $_date->toString($this->_dateFormat);
        $_date->add(6, Zend_Date::DAY);
        $lastDay = $_date->toString($this->_dateFormat);
            
        $result = array(
            $firstDay,
            $lastDay, 
        );
        
        return $result;
    }
    
    /**
     * returns number of the first day of the week (0 = sunday or 1 = monday) depending on locale
     * 
     * @return integer
     */
    protected function _getFirstDayOfWeek()
    {
        $locale = Tinebase_Core::getLocale();
        $weekInfo = Zend_Locale_Data::getList($locale, 'week');
        
        $result = ($weekInfo['firstDay'] == 'sun') ? 0 : 1;
        
        return $result;
    }
}
