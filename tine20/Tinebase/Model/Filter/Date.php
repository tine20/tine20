<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        add times (hh:mm:ss) if $this->_options['isDateTime']
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
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' LIKE ?'),
        'within'     => array('sqlop' => array(' >= ? ', ' <= ?')),
        'before'     => array('sqlop' => ' < ?'),
        'after'      => array('sqlop' => ' > ?')
    );
    
    /**
     * appeds sql to given select statement
     *
     * @param Zend_Db_Select $_select
     */
     public function appendFilterSql($_select)
     {
         // prepare value
         $value = (array)$this->_getDateValues($this->_operator, $this->_value);
         
         // quote field identifier
         // ZF 1.7+ $field = $_select->getAdapter()->quoteIdentifier($this->field);
         $field = $db = Tinebase_Core::getDb()->quoteIdentifier($this->_field);
         
         // append query to select object
         foreach ((array)$this->_opSqlMap[$this->_operator]['sqlop'] as $num => $operator) {
             $_select->where($field . $operator, $value[$num]);
         }
         
     }
     
    /**
     * calculates the date filter values
     *
     * @param string $_operator
     * @param string $_value
     * @param string $_dateFormat
     * @return array|string date value
     * 
     * @todo fix problem with day of week in 'this week' filter (sunday is first day of the week in english locales) 
     * --> get that info from locale
     */
    protected function _getDateValues($_operator, $_value, $_dateFormat = 'yyyy-MM-dd')
    {        
        if ($_operator === 'within') {
            $date = new Zend_Date();
            
            // special values like this week, ...
            switch($_value) {
                case 'weekNext':
                    $date->add(21, Zend_Date::DAY);
                case 'weekBeforeLast':    
                    $date->sub(7, Zend_Date::DAY);
                case 'weekLast':    
                    $date->sub(7, Zend_Date::DAY);
                case 'weekThis':
                    $dayOfWeek = $date->get(Zend_Date::WEEKDAY_DIGIT);
                    // in german locale sunday is last day of the week
                    $dayOfWeek = ($dayOfWeek == 0) ? 7 : $dayOfWeek;
                    $date->sub($dayOfWeek-1, Zend_Date::DAY);
                    $monday = $date->toString($_dateFormat);
                    $date->add(6, Zend_Date::DAY);
                    $sunday = $date->toString($_dateFormat);
                    
                    $value = array(
                        $monday, 
                        $sunday,
                    );
                    break;
                case 'monthNext':
                    $date->add(2, Zend_Date::MONTH);
                case 'monthLast':
                    $date->sub(1, Zend_Date::MONTH);
                case 'monthThis':
                    $dayOfMonth = $date->get(Zend_Date::DAY_SHORT);
                    $monthDays = $date->get(Zend_Date::MONTH_DAYS);
                    
                    $first = $date->toString('yyyy-MM');
                    $date->add($monthDays-$dayOfMonth, Zend_Date::DAY);
                    $last = $date->toString($_dateFormat);
    
                    $value = array(
                        $first, 
                        $last,
                    );
                    break;
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
                case 'dayNext':
                    $date->add(2, Zend_Date::DAY);
                case 'dayLast':
                    $date->sub(1, Zend_Date::DAY);
                case 'today':
                    $value = array(
                        $date->toString('yyyy-MM-dd'), 
                        $date->toString('yyyy-MM-dd'), 
                    );                
                    break;
                default:
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value unknown: ' . $_value);
                    $value = '';
            }        
        } else  {

            $value = substr($_value, 0, 10);
            
            if (isset($this->_options['isDateTime']) && $this->_options['isDateTime']) {
                switch ($_operator) {
                    case 'before':
                        $value .= ' 00:00:00';
                        break;
                    case 'after':
                        $value .= ' 23:59:59';
                        break;
                    case 'equals':
                        $value .= '%';                
                        break;
                }
            }            
        }
        
        return $value;
    }
}