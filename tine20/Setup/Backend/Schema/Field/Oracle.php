<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

 
class Setup_Backend_Schema_Field_Oracle extends Setup_Backend_Schema_Field_Abstract
{

    public function __construct($_declaration)
    {
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
            $type = $_declaration['DATA_TYPE'];
            $length = $_declaration['LENGTH'];
            $scale = null;
            $default = $_declaration['DEFAULT'];

            switch ($_declaration['DATA_TYPE']) {
                case('NUMBER'):
                    $length = $_declaration['PRECISION'];
                    $scale = $_declaration['SCALE'];
                    $default = intval($default);
                    
                    if (is_null($length) && is_null($scale)) {
                        $type = 'float';
                    } else if ($length && $scale) {
                        $type = 'decimal';
                        $scale = intval($scale);
                        $length = intval($length);
                    } else {
                        $type = 'integer';
                        $length = intval($length);
                    }
                    break;
                
                case('enum'):
                    $this->value = explode(',', str_replace("'", '', $_declaration['TYPE_SPECIAL']));
                    break;
                
                case('VARCHAR2'):
                    $type = 'text';
                    break;
                    
                case('BLOB'):
                    $type = 'blob';
                    $length = null;
                    break;
                    
                case('CLOB'):
                    $type = 'text'; //@todo set type to CLOB?
                    $length = null;
                    break;
                }

            if (isset($_declaration['EXTRA']) && $_declaration['EXTRA'] == 'auto_increment') {
                $this->autoincrement = 'true';
            }

            if (!empty($_declaration['UNSIGNED'])) {
                $this->unsigned = 'true';
            }

            $_declaration['NULLABLE'] ? $this->notnull = 'false': $this->notnull = 'true';
            //($_declaration['COLUMN_KEY'] == 'UNI')? $this->unique = 'true': $this->unique = 'false';
            $_declaration['PRIMARY'] ? $this->primary = 'true': $this->primary = 'false';
            
            $this->type     = $type;
            $this->length   = $length;
            $this->scale    = $scale;
            $this->default  = $default;
            $this->comment  = $_declaration['COLUMN_COMMENT'];
            
        }
    }
}
