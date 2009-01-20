<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * create opendocument files
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 */
 
abstract class OpenDocument_SpreadSheet_Cell_Abstract
{
    protected $_value;
    
    protected $_valueType;
    
    protected $_attributes = array();
    
    public function __construct($_value) {
        $this->_value = $_value; 
    }
    
    #public function __toString() {
    #    return $this->_value;
    #}
    
    abstract public function generateXML(SimpleXMLElement $_table);
    
    protected function _encodeValue()
    {
        return htmlspecialchars($this->_value, ENT_NOQUOTES, 'UTF-8');
    }
    
    public function setStyle($_styleName)
    {
        $this->_attributes[OpenDocument_Document::NS_TABLE]['table:style-name'] = $_styleName;
    }
    
    public function setAttribute($_nameSpace, $_key, $_value)
    {
        $this->_attributes[$_nameSpace][$_key] = $_value;
    }
    
    public function setFormula($_formula)
    {
        $this->_attributes[OpenDocument_Document::NS_TABLE]['table:formula'] = $_formula;
    }
    
    protected function _addAttributes(SimpleXMLElement $_cell)
    {
        foreach($this->_attributes as $nameSpace => $attributes) {
            foreach($attributes as $key => $value) {
                $_cell->addAttribute($key, $value, $nameSpace);
            }
        }
    }
}