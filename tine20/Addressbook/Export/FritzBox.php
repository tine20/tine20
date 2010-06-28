<?php
class Addressbook_Export_FritzBox extends Tinebase_Export_Abstract
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
    
    protected function _addContact($_writer, $_contact)
    {
        $_writer->startElement("contact");
        
        $_writer->startElement("realName");
        $_writer->text($_contact->n_fn);
        $_writer->endElement();
        
        $_writer->startElement("telephony");
        $this->_addNumber($_writer, $_contact->tel_home, "home");
        $this->_addNumber($_writer, $_contact->tel_work, "work");
        $this->_addNumber($_writer, $_contact->tel_cell ? $_contact->tel_cell : $_contact->tel_cell_private, "mobile");
        $_writer->endElement();
        
        $_writer->startElement("services");
        $_writer->endElement();
        
        $_writer->startElement("setup");
        $_writer->endElement();
        
        $_writer->endElement();
    }
    
    protected function _addNumber($_writer, $_number, $_type)
    {
        $_writer->startElement("number");
        $_writer->writeAttribute("type", $_type);
        $_writer->writeAttribute("quickdial", "");
        $_writer->writeAttribute("vanity", "");
        $_writer->writeAttribute("prio", "");
        $_writer->text($_number);
        $_writer->endElement();
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
 
        $records = $this->_getRecords();
        foreach($this->_getRecords() as $contact) {
            $this->_addContact($this->_writer, $contact);
        }
         
        $this->_writer->endDocument();
        $this->_writer->flush();
    }
     
    /**
     * get export document object
     * 
     * @return Object the generated document
     */
    public function getDocument() {}
    
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