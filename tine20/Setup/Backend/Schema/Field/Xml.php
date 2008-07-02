<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: XML.php 1703 2008-04-03 18:16:32Z lkneschke $
 */


class Setup_Backend_Schema_Field_Xml extends Setup_Backend_Schema_Field_Abstract
{
    public function __construct($_definition = NULL)
    {
        if($_definition !== NULL) {
            $this->_setField($_definition);
        }
    }

    /**
     * set Setup_Backend_Schema_Table from a given XML 
     *
     * @param SimpleXMLElement $_declaration
     */
    protected function _setField($_declaration)
    {
        $this->name = $_declaration->name;
        $this->type = $_declaration->type;

        if(!empty($_declaration->comment)) {
            $this->comment = $_declaration->comment;
        }

        if(isset($_declaration->length)) {
            $this->length = (int) $_declaration->length;
        } else {
            $this->length = NULL;
        }

        if(isset($_declaration->notnull)) {
            $this->notnull = (strtolower($_declaration->notnull) == 'true') ? true : false;
        } else {
            $this->notnull = false;
        }

        switch ($this->type) {
            case 'text':
                if ($this->length === NULL) {
                    $this->type = 'text';
                } else {
                    $this->type = 'varchar';
                }
                break;
            
            case 'tinyint':
                $this->type = 'integer';
                $this->length = 4;
                break;
            
            case 'clob':
                $this->type = 'text';
                $this->length = 65535;
                break;
            
            case 'blob':
                $this->type = 'longblob';
                $this->length = 4294967295;
                break;
            
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

            case 'datetime':
                $this->type = 'datetime';
                break;
    
            case 'double':
                $this->type = 'double';
                break;
            
            case 'float':
                $this->type = 'float';
                break;
            
            case 'boolean':
                $this->type =  'integer';
                $this->length = 4;
                break;
            
            case 'integer':
                $this->type =  'integer';
                break;
            
/*            case ('decimal'):
                $this->type =  "decimal";
                $this->value = (string) $_declaration->value ;
                if (empty($this->default)) {
                    $this->default = 'NULL';
                }
              
                break;
*/      
            default :
                throw new Exception('unsupported type ' . print_r($_declaration, true));
                break;
        }

        /**
         * set default values
         */        
        switch ($this->type) {
            case 'text':
            case 'clob':
            case 'blob':
            case 'enum':
                if(isset($_declaration->default)) {
                    if(strtolower($_declaration->default) == 'null') {
                        $this->default = NULL;
                    } else {
                        $this->default = (string) $_declaration->default;
                    }
                }
                break;
            
            case 'tinyint':
            case 'integer':
                if ($_declaration->autoincrement) {
                    $this->notnull = true;
                    $this->autoincrement = true;
                } else {
                    if(isset($_declaration->default)) {
                        if(strtolower($_declaration->default) == 'null') {
                            $this->default = NULL;
                        } else {
                            $this->default = (int) $_declaration->default;
                        }
                    }
                    if(isset($_declaration->unsigned)) {
                        $this->unsigned = (strtolower($_declaration->unsigned) == 'true') ? true : false;
                    } else {
                        $this->unsigned = false;
                    }
                }
                break;
            
            case 'datetime':
                $this->type = 'datetime';
                if(isset($_declaration->default)) {
                    $this->default = NULL;
                }
                break;
    
            case 'double':
                if(isset($_declaration->default)) {
                    if(strtolower($_declaration->default) == 'null') {
                        $this->default = NULL;
                    } else {
                        $this->default = (double) $_declaration->default;
                    }
                }
                if(isset($_declaration->unsigned)) {
                    $this->unsigned = (strtolower($_declaration->unsigned) == 'true') ? true : false;
                } else {
                    $this->unsigned = false;
                }

                break;
            
            case 'float':
                if(isset($_declaration->default)) {
                    if(strtolower($_declaration->default) == 'null') {
                        $this->default = NULL;
                    } else {
                        $this->default = (float) $_declaration->default;
                    }
                }
                if(isset($_declaration->unsigned)) {
                    $this->unsigned = (strtolower($_declaration->unsigned) == 'true') ? true : false;
                } else {
                    $this->unsigned = false;
                }

                break;
            
            case 'boolean':
                if(isset($_declaration->default)) {
                    if(strtolower($_declaration->default) == 'false') {
                        $this->default = 0;
                    } else {
                        $this->default = 1;
                    }
                }
                $this->unsigned = true;
                break;
            
        }
        
        /**
         * set signed / unsigned
         */        
        switch ($this->type) {
            case 'tinyint':
            case 'integer':
            case 'double':
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