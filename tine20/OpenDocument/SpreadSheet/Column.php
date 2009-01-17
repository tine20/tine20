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
 
class OpenDocument_SpreadSheet_Column 
{
    protected $_attributes = array();
    
    public function setStyle($_styleName)
    {
        $this->_attributes['table:style-name'] = $_styleName;
    }
    
    public function setDefaultCellStyle($_styleName)
    {
        $this->_attributes['table:default-cell-style-name'] = $_styleName;
    }
    
    public function saveXML(SimpleXMLElement $_table)
    {
        $row = $_table->addChild('table-column', NULL, OpenDocument_Document::NS_TABLE);
        foreach($this->_attributes as $key => $value) {
            $row->addAttribute($key, $value, OpenDocument_Document::NS_TABLE);
        }        
    }    
}