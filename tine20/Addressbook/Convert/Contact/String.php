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
     * @var Zend_Config
     */    
    protected $_config = array();

    /**
     * config filename
     * 
     * @var string
     */
    const CONFIG_FILENAME = 'config/convert_from_string.xml';
    
    /**
     * the constructor
     * 
     * @param  array  $_config  config rules
     */
    public function __construct($_config = array())
    {
        if (! empty($_config)) {
            $this->_config = new Zend_Config($_config);
        } else {
            // read file with Zend_Config_Xml
            $filename = dirname(__FILE__) . '/' . self::CONFIG_FILENAME;
            $this->_config = new Zend_Config_Xml($filename);
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
        
        foreach ($this->_config->rules->rule as $rule) {
            // @todo fill contact record + remove recognized tokens
            //echo $rule->regex;
        }
        
        // @todo remaining tokens are $this->_unrecognizedTokens 
        
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
