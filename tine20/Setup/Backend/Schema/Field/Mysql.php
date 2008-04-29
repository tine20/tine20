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

 
 class Setup_Backend_Schema_Field_Mysql extends Setup_Backend_Schema_Field_Abstract
 {
 
    public function __construct($_declaration)
    {
        $this->_setField($_declaration);
    }
    
    /**
     * set Setup_Backend_Schema_Table from a given database query 
     *
     * @param stdClass $_declaration
     */    
    protected function _setField($_declaration)
    {    
        if (is_array($_declaration)) {
            
            $this->name = $_declaration['COLUMN_NAME'];
            $type = '';
            $length= '';
            
            switch ($_declaration['DATA_TYPE']) {
                case('int'):
                    $type = 'integer';
                    $length = $_declaration['NUMERIC_PRECISION'] + 1;
                    break;
            
                case('tinyint'):
                    $type = 'integer';
                    $length = $_declaration['NUMERIC_PRECISION'] + 1;
                    break;
                
                case('enum'):
                    $type = $_declaration['DATA_TYPE'];
                    $this->value = explode(',', str_replace("'", '', substr($_declaration['COLUMN_TYPE'], 5, (strlen($_declaration['COLUMN_TYPE']) - 6))));
                    break;
                
                case('varchar'):
                    $length = $_declaration['CHARACTER_MAXIMUM_LENGTH'];
                    $type = 'text';
                
                default:
                    $length = $_declaration['CHARACTER_MAXIMUM_LENGTH'];
                    $type = $_declaration['DATA_TYPE'];
            }

            if ($_declaration['EXTRA'] == 'auto_increment') {
                $this->autoincrement = 'true';
            }

            if (preg_match('/unsigned/', $_declaration['COLUMN_TYPE'])) {
                $this->unsigned = 'true';
            }

            ($_declaration['IS_NULLABLE'] == 'NO')? $this->notnull = 'true': $this->notnull = 'false';
            ($_declaration['COLUMN_KEY'] == 'UNI')? $this->unique = 'true': $this->unique = 'false';
            ($_declaration['COLUMN_KEY'] == 'PRI')? $this->primary = 'true': $this->primary = 'false';
            ($_declaration['COLUMN_KEY'] == 'MUL')? $this->mul = 'true': $this->mul = 'false';

            $this->comment = $_declaration['COLUMN_COMMENT'];
            $this->length = $length;
            $this->type = $type;
        }
    }
}