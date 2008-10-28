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
 * 
 * a sample mapping:
 * --
 * array(
    'mapping' => array(
        'adr_one_locality'      => 'Ort',
        'adr_one_postalcode'    => 'Plz',
        'adr_one_street'        => 'Straße',
        'org_name'              => 'Name1',
        'org_unit'              => 'Name2',
        'note'                  => array(
            'Mitarbeiter'           => 'inLab Spezi',
            'Anzahl Mitarbeiter'    => 'ANZMitarbeiter',
            'Bemerkung'             => 'Bemerkung',
        ),
        'tel_work'              => 'TelefonZentrale',
        'tel_cell'              => 'TelefonDurchwahl',
        'n_family'              => 'Nachname',
        'n_given'               => 'Vorname',
        'n_prefix'              => array('Anrede', 'Titel'),
        'container_id'                 => array(
            'inLab Spezi'           => array(
            'Name 1'                 => 92,
            'Name 2'                 => 66,
            'Name 3'                 => 88
            ),
        ),
    ),
    //'containerId' => 2,
 *
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
     * @param   string $_filename filename to import
     * @param   array $_mapping mapping record fields to csv columns (destination => source) 
     * @return  Tinebase_Record_RecordSet of Addressbook_Model_Contact
     * @throws  Addressbook_Exception_NotFound if file not found
     */
    public function read($_filename, $_mapping)
    {
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
        
        return $result;
    }
    
    /**
     * import the data
     *
     * @param Tinebase_Record_RecordSet $_records Addressbook_Model_Contact records
     * @param integer $_containerId
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     * 
     * @todo create Abstract and move import() there when new import formats are implemented 
     */
    public function import(Tinebase_Record_RecordSet $_records, $_containerId = NULL)
    {
        if ($_containerId === NULL) {
            // get personal container
            $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
                Zend_Registry::get('currentAccount'), 
                'Addressbook', 
                Zend_Registry::get('currentAccount'), 
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
            $newContact = $addressbookController->createContact($contact);
            $result->addRecord($newContact);
        }
        
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
