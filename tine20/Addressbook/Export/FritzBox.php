<?php
/**
 * Addressbook_Export_FritzBox
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add test for this export
 */

/**
 * Addressbook_Export_FritzBox
 * 
 * @package     Addressbook
 * @subpackage  Export
 * 
 * @deprecated this is no longer supported 
 */
class Addressbook_Export_FritzBox extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'FRITZ.Box_Telefonbuch_';
    
    /**
     * @var XMLWriter
     */
    protected $_writer = null;
    
    /**
    * add rows to csv body
    *
    * @param Tinebase_Record_RecordSet $_records
    */
    public function processIteration($_records)
    {
        foreach($_records as $contact) {
            $this->_addContact($contact);
        }
    }
    
    /**
     * add contact
     * 
     * @param Addressbook_Model_Contact $_contact
     */
    protected function _addContact($_contact)
    {
        $this->_writer->startElement("contact");
        
        $this->_writer->startElement("realName");
        $this->_writer->text($_contact->n_fn);
        $this->_writer->endElement();
        
        $this->_writer->startElement("telephony");
        $this->_addNumber($_contact->tel_home, "home");
        $this->_addNumber($_contact->tel_work, "work");
        $this->_addNumber($_contact->tel_cell ? $_contact->tel_cell : $_contact->tel_cell_private, "mobile");
        $this->_writer->endElement();
        
        $this->_writer->startElement("services");
        $this->_writer->endElement();
        
        $this->_writer->startElement("setup");
        $this->_writer->endElement();
        
        $this->_writer->endElement();
    }
    
    /**
     * add number
     * 
     * @param integer $_number
     * @param string $_type
     */
    protected function _addNumber($_number, $_type)
    {
        $this->_writer->startElement("number");
        $this->_writer->writeAttribute("type", $_type);
        $this->_writer->writeAttribute("quickdial", "");
        $this->_writer->writeAttribute("vanity", "");
        $this->_writer->writeAttribute("prio", "");
        $this->_writer->text($_number);
        $this->_writer->endElement();
    }
    
    /**
     * generate export
     * 
     * @return mixed filename/generated object/...
     */
    public function generate()
    {
        $this->_writer = new XMLWriter();
        $this->_writer->openURI('php://output');
        $this->_writer->startDocument("1.0", "iso-8859-1");
        $this->_writer->startElement("phonebooks");
        $this->_writer->startElement("phonebook");
 
        $this->_exportRecords();
         
        $this->_writer->endDocument();
        $this->_writer->flush();
    }
    
    /**
     * get download content type
     * 
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'text/xml';
    }
}
