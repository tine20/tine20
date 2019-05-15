<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * abstract class for VCALENDAR/VTODO/VCARD/... conversion
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
abstract class Tinebase_Convert_VCalendar_Abstract
{
    /**
     * use servers modlogProperties instead of given DTSTAMP & SEQUENCE
     * use this if the concurrency checks are done differntly like in CalDAV
     * where the etag is checked
     */
    const OPTION_USE_SERVER_MODLOG = 'useServerModlog';
    
    protected $_supportedFields = array();
    
    protected $_version;
    
    protected $_modelName = null;
    
    /**
     * @param string  $version  the version of the client
     * @throws Tinebase_Exception
     */
    public function __construct($version = null)
    {
        if (! $this->_modelName) {
            throw new Tinebase_Exception('modelName is required');
        }
        $this->_version = $version;
    }

    /**
     * returns VObject of input data
     * 
     * @param   mixed  $blob
     * @return  \Sabre\VObject\Component\VCalendar
     */
    public static function getVObject($blob)
    {
        if ($blob instanceof \Sabre\VObject\Component\VCalendar) {
            return $blob;
        }
        
        if (is_resource($blob)) {
            $blob = stream_get_contents($blob);
        }
        
        $blob = Tinebase_Core::filterInputForDatabase($blob);

        try {
            $vcalendar = self::readVCalBlob($blob);
        } catch (Sabre\VObject\ParseException $svpe) {
            // try again with utf8_encoded blob
            $utf8_blob = Tinebase_Helper::mbConvertTo($blob);
            // alse replace some linebreaks and \x00's
            $search = array("\r\n", "\x00");
            $replace = array("\n", '');
            $utf8_blob = str_replace($search, $replace, $utf8_blob);
            $vcalendar = self::readVCalBlob($utf8_blob);
        }
        
        return $vcalendar;
    }
    
    /**
     * reads vcal blob and tries to repair some parsing problems that Sabre has
     *
     * @param string $blob
     * @param integer $failcount
     * @param integer $spacecount
     * @param integer $lastBrokenLineNumber
     * @param array $lastLines
     * @throws Sabre\VObject\ParseException
     * @return Sabre\VObject\Component\VCalendar
     *
     * @see 0006110: handle iMIP messages from outlook
     *
     * @todo maybe we can remove this when #7438 is resolved
     */
    public static function readVCalBlob($blob, $failcount = 0, $spacecount = 0, $lastBrokenLineNumber = 0, $lastLines = array())
    {
        // convert to utf-8
        $blob = Tinebase_Helper::mbConvertTo($blob);
    
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                ' ' . $blob);
    
        try {
            $vcalendar = \Sabre\VObject\Reader::read($blob);
        } catch (Sabre\VObject\ParseException $svpe) {
            // NOTE: we try to repair\Sabre\VObject\Reader as it fails to detect followup lines that do not begin with a space or tab
            if ($failcount < 10 && preg_match(
                    '/Invalid VObject, line ([0-9]+) did not follow the icalendar\/vcard format/', $svpe->getMessage(), $matches
            )) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        ' ' . $svpe->getMessage() .
                        ' lastBrokenLineNumber: ' . $lastBrokenLineNumber);
    
                $brokenLineNumber = $matches[1] - 1 + $spacecount;
    
                if ($lastBrokenLineNumber === $brokenLineNumber) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                            ' Try again: concat this line to previous line.');
                    $lines = $lastLines;
                    $brokenLineNumber--;
                    // increase spacecount because one line got removed
                    $spacecount++;
                } else {
                    $lines = preg_split('/[\r\n]*\n/', $blob);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                            ' Concat next line to this one.');
                    $lastLines = $lines; // for retry
                }
                $lines[$brokenLineNumber] .= $lines[$brokenLineNumber + 1];
                unset($lines[$brokenLineNumber + 1]);
    
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                        ' failcount: ' . $failcount .
                        ' brokenLineNumber: ' . $brokenLineNumber .
                        ' spacecount: ' . $spacecount);
    
                $vcalendar = self::readVCalBlob(implode("\n", $lines), $failcount + 1, $spacecount, $brokenLineNumber, $lastLines);
            } else {
                throw $svpe;
            }
        }
    
        return $vcalendar;
    }
    
    /**
     * to be overwriten in extended classes to modify/cleanup $_vcalendar
     *
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     */
    protected function _afterFromTine20Model(\Sabre\VObject\Component\VCalendar $vcalendar)
    {
    }
    
    /**
     * parse valarm properties
     * 
     * @param Tinebase_Record_Abstract $record
     * @param Traversable $valarms
     * @param \Sabre\VObject\Component $vcomponent
     */
    protected function _parseAlarm(Tinebase_Record_Abstract $record, $valarms, \Sabre\VObject\Component $vcomponent)
    {
        foreach ($valarms as $valarm) {
            
            if ($valarm->ACTION == 'NONE') {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' We can\'t cope with action NONE: iCal 6.0 sends default alarms in the year 1976 with action NONE. Skipping alarm.');
                continue;
            }
            
            if (! is_object($valarm->TRIGGER)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Alarm has no TRIGGER value. Skipping it.');
                continue;
            }
            
            # TRIGGER:-PT15M
            if (is_string($valarm->TRIGGER->getValue()) && $valarm->TRIGGER instanceof Sabre\VObject\Property\ICalendar\Duration) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Adding DURATION trigger value for ' . $valarm->TRIGGER->getValue());
                $valarm->TRIGGER->add('VALUE', 'DURATION');
            }
            
            $trigger = is_object($valarm->TRIGGER['VALUE']) ? $valarm->TRIGGER['VALUE'] : (is_object($valarm->TRIGGER['RELATED']) ? $valarm->TRIGGER['RELATED'] : NULL);
            
            if ($trigger === NULL) {
                // added Trigger/Related for eM Client alarms
                // 2014-01-03 - Bullshit, why don't we have testdata for emclient alarms?
                        //              this alarm handling should be refactored, the logic is scrambled
                // @see 0006110: handle iMIP messages from outlook
                // @todo fix 0007446: handle broken alarm in outlook invitation message
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Alarm has no TRIGGER value. Skipping it.');
                continue;
            }
            
            switch (strtoupper($trigger->getValue())) {
                # TRIGGER;VALUE=DATE-TIME:20111031T130000Z
                case 'DATE-TIME':
                    $alarmTime = new Tinebase_DateTime($valarm->TRIGGER->getValue());
                    $alarmTime->setTimezone('UTC');
                    
                    $alarm = new Tinebase_Model_Alarm(array(
                        'alarm_time'        => $alarmTime,
                        'minutes_before'    => 'custom',
                        'model'             => $this->_modelName,
                    ));
                    
                    break;
                
                # TRIGGER;VALUE=DURATION:-PT1H15M
                case 'DURATION':
                default:
                    $durationBaseTime = isset($vcomponent->DTSTART) ? $vcomponent->DTSTART : $vcomponent->DUE;
                    $alarmTime = $this->_convertToTinebaseDateTime($durationBaseTime);
                    $alarmTime->setTimezone('UTC');
                    
                    preg_match('/(?P<invert>[+-]?)(?P<spec>P.*)/', $valarm->TRIGGER->getValue(), $matches);
                    $duration = new DateInterval($matches['spec']);
                    $duration->invert = !!($matches['invert'] === '-');
                    
                    $alarm = new Tinebase_Model_Alarm(array(
                        'alarm_time'        => $alarmTime->add($duration),
                        'minutes_before'    => ($duration->format('%d') * 60 * 24) + ($duration->format('%h') * 60) + ($duration->format('%i')),
                        'model'             => $this->_modelName,
                    ));
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' Adding DURATION alarm ' . print_r($alarm->toArray(), true));
            }
            
            if ($valarm->ACKNOWLEDGED) {
                $dtack = $valarm->ACKNOWLEDGED->getDateTime();
                Calendar_Controller_Alarm::setAcknowledgeTime($alarm, $dtack);
            }
            
            $record->alarms->addRecord($alarm);
        }
    }
    
    /**
     * get datetime from sabredav datetime property (user TZ is fallback)
     * 
     * @param  Sabre\VObject\Property  $dateTimeProperty
     * @param  boolean                 $_useUserTZ
     * @return Tinebase_DateTime
     * 
     * @todo try to guess some common timezones
     */
    protected function _convertToTinebaseDateTime(\Sabre\VObject\Property $dateTimeProperty, $_useUserTZ = FALSE)
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set((string) Tinebase_Core::getUserTimezone());
        
        if ($dateTimeProperty instanceof Sabre\VObject\Property\ICalendar\DateTime) {
            $dateTime = $dateTimeProperty->getDateTime();

            $isFloatingTime = !$dateTimeProperty['TZID'] && !preg_match('/Z$/i', $dateTimeProperty->getValue());
            $isDate = (isset($dateTimeProperty['VALUE']) && strtoupper($dateTimeProperty['VALUE']) == 'DATE');

            $tz = ($_useUserTZ || $isFloatingTime || $isDate) ?
                (string) Tinebase_Core::getUserTimezone() : 
                $dateTime->getTimezone();
            
            $result = new Tinebase_DateTime($dateTime->format(Tinebase_Record_Abstract::ISO8601LONG), $tz);
        } else {
            $result = new Tinebase_DateTime($dateTimeProperty->getValue());
        }
        
        date_default_timezone_set($defaultTimezone);
        
        return $result;
    }
}
