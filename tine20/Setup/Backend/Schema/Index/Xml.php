<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
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
        $declaration = $_declaration instanceof SimpleXMLElement ? $_declaration : new SimpleXMLElement($_declaration);
        
        $this->_setIndex($declaration);
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
                    
                    if (isset($val->length)) {
                        $this->fieldLength[(string) $val->name] = (int) $val->length;
                    }
                } else {
                    $this->field   = (string) $val;
                }
            
            // reduce complexity of storage of foreign keys 
            } else if ($key == 'reference') {
                $this->referenceTable    = (string) $val->table;
                $this->referenceField    = (string) $val->field;
                $this->referenceOnUpdate = (string) $val->onupdate;
                $this->referenceOnDelete = (string) $val->ondelete;
                $this->field             = $this->field[0];
            }
        }

        if (empty($this->name)) {
            $this->setName(join('-', (array)$this->field));
        }
    }
}
