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
     * csv headline
     * 
     * @var array
     */
    protected $_headline = array();
    
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
        $this->_options = array_merge($this->_options, array(
            'maxLineLength'     => 8000,
            'delimiter'         => ',',
            'enclosure'         => '"',
            'escape'            => '\\',
            'encoding'          => 'UTF-8',
            'encodingTo'        => 'UTF-8',
            'mapping'           => '',
            'headline'          => 0,
            'use_headline'      => 1,
        ));
        
        parent::__construct($_options);
        
        if (empty($this->_options['model'])) {
            throw new Tinebase_Exception_InvalidArgument(get_class($this) . ' needs model in config.');
        }
        
        $this->_setController();
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
     * do something before the import
     * 
     * @param resource $_resource
     */
    protected function _beforeImport($_resource = NULL)
    {
        // get headline
        if (isset($this->_options['headline']) && $this->_options['headline']) {
            $this->_headline = $this->_getRawData($_resource);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Got headline: ' . implode(', ', $this->_headline));
            if (! $this->_options['use_headline']) {
                // just read headline but do not use it
                $this->_headline = array();
            }
        }        
    }
    
    /**
     * do the mapping and replacements
     *
     * @param array $_data
     * @return array
     */
    protected function _doMapping($_data)
    {
        $data = array();
        $_data_indexed = array();

        if (! empty($this->_headline) && sizeof($this->_headline) == sizeof($_data)) {
            $_data_indexed = array_combine($this->_headline, $_data);
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
}
