<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Month filter Class
 * 
 * a month is a string with 7 digits with the format YYYY-MM (date format 'Y-m')
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Month extends Tinebase_Model_Filter_Date
{
    protected $_operators = array('within', 'before', 'after', 'equals');

    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    function appendFilterSql($_select, $_backend)
    {
        $months = array();
        $db = $_backend->getAdapter();
        
        $date = new Tinebase_DateTime();
        $format = 'Y-m';
        
        $like = FALSE;
        
        if ($this->_operator == 'within') {
            switch ($this->_value) {
                case 'monthThis':
                    $months = array($date->format($format));
                    break;
                case 'monthLast':
                    $months = array($date->subMonth(1)->format($format));
                    break;
                case 'beforeLastMonth':
                    $months = array($date->subMonth(2)->format($format));
                    break;
                case 'quarterThis':
                    $month = ceil(intval($date->format('m')) / 3) * 3;
                    $date->setDate($date->format('Y'), $month, 15);
                    $months = array($date->format($format),$date->subMonth(1)->format($format),$date->subMonth(1)->format($format));
                    break;
                case 'quarterLast':
                    $date->subMonth(3);
                    $month = ceil(intval($date->format('m')) / 3) * 3;
                    $date->setDate($date->format('Y'), $month, 15);
                    $months = array($date->format($format),$date->subMonth(1)->format($format),$date->subMonth(1)->format($format));
                    break;
                case 'beforeLastQuarter':
                    $date->subMonth(6);
                    $month = ceil(intval($date->format('m')) / 3) * 3;
                    $date->setDate($date->format('Y'), $month, 15);
                    $months = array($date->format($format),$date->subMonth(1)->format($format),$date->subMonth(1)->format($format));
                    break;
                case 'yearThis':
                    $like = $date->format('Y') . '-%';
                    break;
                case 'yearLast':
                    $date->subYear(1);
                    $like = $date->format('Y') . '-%';
                    break;
                default: throw new Tinebase_Exception_InvalidArgument('The value for the within operator is not supported: ' . $this->_value);
            }
            
            if ($like) {
                $_select->where($db->quoteInto($this->_getQuotedFieldName($_backend) . " LIKE (?)", $like));
            } else {
                $_select->where($db->quoteInto($this->_getQuotedFieldName($_backend) . " IN (?)", $months));
            }
        } elseif (($this->_operator == 'before') || ($this->_operator == 'after')) {
            
            $date = new Tinebase_DateTime($this->_value);
            $dateString = $date->format('Y-m');
            
            if ($this->_operator == 'before') {
                $_select->where($db->quoteInto($this->_getQuotedFieldName($_backend) . " < (?)", $dateString));
            } else {
                $_select->where($db->quoteInto($this->_getQuotedFieldName($_backend) . " >= (?)", $dateString));
            }
        } elseif ($this->_operator == 'equals') {
            $_select->where($db->quoteInto($this->_getQuotedFieldName($_backend) . " = (?)", $this->_value));
        } else {
            throw new Tinebase_Exception_InvalidArgument('The operator ' . $this->_operator . ' is not supported for this filter!');
        }
    }
}