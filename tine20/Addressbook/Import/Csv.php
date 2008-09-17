<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * csv import class for the addressbook
 * 
 * for the use of the mapping parameter => see tests/tine20/Addressbook/Import/CsvTest 
 *
 * @package     Addressbook
 * @subpackage  Import
 */
class Addressbook_Import_Csv implements Addressbook_Import_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * delimiter for csv file
     *
     * @var string
     */
    protected $_delimiter = ';';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct ()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone ()
    {

    }
    
    /**
     * holdes the instance of the singleton
     *
     * @var Addressbook_Backend_Sql
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Backend_Sql
     */
    public static function getInstance ()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Import_Csv();
        }
        return self::$_instance;
    }   
    
    /**
     * read data from import file
     *
     * @param string $_filename filename to import
     * @param array $_mapping mapping record fields to csv columns (destination => source) 
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     */
    public function read($_filename, $_mapping)
    {
        // read file
        if (!file_exists($_filename)) {
            throw new Exception("File $_filename not found.");
        }
        $fileArray = file($_filename);
        
        // get headline
        $headline = trim(array_shift($fileArray));
        
        $result = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        foreach($fileArray as $line) {
            $record = $this->_parseLine(trim($line), $headline, $_mapping);
            if (!empty($record)) {
                try {
                    $result->addRecord(new Addressbook_Model_Contact($record));
                } catch (Exception $e) {
                    //-- don't add incorrect record (name missing for example)
                }
            }
        }
        
        return $result;
    }
    
    /**
     * import the data
     *
     * @param Tinebase_Record_RecordSet of Addressbook_Model_Contact
     * @param integer $_containerId
     */
    public function import(Tinebase_Record_RecordSet $_records, $_containerId)
    {
        
    }
    
    /**
     * parse a csv line
     *
     * @param string $_line
     * @param string $_headline
     * @param array $_line
     * @return array with values
     */
    protected function _parseLine($_line, $_headline, $_mapping)
    {
        $headline = array_flip(explode($this->_delimiter, $_headline));
        $values = explode($this->_delimiter, $_line);
        
        //print_r($headline);
        //print_r($values);
        
        $result = array();
        foreach($_mapping as $destination => $source) {
            if (is_array($source)) {
                
                $result[$destination] = '';                
                foreach ($source as $key => $value) {
                    if (isset($values[$headline[$value]]) && !empty($values[$headline[$value]])) {
                        if (!is_numeric($key)) {
                            if (!empty($result[$destination])) {
                                $result[$destination] .= "\n";
                            }
                            $result[$destination] .= $key . ': ' . $values[$headline[$value]];
                        } else {
                            if (!empty($result[$destination])) {
                                $result[$destination] .= " ";
                            }
                            $result[$destination] .= $values[$headline[$value]];
                        }
                    }                    
                }
            } elseif (is_string($source) && !empty($source)) {
                if (isset($values[$headline[$source]])) {
                    $result[$destination] = $values[$headline[$source]];
                }
            }
        }
        //print_r($result);
        return $result;
    }
}
