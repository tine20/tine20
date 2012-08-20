<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */


class Setup_Backend_Schema_Field_Xml extends Setup_Backend_Schema_Field_Abstract
{
    /**
     * constructor of this class
     *
     * @param string|SimpleXMLElement $_declaration the xml definition of the field
     */
    public function __construct($_declaration = NULL)
    {
        if($_declaration instanceof SimpleXMLElement) {
            $this->_setField($_declaration);
        } elseif ($_declaration !== NULL) {
            $declaration = new SimpleXMLElement($_declaration);
            $this->_setField($declaration);
        }
        
        parent::__construct($_declaration);
    }

    /**
     * set Setup_Backend_Schema_Table from a given XML 
     *
     * @param   SimpleXMLElement $_declaration
     * @throws  Setup_Exception
     */
    protected function _setField($_declaration)
    {
     
        $this->name = (string)$_declaration->name;
        $this->type = (string)$_declaration->type;

        if(!empty($_declaration->comment)) {
            $this->comment = $_declaration->comment;
        }

        if(isset($_declaration->length)) {
            $this->length = (int) $_declaration->length;
        } else {
            $this->length = NULL;
        }
        
        if(isset($_declaration->scale)) {
            $this->scale = (int) $_declaration->scale;
        } else {
            $this->scale = NULL;
        }

        if(isset($_declaration->notnull)) {
            $this->notnull = (strtolower($_declaration->notnull) == 'true') ? true : false;
        } else {
            $this->notnull = false;
        }

        switch ($this->type) {
            case 'enum':
                if (isset($_declaration->value[0])) {
                    $i = 0;
                    $array = array();
                    while (isset($_declaration->value[$i])) {
                        $array[] = (string) $_declaration->value[$i];
                        $i++;
                    }
                    $this->value = $array;
                }
                break;
        }

        /**
         * set default values
         */     

        if(!isset($_declaration->default) || strtolower($_declaration->default) == 'null') {
            $this->default = NULL;
        } else {
            switch ($this->type) {
                case 'boolean':
                    if ($_declaration->default != 'true') {
                        $this->default = 0;
                    } else {
                        $this->default = 1;
                    }
                    break;
                
                case 'integer':
                    $this->default = (int) $_declaration->default;
                    break;
    
                case 'datetime':
                    $this->type = 'datetime';
                    $this->default = NULL; //@todo default value is ignored - is this intended?
                    break;
                
                case 'float':
                    $this->default = (float) $_declaration->default;
                    break;

                case 'text':
                case 'clob':
                case 'blob':
                case 'enum':
                default:
                    $this->default = (string) $_declaration->default;
                    break;
            }
        }
        
        
        //special type handling
        switch ($this->type) {
            case 'boolean':
                $this->unsigned = true;
                break;
                
            case 'integer':
                if ($_declaration->autoincrement) {
                    $this->notnull = true;
                    $this->autoincrement = true;
                }
                break;
        }
        
        /**
         * set signed / unsigned
         */        
        switch ($this->type) {
            case 'integer':
            case 'float':
                if(isset($_declaration->unsigned)) {
                    $this->unsigned = (strtolower($_declaration->unsigned) == 'true') ? true : false;
                } else {
                    $this->unsigned = true;
                }

                break;
            
        }
    }
}
