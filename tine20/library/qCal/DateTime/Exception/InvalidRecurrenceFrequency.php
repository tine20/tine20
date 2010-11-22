<?php
/**
 * qCal_DateTime_Exception_InvalidRecurrenceFrequency
 * 
 * Invalid Recurrence Frequency Exception
 * This exception is thrown when qCal_DateTime_Recur::factory() is called with
 * a recurrence frequency that isn't supported by the library. The library supports
 * the following frequencies: "yearly", "monthly", "weekly", "daily", "hourly",
 * "minutely", "secondly"
 * 
 * @package qCal
 * @subpackage qCal_DateTime
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
class qCal_DateTime_Exception_InvalidRecurrenceFrequency extends qCal_DateTime_Exception {}