<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        add generic mechanism for value pre/postfixes? (see accountLoginNamePrefix in Admin_User_Import)
 * @todo        add more conversions e.g. date/accounts
 * @todo        add tests for notes
 * @todo        add more documentation
 * @todo        make it possible to import custom fields
 */

/**
 * abstract csv import class
 * 
 * @package     Tinebase
 * @subpackage  Import
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
 */
abstract class Tinebase_Import_Csv_Abstract extends Tinebase_Import_Abstract
{
    /**
     * @var array
     */
    protected $_options = array(
        'maxLineLength'     => 8000,
        'delimiter'         => ',',
        'enclosure'         => '"',
        'escape'            => '\\',
        'encoding'          => 'UTF-8',
        'encodingTo'        => 'UTF-8',
        'dryrun'            => FALSE,
        'dryrunCount'       => 20,
        'dryrunLimit'       => 0,       
        'duplicateCount'    => 0,
        'createMethod'      => 'create',
        'model'             => '',
        'mapping'           => '',
        'duplicates'        => 0,
        'headline'          => 0,
        'use_headline'      => 1,
        'shared_tags'       => 'onlyexisting',
    );
    
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
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        if (empty($this->_options['model'])) {
            throw new Tinebase_Exception_InvalidArgument(get_class($this) . ' needs model in config.');
        }
        
        $this->_setController();
    }
    
    /**
     * import the data
     *
     * @param  resource $_resource (if $_filename is a stream)
     * @return array with Tinebase_Record_RecordSet the imported records (if dryrun) and totalcount 
     */
    public function import($_resource = NULL)
    {
        // get headline
        if (isset($this->_options['headline']) && $this->_options['headline']) {
            $headline = $this->_getRawData($_resource);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got headline: ' . implode(', ', $headline));
            if (! $this->_options['use_headline']) {
                // just read headline but do not use it
                $headline = array();
            }
        } else {
            $headline = array();
        }

        $result = array(
            'results'           => new Tinebase_Record_RecordSet($this->_options['model']),
            'totalcount'        => 0,
            'failcount'         => 0,
            'duplicatecount'    => 0,
        );

        while (
            ($recordData = $this->_getRawData($_resource)) !== FALSE && 
            (! $this->_options['dryrun'] 
                || ! ($this->_options['dryrunLimit'] && $result['totalcount'] >= $this->_options['dryrunCount'])
            )
        ) {
            if (is_array($recordData)) {
                try {
                    $mappedData = $this->_doMapping($recordData, $headline);
                    
                    if (! empty($mappedData)) {
                        $convertedData = $this->_doConversions($mappedData);

                        // merge additional values (like group id, container id ...)
                        $mergedData = array_merge($convertedData, $this->_addData($convertedData));
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Merged data: ' . print_r($mergedData, true));
                        
                        // import record into tine!
                        $importedRecord = $this->_importRecord($mergedData, $result);
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got empty record from mapping! Was: ' . print_r($recordData, TRUE));
                        $result['failcount']++;
                    }
                    
                } catch (Exception $e) {
                    // don't add incorrect record (name missing for example)
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                    $result['failcount']++;
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No array: ' . $recordData);
            }
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Import finished. (total: ' . $result['totalcount'] 
            . ' fail: ' . $result['failcount'] . ' duplicates: ' . $result['duplicatecount']. ')');
        
        return $result;
    }
    
    /**
     * get raw data of a single record
     * 
     * @param  resource $_resource
     * @return array
     */
    protected function _getRawData($_resource) 
    {
        $delimiter = (array_key_exists($this->_options['delimiter'], $this->_specialDelimiter)) ? $this->_specialDelimiter[$this->_options['delimiter']] : $this->_options['delimiter'];
        
        $lineData = fgetcsv(
            $_resource, 
            $this->_options['maxLineLength'], 
            $delimiter,
            $this->_options['enclosure'] 
            // escape param is only available in PHP >= 5.3.0
            // $this->_options['escape']
        );
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($lineData, TRUE));
        if (is_array($lineData) && count($lineData) == 1) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Only got 1 field in line. Wrong delimiter?');
        }
        
        return $lineData;
    }
    
    /**
     * do the mapping and replacements
     *
     * @param array $_data
     * @param array $_headline [optional]
     * @return array
     */
    protected function _doMapping($_data, $_headline = array())
    {
        $data = array();
        $_data_indexed = array();

        if (! empty($_headline) && sizeof($_headline) == sizeof($_data)) {
            $_data_indexed = array_combine($_headline, $_data);
        }
        
        foreach ($this->_options['mapping']['field'] as $index => $field) {
            if (empty($_data_indexed)) {
                // use import definition order
                
                if (! array_key_exists('destination', $field) || $field['destination'] == '' || !isset($_data[$index])) {
                    continue;
                }
            
                if (isset($field['replace'])) {
                    if ($field['replace'] === '\n') {
                        $_data[$index] = str_replace("\\n", "\r\n", $_data[$index]);
                    }
                }
            
                if (isset($field['separator'])) {
                    $data[$field['destination']] = explode($field['separator'], $_data[$index]);
                } else if (isset($field['fixed'])) {
                    $data[$field['destination']] = $field['fixed'];
                } else {
                    $data[$field['destination']] = $_data[$index];
                }
            } else {
                // use order defined by headline
                
                if ($field['destination'] == '' || !isset($field['source']) || !isset($_data_indexed[$field['source']])) {
                    continue;
                }
            
                if (isset($field['replace'])) {
                    if ($field['replace'] === '\n') {
                        $_data_indexed[$field['source']] = str_replace("\\n", "\r\n", $_data_indexed[$field['source']]);
                    }
                }
            
                if (isset($field['separator'])) {
                    $data[$field['destination']] = preg_split('/\s*' . $field['separator'] . '\s*/', $_data_indexed[$field['source']]);
                } else if (isset($field['fixed'])) {
                    $data[$field['destination']] = $field['fixed'];
                } else if (isset($field['append'])) {
                    $data[$field['destination']] .= $field['append'] . $_data_indexed[$field['source']];
                } else {
                    $data[$field['destination']] = $_data_indexed[$field['source']];
                }
            }
        }
        
        return $data;
    }
    
    /**
     * add some more values (overwrite that if you need some special/dynamic fields)
     *
     * @param  array recordData
     */
    protected function _addData()
    {
        return array();
    }
}
