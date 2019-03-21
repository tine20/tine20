<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

 
class Setup_Backend_Schema_Field_Pgsql extends Setup_Backend_Schema_Field_Abstract
{

    public function __construct($_declaration)
    {
        $this->unsigned = false; //not accepted by PostgreSQL
        $this->_setField($_declaration);
        
        parent::__construct($_declaration);
    }
    
    /**
     * set Setup_Backend_Schema_Table from a given database query 
     *
     * @todo this function does not work is_array and -> does not fit together
     * @param stdClass $_declaration
     */    
    protected function _setField($_declaration)
    {
        if (is_array($_declaration)) {
            $this->name = $_declaration['COLUMN_NAME'];
            $type       = $_declaration['DATA_TYPE'];
            $default    = $_declaration['COLUMN_DEFAULT'];
            $length     = $_declaration['CHARACTER_MAXIMUM_LENGTH'];
            $scale      = null;
            
            switch ($_declaration['DATA_TYPE']) {
                case('tinyint'):
                case('mediumint'):
                case('bigint'):
                case('int'):
                    $type = 'integer';
                    $default = intval($default);
                    $length = null;
                    break;

                case('decimal'):
                    $length = $_declaration['NUMERIC_PRECISION'];
                    $scale  = $_declaration['NUMERIC_SCALE'];
                    break;
                
                case('double'):
                    $length = null;
                    $type = 'float';
                    break;
                    
                case('enum'):
                    $this->value = explode(',', str_replace("'", '', substr($_declaration['COLUMN_TYPE'], 5, (strlen($_declaration['COLUMN_TYPE']) - 6))));
                    break;
                    
                case('longblob'):
                    $type = 'blob';
                    $length = null;
                    break;
                
                case('longtext'): //@todo should return clob?
                case('varchar'):
                    $type = 'text';
                    break;
                }

            if ($_declaration['EXTRA'] == 'auto_increment') {
                $this->autoincrement = 'true';
            }

            ($_declaration['IS_NULLABLE'] == 'NO')? $this->notnull = 'true': $this->notnull = 'false';
            ($_declaration['COLUMN_KEY'] == 'UNI')? $this->unique = 'true': $this->unique = 'false';
            ($_declaration['COLUMN_KEY'] == 'PRI')? $this->primary = 'true': $this->primary = 'false';
            ($_declaration['COLUMN_KEY'] == 'MUL')? $this->mul = 'true': $this->mul = 'false';
            
            $this->default  = $default;
            $this->comment  = $_declaration['COLUMN_COMMENT'];
            $this->length   = $length;
            $this->scale    = $scale;
            $this->type     = $type;
        }
    }
}
