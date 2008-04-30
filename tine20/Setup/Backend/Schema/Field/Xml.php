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
        $this->_setField($_definition);
    }

    /**
     * set Setup_Backend_Schema_Table from a given XML 
     *
     * @param SimpleXMLElement $_declaration
     */
    protected function _setField($_declaration)
    {
        $this->name = (string) $_declaration->name;
        $this->length = (int) $_declaration->length;
        $this->type = (string) $_declaration->type;
        $this->notnull = (string) $_declaration->notnull;
        $this->comment = (string) $_declaration->comment;
        
        if (!empty ($_declaration->default) || $_declaration->default == '0') {
            $this->default = (string) $_declaration->default;
        }
        
        if (!empty ($_declaration->unsigned)) {
            $this->unsigned = (string) $_declaration->unsigned;
        } else if ( $this->type == 'integer') {
            $this->unsigned = 'true';
        }
        
        if ($_declaration->autoincrement) {
            $this->notnull = 'true';
            $this->length = 11;
            $this->autoincrement = 'true';
            $this->unsigned = 'true';
        }
        
        if (!isset ($_declaration->notnull)) {
            $this->notnull = 'false';
        }

        if (empty($_declaration->length) && $this->type == 'integer') {
            $this->length = 11;
        }
        
        switch ($this->type) {
            case('text'):
                $this->type = 'varchar';
                if ($this->length == 0) {
                    $this->type = 'text';
                    $this->length = 65535;
                }
                break;
            
            case('tinyint'):
                $this->type = 'integer';
                $this->length = 4;
                break;
            
            case ('clob'):
                $this->type = 'text';
                $this->length = 65535;
                break;
            
            case ('blob'):
                $this->type = 'longblob';
                $this->length = 4294967295;
                break;
            
            case ('enum'):
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

            case ('datetime'):
               $this->type = 'datetime';
                break;
    
            case ('double'):
                $this->type = 'double';
                break;
            
            case ('float'):
                $this->type = 'float';
                break;
            
            case ('boolean'):
                $this->type =  'tinyint';
                $this->length = 4;
                if ($this->default == 'false') {
                    $this->default = 0;
                } else {
                    $this->default = 1;
                }
                break;
            
            case ('decimal'):
              //$this->type =  "decimal (" . (string) $_declaration->value . ")" ;
              $this->type =  "decimal";
			  $this->value = (string) $_declaration->value ;
              
			  break;
        
            default :
                $this->type = 'integer';
        }

        $this->mul = 'false';
        $this->primary = 'false';
        $this->unique = 'false';
    }
}