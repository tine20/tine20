<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract class to convert a string to contact record and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_String implements Tinebase_Convert_Interface
{
    /**
     * unrecognized tokens
     * 
     * @var array
     */    
    protected $_unrecognizedTokens = array();
    
    /**
     * config (parsing rules)
     * 
     * @var array
     */    
    protected $_config = array();
    
    /**
     * the constructor
     * 
     * @param  array  $_config  config rules
     */
    public function __construct($_config = array())
    {
        // @todo if config is empty -> get from file
        if (! empty($_config)) {
            $this->_config = $_config;
        }
    }

    /**
     * converts string to Addressbook_Model_Contact
     * 
     * @param  string  						$_blob   the string to parse
     * @param  Tinebase_Record_Abstract		$_record  update existing contact
     * @return Addressbook_Model_Contact
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = null)
    {
        $contact = new Addressbook_Model_Contact();
        
        // @todo loop rules
        // @todo fill contact record + $this->_unrecognizedTokens
        
        return $contact;
    }
    
    /**
    * converts Addressbook_Model_Contact to string
    *
    * @param  Addressbook_Model_Contact  $_model
    * @return string
    */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        return $_record->__toString();
    }
    
    /**
     * returns the unrecognized tokens
     * 
     * @return array
     */
    public function getUnrecognizedTokens()
    {
        return $this->_unrecognizedTokens;
    }
}
