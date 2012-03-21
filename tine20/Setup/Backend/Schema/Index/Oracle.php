<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

 
class Setup_Backend_Schema_Index_Oracle extends Setup_Backend_Schema_Index_Abstract
{

    public function __construct($_declaration)
    {
        $this->_setIndex($_declaration);
    }

    public function setForeignKey($_declaration)
    {
        parent::setForeignKey($_declaration);
        //@todo: correclty read reference information
//        $this->referencetable = substr($_declaration['REFERENCED_TABLE_NAME'], strlen(SQL_TABLE_PREFIX));
//        $this->referencefield = $_declaration['REFERENCED_COLUMN_NAME'];
//        $this->referenceOnDelete;
//        $this->referenceOnUpdate;
    }
    
    
    /**
     * set Setup_Backend_Schema_Table from a given database query 
     *
     * @param stdClass $_declaration
     */    
    protected function _setIndex($_declaration)
    {
        $this->setName($_declaration['COLUMN_NAME']);
        $type = '';
        $length= '';
        switch ($_declaration['DATA_TYPE']) {
            case('NUMBER'):
                $type = 'integer';
                $length = $_declaration['LENGTH'];
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
                break;
            
            default:
                $length = $_declaration['LENGTH'];
                $type = $_declaration['DATA_TYPE'];
            }

        if ($_declaration['EXTRA'] == 'auto_increment') {
            $this->autoincrement = 'true';
        }

        if (!empty($_declaration['UNSIGNED'])) {
           $this->unsigned = 'true';
        }

        
        $_declaration['NULLABLE'] ? $this->notnull = 'false': $this->notnull = 'true';
        //($_declaration['COLUMN_KEY'] == 'UNI')? $this->unique = 'true': $this->unique = 'false';
        $_declaration['PRIMARY'] ? $this->primary = 'true': $this->primary = 'false';
        //($_declaration['COLUMN_KEY'] == 'MUL')? $this->mul = 'true': $this->mul = 'false';

//        $this->comment = $_declaration['COLUMN_COMMENT'];
        $this->length = $length;
        $this->type = $type;
    }
}
