<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract csv import class
 * 
 * some documentation for the xml import definition:
 * 
 * <delimiter>TAB</delimiter>:           use tab as delimiter
 * <config> main tags
 * <container_id>34</container_id>:     container id for imported records (required)
 * <encoding>UTF-8</encoding>:          encoding of input file
 * <duplicates>1<duplicates>:           check for duplicates
 * <use_headline>0</use_headline>:      just remove the headline/first line but do not use it for mapping
 *
 * <mapping><field> special tags:
 * <append>glue</append>:               value is appended to destination field with 'glue' as glue
 * <replace>\n</replace>:               replace \r\n with \n
 * <fixed>fixed</fixed>:                the field has a fixed value ('fixed' in this example)
 * 
 *
 * @todo        add tests for notes
 * @todo        add more documentation
 * @package     Tinebase
 * @subpackage  Import
 */
abstract class Tinebase_Import_Csv_Abstract extends Tinebase_Import_Abstract
{
    /**
     * csv headline
     * 
     * @var array
     */
    protected $_headline = array();

    // this week
    protected $_monday       = NULL;
    protected $_tuesday      = NULL;
    protected $_wednesday    = NULL;
    protected $_thursday     = NULL;
    protected $_friday       = NULL;
    protected $_saturday     = NULL;
    protected $_sunday       = NULL;

    // last week
    protected $_lastMonday   = NULL;
    protected $_lastFriday   = NULL;
    protected $_lastSaturday = NULL;
    protected $_lastSunday   = NULL;

    // next week
    protected $_nextMonday     = NULL;
    protected $_nextWednesday  = NULL;
    protected $_nextFriday     = NULL;

    protected $_wednesday2week = NULL;
    protected $_friday2week    = NULL;

    // next year
    protected $_nextyear       = NULL;
    protected $_next2year       = NULL;
    
    /**
     * special delimiters
     * 
     * @var array
     */
    protected $_specialDelimiter = array(
        'TAB'   => "\t"
    );
    
    /**
     * constructs a new importer from given config
     * 
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct(array $_options = array())
    {
        $this->_options = array_merge($this->_options, array(
            'maxLineLength'               => 8000,
            'delimiter'                   => ',',
            'enclosure'                   => '"',
            'escape'                      => '\\',
            'encodingTo'                  => 'UTF-8',
            'mapping'                     => '',
            'headline'                    => 0,
            'use_headline'                => 1,
            'mapUndefinedFieldsEnable'    => 0,
            'mapUndefinedFieldsTo'        => 'description',
            'demoData'                    => false
        ));

        $this->_days();
        
        parent::__construct($_options);

        if (empty($this->_options['model'])) {
            throw new Tinebase_Exception_InvalidArgument(get_class($this) . ' needs model in config.');
        }
        
        $this->_setController();
    }

    /**
     *
     * @param Tinebase_DateTime $now
     */
    protected function _days(Tinebase_DateTime $now = NULL)
    {
        // find out where we are
        if (! $now) {
            $now = new Tinebase_DateTime();
        }
        $weekday = $now->format('w');

        $subdaysLastMonday = 6 + $weekday;    // Monday last Week
        $subdaysLastFriday = 2 + $weekday;    // Friday last Week

        // this week
        $this->_monday = new Tinebase_DateTime();
        $this->_monday->sub(date_interval_create_from_date_string(($weekday - 1) . ' days'));
        $this->_tuesday = new Tinebase_DateTime();
        $this->_tuesday->sub(date_interval_create_from_date_string(($weekday - 2) . ' days'));
        $this->_wednesday = new Tinebase_DateTime();
        $this->_wednesday->sub(date_interval_create_from_date_string(($weekday - 3) . ' days'));
        $this->_thursday = new Tinebase_DateTime();
        $this->_thursday->sub(date_interval_create_from_date_string(($weekday - 4) . ' days'));
        $this->_friday = new Tinebase_DateTime();
        $this->_friday->sub(date_interval_create_from_date_string(($weekday - 5) . ' days'));
        $this->_saturday = clone $this->_friday;
        $this->_saturday->add(date_interval_create_from_date_string('1 day'));
        $this->_sunday = clone $this->_friday;
        $this->_sunday->add(date_interval_create_from_date_string('2 days'));

        // last week
        $this->_lastMonday = clone $this->_monday;
        $this->_lastMonday->subWeek(1);
        $this->_lastWednesday = clone $this->_wednesday;
        $this->_lastWednesday->subWeek(1);
        $this->_lastFriday = clone $this->_friday;
        $this->_lastFriday->subWeek(1);
        $this->_lastThursday = clone $this->_thursday;
        $this->_lastThursday->subWeek(1);
        $this->_lastSaturday = clone $this->_saturday;
        $this->_lastSaturday->subWeek(1);
        $this->_lastSunday = clone $this->_sunday;
        $this->_lastSunday->subWeek(1);

        $this->_nextMonday = clone $this->_monday;
        $this->_nextMonday->addWeek(1);
        $this->_nextTuesday = clone $this->_tuesday;
        $this->_nextTuesday->addWeek(1);
        $this->_nextWednesday = clone $this->_wednesday;
        $this->_nextWednesday->addWeek(1);
        $this->_nextThursday = clone $this->_thursday;
        $this->_nextThursday->addWeek(1);
        $this->_nextFriday = clone $this->_friday;
        $this->_nextFriday->addWeek(1);

        $this->_wednesday2week = clone $this->_nextWednesday;
        $this->_wednesday2week->addWeek(1);
        $this->_friday2week = clone $this->_nextFriday;
        $this->_friday2week->addWeek(1);

        $this->_nextyear = new Tinebase_DateTime();
        $this->_nextyear->addYear(1);
        $this->_next2year = new Tinebase_DateTime();
        $this->_next2year->addYear(2);


    }

    protected function _getDay($data,$dates)
    {
        foreach ($dates as $date) {
            if($date != null && $data[$date] != 'today') {
                $data[$date] = $this->{'_' . $data[$date]};
            }else
            {
                $data[$date] = new Tinebase_DateTime();
            }
        }
        return $data;
    }

    /**
     * get raw data of a single record
     * 
     * @param  resource $_resource
     * @return array|null
     */
    protected function _getRawData(&$_resource)
    {
        $delimiter = ((isset($this->_specialDelimiter[$this->_options['delimiter']])
            || array_key_exists($this->_options['delimiter'], $this->_specialDelimiter))
        )
            ? $this->_specialDelimiter[$this->_options['delimiter']]
            : $this->_options['delimiter'];
        $lineData = fgetcsv(
            $_resource,
            $this->_options['maxLineLength'],
            $delimiter,
            $this->_options['enclosure'],
            $this->_options['escape']
        );
        
        if (is_array($lineData) && count($lineData) == 1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Only got 1 field in line. Wrong delimiter?');
            return null;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Raw data: ' . print_r($lineData, true));
        
        return $lineData;
    }
    
    /**
     * do something before the import
     * 
     * @param resource $_resource
     */
    protected function _beforeImport($_resource = NULL)
    {
        // get headline
        if (isset($this->_options['headline']) && $this->_options['headline']) {
            $firstLine = $this->_getRawData($_resource);
            $this->_headline = $firstLine ? $firstLine : array();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Got headline: ' . implode(', ', $this->_headline));
            if (! $this->_options['use_headline']) {
                // just read headline but do not use it
                $this->_headline = array();
            } else {
                array_walk($this->_headline, function(&$value) {
                    $value = trim($value);
                });
            }
        }
    }
    
    /**
     * do the mapping
     *
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _doMapping($_data)
    {
        $data = array();
        $_data_indexed = array();

        if (! $_data) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Got empty raw data - skipping.');
            return $data;
        }
        
        if (! empty($this->_headline)) {
            if (sizeof($this->_headline) != sizeof($_data)) {
                $arrayWithEmptyValues = array_fill(sizeof($_data), sizeof($this->_headline)-sizeof($_data), '');
                if (is_array($arrayWithEmptyValues)) {
                    $_data = array_merge($_data, $arrayWithEmptyValues);
                }
            }
            $_data_indexed = array_combine($this->_headline, $_data);
        }

        if (! isset($this->_options['mapping']['field']) || ! is_array($this->_options['mapping']['field'])) {
            throw new Tinebase_Exception_UnexpectedValue('No field mapping defined');
        }

        $this->_mapValuesToDestination($_data_indexed, $_data, $data);

        if ($this->_options['mapUndefinedFieldsEnable'] == 1) {
            $undefinedFieldsText = $this->_createInfoTextForUnmappedFields($_data_indexed);
            if (! $undefinedFieldsText === false) {
                if ((isset($data[$this->_options['mapUndefinedFieldsTo']]) || array_key_exists($this->_options['mapUndefinedFieldsTo'], $data))) {
                    $data[$this->_options['mapUndefinedFieldsTo']] .= $this->_createInfoTextForUnmappedFields($_data_indexed);
                } else {
                    $data[$this->_options['mapUndefinedFieldsTo']] = $this->_createInfoTextForUnmappedFields($_data_indexed);
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Mapped data: ' . print_r($data, true));
        
        return $data;
    }

    /**
     * map values to destination fields
     *
     * @param $_data_indexed
     * @param $_data
     * @param $data
     */
    protected function _mapValuesToDestination($_data_indexed, $_data, &$data)
    {
        foreach ($this->_options['mapping']['field'] as $index => $field) {
            if (empty($_data_indexed) && isset($_data[$index])) {
                $value = $_data[$index];
            } else if (isset($field['source']) && isset($_data_indexed[$field['source']])) {
                $value = $_data_indexed[$field['source']];
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' No value found for field ' . (isset($field['source']) ? $field['source'] : print_r($field, true)));
                continue;
            }

            if ((! isset($field['destination']) || empty($field['destination'])) && ! isset($field['destinations'])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' No destination in definition for field ' . $field['source']);
                continue;
            }

            if (isset($field['destinations']) && isset($field['destinations']['destination'])) {
                $destinations = $field['destinations']['destination'];
                $delimiter = isset($field['$separator']) && ! empty($field['$separator']) ? $field['$separator'] : ' ';
                $values = array_map('trim', explode($delimiter, $value, count($destinations)));
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' values: ' . print_r($values, true));
                $i = 0;
                foreach ($destinations as $destination) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' destination ' . $destination);
                    if (isset($values[$i])) {
                        $data[$destination] = trim($values[$i]);
                    }
                    $i++;
                }
            } else {
                $data[$field['destination']] = $value;
            }
        }
    }
    
    /**
     * Generates a text with every undefined data from import 
     * 
     * @param array $_data_indexed
     * @return string
     */
    protected function _createInfoTextForUnmappedFields ($_data_indexed)
    {
        $return = null;
        
        $translation = Tinebase_Translation::getTranslation('Tinebase');
        
        $validKeys = array();
        foreach ($this->_options['mapping']['field'] as $keys) {
            $validKeys[$keys['source']] = null;
        }
        // This is an array containing every not mapped field as key with his value.
        $notImportedFields = array_diff_key($_data_indexed, $validKeys);
        
        if (count($notImportedFields) >= 1) {
            $description = sprintf($translation->_("The following fields weren't imported: %s"), "\n");
            $valueIfEmpty = $translation->_("N/A");
            
            foreach ($notImportedFields as $nKey => $nVal) {
                if (trim($nKey) == "") $nKey = $valueIfEmpty;
                if (trim($nVal) == "") $nVal = $valueIfEmpty;
                
                $description .= $nKey . " : " . $nVal . " \n";
            }
            $return = $description;
        } else {
            $return = false;
        }
        return $return;
    }
}
