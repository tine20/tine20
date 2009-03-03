<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        finish implementation
 */

/**
 * abstract csv import class
 * 
 * @package     Tinebase
 * @subpackage  Import
 * 
 */
abstract class Tinebase_Import_Csv_Abstract implements Tinebase_Import_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_options;
    
    /**
     * delimiter for csv file
     *
     * @var string
     */
    protected $_delimiter = ';';
    
    /**
     * the constructor
     *
     */
    public function __construct ($_options = NULL)
    {
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * read data from import file
     *
     * @param   string $_from
     * @return  Tinebase_Record_RecordSet
     * 
     * @todo implement
     */
    public function read($_from)
    {
        /*
        // read file
        if (!file_exists($_filename)) {
            throw new Addressbook_Exception_NotFound("File $_filename not found.");
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
        */
        
        $result = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        return $result;
    }
    
    /**
     * import the data
     *
     * @param Tinebase_Record_RecordSet $_records
     * @return Tinebase_Record_RecordSet
     * 
     * @todo implement
     * @todo add 'dry run' functionality
     */
    public function import(Tinebase_Record_RecordSet $_records)
    {
        /*
        if ($_containerId === NULL) {
            // get personal container
            $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer( 
                Tinebase_Core::getUser(),
                'Addressbook', 
                Tinebase_Core::getUser(), 
                Tinebase_Model_Container::GRANT_EDIT
            );
            $containerId = $personalContainer[0]->getId();
        } else {
            $containerId = $_containerId;
        }
        
        $addressbookController = Addressbook_Controller_Contact::getInstance();
        $result = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        foreach ($_records as $contact) {
            // set container_id/container id only if it isn't set already
            if (empty($contact->container_id)) {
                $contact->container_id = $containerId;
            }
            $newContact = $addressbookController->create($contact);
            $result->addRecord($newContact);
        }
        
        */
        return $result;
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
        
        //print_r($_mapping);
        //print_r($headline);
        //print_r($values);
        
        $result = array();
        foreach($_mapping as $destination => $source) {
            if (is_array($source)) {
                
                $result[$destination] = '';                
                foreach ($source as $key => $value) {
                    if (is_array($value) || (isset($headline[$value]) && isset($values[$headline[$value]]) && !empty($values[$headline[$value]]))) {
                        if (is_array($value) && !empty($value)) {
                            // match to defined values (i.e. user -> container_id/container id)
                            $keyForValue = $values[$headline[$key]];
                            if (isset($value[$keyForValue])) {
                                $result[$destination] = $value[$keyForValue];
                            }
                        } elseif (!is_numeric($key)) {
                            // add multiple values to one destination field with $key added 
                            if (!empty($result[$destination])) {
                                $result[$destination] .= "\n";
                            }
                            $result[$destination] .= $key . ': ' . $values[$headline[$value]];
                        } else {
                            // add multiple values to one destination field (separated with spaces)
                            if (!empty($result[$destination])) {
                                $result[$destination] .= " ";
                            }
                            $result[$destination] .= $values[$headline[$value]];
                        }
                    }                    
                }
            } elseif (is_string($source) && !empty($source)) {
                // add single value to destination 
                if (isset($headline[$source]) && isset($values[$headline[$source]])) {
                    $result[$destination] = $values[$headline[$source]];
                }
            }
        }
        //print_r($result);
        return $result;
    }
}
