<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Scheduler
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Automatic scheduler task rule.
 *
 * @category   Zend
 * @package    Zend_Scheduler
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Scheduler_Task_Rule
{
    /** @var Zend_Date Request time */
    protected $_time = null;

    /** @var string Type */
    protected $_type = '';

    /** @var string Rule */
    protected $_rule = '';

    /** @var array Time numeric values */
    protected $_times = array(
        'months'   => Zend_Date::MONTH_SHORT,
        'days'     => Zend_Date::DAY_SHORT,
        'weekdays' => Zend_Date::WEEKDAY_DIGIT,
        'hours'    => Zend_Date::HOUR_SHORT,
        'minutes'  => Zend_Date::MINUTE_SHORT
    );

    /** @var array Time ranges */
    protected $_ranges = array(
        'months'   => array('minimum' => 1, 'maximum' => 12),
        'days'     => array('minimum' => 1, 'maximum' => 31),
        'weekdays' => array('minimum' => 0, 'maximum' => 6),
        'hours'    => array('minimum' => 0, 'maximum' => 23),
        'minutes'  => array('minimum' => 0, 'maximum' => 59)
    );

    /** @var array Months of the year */
    protected $_months = array(
        'Jan' => 1, 'Feb' => 2,  'Mar' => 3,  'Apr' => 4,
        'May' => 5, 'Jun' => 6,  'Jul' => 7,  'Aug' => 8,
        'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
    );

    /** @var array Days of the month */
    protected $_days = array(
        'last' => ''
    );

    /** @var array Days of the week */
    protected $_weekdays = array(
        'Sun' => 0, 'Mon' => 1, 'Tue' => 2, 'Wed' => 3, 
        'Thu' => 4, 'Fri' => 5, 'Sat' => 6
    );

    /**
     * Constructor.
     *
     * @param string $type
     * @param string $rule
     */
    public function __construct($type, $rule)
    {
        $type = trim(strtolower($type));
        $rule = trim($rule);

        if (empty($type)) {
            throw new Zend_Scheduler_Exception('Type string expected');
        }
        if (!isset($this->_times[$type])) {
            throw new Zend_Scheduler_Exception("Type '{$type}' was not a valid type");
        }
        if (empty($type)) {
            throw new Zend_Scheduler_Exception('Rule string expected');
        }

        $this->_type = $type;
        $this->_rule = $rule;
    }

    /**
     * Set the time (by default, the request time).  For testing purposes a
     * different time can be passed in.
     *
     * @param Zend_Date $time
     * @return Zend_Scheduler_Task This instance
     */
    public function setTime($time)
    {
        if (!$time instanceof Zend_Date) {
            $time = new Zend_Date($time);
        }
        $this->_time = $time;

        if ($this->_type == 'days') { // Set number of days in the month
            $this->_days['last'] = $time->get(Zend_Date::MONTH_DAYS);
        }

        return $this;
    }

    /**
     * Checks if rule matches the supplied timestamp.
     *
     * @param  string $time
     * @return bool
     */
    public function matches($time = '')
    {
        if ($time) {
            $this->setTime($time);
        }
        if (empty($this->_time)) {
            throw new Zend_Scheduler_Exception('You must supply a time');
        }
        return in_array($this->_getTime(), $this->_parse());
    }

    /**
     * Executes following unserialization.
     */
    public function __wakeup()
    {
        $this->_time = null;
    }

    /**
     * Gets int value for specified type (e.g., 'Dec' equals 12)
     *
     * @return int
     */
    protected function _getTime()
    {
        return $this->_time->get($this->_times[$this->_type]);
    }

    /**
     * Parses the rule.
     *
     * @param  string $rule
     * @return array Expanded values
     */
    protected function _parse()
    {
        $rule = $this->_rule;

        if ($this->_type == 'months' or $this->_type == 'weekdays') {
            if (strpos($rule, '/') !== false) {
                throw new Zend_Scheduler_Exception(ucfirst($this->_type) 
                    . ' can only be specified individually or as a range');
            }
        }

        if (preg_match('/[A-Za-z]/', $this->_rule)) {
            $patterns     = array();
            $replacements = array();
            $type         = '_' . $this->_type;
            foreach ($this->$type as $name => $value) {
                $patterns[]     = '/' . $name . '[^,\-\/]*/i';
                $replacements[] = $value;
            }
            $rule = preg_replace($patterns, $replacements, $this->_rule);
        }

        $values = preg_split('/\s*,\s*/', $rule, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($values as $i => $value) {
            if (strpos($value, '-') !== false and strpos($value, '/') !== false) {
                throw new Zend_Scheduler_Exception('Invalid ' . $this->_type . ' value');
            }

            if (strcmp($value, (int) $value) === 0) {
                continue; // Value is an integer; go to next value
            }
            if (preg_match('/\A\d+\s*\-\s*\d+\Z/', $value)) {
                // Parse range (e.g., 5-10)
                list($valueFrom, $valueTo) = preg_split('/\s*\-\s*/', $value);
                if (!$this->_inRange($valueFrom) or !$this->_inRange($valueTo)) {
                    throw new Zend_Scheduler_Exception('Invalid ' . $this->_type . ' range');
                }

                $j = $valueFrom;
                if ($valueFrom > $valueTo) {
                    for (; $j < $this->_getMaximum() + 1; $j++) {
                        $values[] = $j;
                    }
                    $j = $this->_getMinimum();
                }
                for (; $j < $valueTo + 1; $j++) {
                    $values[] = $j;
                }
                unset($values[$i]);
            } else if (preg_match('/\A\d+\s*\/\s*\d+\Z/', $value)) {
                // Parse incremental step (e.g., 5/10)
                list($valueFrom, $valueStep) = preg_split('/\s*\/\s*/', $value);
                $j = $valueFrom;
                while ($this->_inRange($j)) {
                    $values[] = $j;
                    $j += $valueStep;
                }
                unset($values[$i]);
            } else {
                throw new Zend_Scheduler_Exception('Invalid ' . $this->_type . ' value');
            }
        }

        $values = array_unique($values);
        sort($values);

        return $values;
    }

    /**
     * Gets minimally-acceptable value for type.
     *
     * @return int Minimum
     */
    protected function _getMinimum()
    {
        return $this->_ranges[$this->_type]['minimum'];
    }

    /**
     * Gets maximally-acceptable value for type.
     *
     * @return int Maximum
     */
    protected function _getMaximum()
    {
        return $this->_ranges[$this->_type]['maximum'];
    }

    /**
     * Checks if rule value is within the acceptable range.
     *
     * @param  int $value
     * @return bool
     */
    protected function _inRange($value)
    {
        return ($value >= $this->_getMinimum() and $value <= $this->_getMaximum());
    }
    
    /**
     * Return Rule
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->_rule;
    }
}
