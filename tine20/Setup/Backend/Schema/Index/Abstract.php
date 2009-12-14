<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Field.php 1703 2008-04-03 18:16:32Z lkneschke $
 */


abstract class Setup_Backend_Schema_Index_Abstract extends Setup_Backend_Schema_Abstract
{
    
    /**
     * the name of the field(s)/column(s) in its own table 
     *
     * @var array
     */
    public $field = array();

    /**
     * index defines primary key
     *
     * @var boolean
     */
    public $primary;

    /**
     * index defines unique key
     *
     * @var boolean
     */
    public $unique;

    /**
     * index defines any key, except (foreign, unique or primary)
     *
     * @var boolean
     */
    public $mul;
    
    /**
     * index defines foreign key
     *
     * @var boolean
     */
    public $foreign;
    
    /**
     * name of referenced table of foreign key
     *
     * @var string
     */
    public $referencetable;
    
    /**
     * name of referenced table field/column of foreign key
     *
     * @var string
     */
    public $referencefield;
    
    /**
     * defines behaviour of foreign key
     *
     * @var boolean
     */
    public $referenceOnDelete;
    
    /**
     * defines behaviour of foreign key
     *
     * @var boolean
     */
    public $referenceOnUpdate;
    
    /**
     * lenght of index 
     * 
     * @var integer
     */
    public $length = NULL;
    
    abstract protected function _setIndex($_declaration);

    public function setForeignKey($_foreign)
    {
        $this->foreign = 'true';
    }

//    
//    /**
//     * set index from declaration 
//    * @param stdClass $_declaration
//     * NOT IMPLEMENTED YET
//     */  
//    public function addIndex($_definition)
//    {
//        foreach ($this->declaration['index'] as $index) {
//            if ($index->field['name'] == $_definition['COLUMN_NAME']) {
//                if ($_definition['CONSTRAINT_NAME'] == 'PRIMARY') {
//                    $index->setName($_definition['COLUMN_NAME']);
//                } else {
//                    $index->setName($_definition['CONSTRAINT_NAME']);
//                }
//            }
//        }
//    }
//    
//    /**
//     * set index from declaration 
//    * @param stdClass $_declaration
//     * NOT IMPLEMENTED YET
//     */     
//    public function setIndex($_definition)
//    {
//        foreach ($this->declaration['index'] as $index) {
//            if ($index->field['name'] == $_definition['COLUMN_NAME']) {
//                if ($_definition['CONSTRAINT_NAME'] == 'PRIMARY') {
//                    $index->setName($_definition['COLUMN_NAME']);
//                } else {
//                    $index->setName($_definition['CONSTRAINT_NAME']);
//                }
//            }
//        }
//    }
//    
//    /**
//     * set index from declaration 
//    * @param stdClass $_declaration
//     * NOT IMPLEMENTED YET
//     */  
//    public function setForeign($_definition)
//    {
//        foreach ($this->declaration['index'] as $index) {
//            //// auto shutup by cweiss: echo "<h1>"  . substr($_definition['CONSTRAINT_NAME'], strlen(SQL_TABLE_PREFIX)) . "/" .$index->field->name.  "</h1>";
//            
//            //if ($index->field->name == substr($_definition['CONSTRAINT_NAME'], strlen(SQL_TABLE_PREFIX)))
//            //{
//                $index->setForeignKey($_definition);
//            //}
//        }
//    }
    
}