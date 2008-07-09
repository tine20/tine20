<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: XML.php 1703 2008-04-03 18:16:32Z lkneschke $
 */


class Setup_Backend_Schema_Index_Xml extends Setup_Backend_Schema_Index_Abstract
{
    /**
     * constructor of this class
     *
     * @param string|SimpleXMLElement $_declaration the xml definition of the index
     */
    public function __construct($_declaration)
    {
        if($_declaration instanceof SimpleXMLElement) {
            $this->_setIndex($_declaration);
        } elseif ($_declaration !== NULL) {
            $declaration = new SimpleXMLElement($_declaration);
            $this->_setIndex($declaration);
        }
    }
 
    protected function _setIndex($_declaration)
    {
        foreach ($_declaration as $key => $val) {
        
            if ($key != 'field' && $key != 'reference') {
                $this->$key = (string) $val;
                
            // field definition is stored in SimpleXMLElement in quite different ways, depending on quantity
            } else if ($key == 'field') {
                if ($val instanceof SimpleXMLElement) {
                    $this->field[] = (string) $val->name;
                } else {
                    $this->field = (string) $val;
                }
            
            // reduce complexity of storage of foreign keys 
            } else if ($key == 'reference') {
                $this->referenceTable = $val->table;
                $this->referenceField = $val->field;
                $this->referenceOnUpdate = $val->onupdate;
                $this->referenceOnDelete= $val->ondelete;
                $this->field = $this->field[0];
            }
        }
    }
}