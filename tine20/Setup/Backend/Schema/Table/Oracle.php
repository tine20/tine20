<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @version     $Id: Mysql.php 1703 2008-04-03 18:16:32Z lkneschke $
 */


class Setup_Backend_Schema_Table_Oracle extends Setup_Backend_Schema_Table_Abstract
{

    public function __construct($_tableDefinition)
    {
         
         //@todo version
         //$version = explode(';', $_tableDefinition->TABLE_COMMENT);
         //$this->version = substr($version[0], 9);  
         
         foreach ($_tableDefinition as $fieldDefinition) {
            $this->name = substr($fieldDefinition['TABLE_NAME'], strlen(SQL_TABLE_PREFIX));
            break;
        }
        $this->setFields($_tableDefinition);
         
    }
      
    public function setFields($_tableDefinition)
    {
        foreach ($_tableDefinition as $fieldDefinition) {
            $this->addField(Setup_Backend_Schema_Field_Factory::factory('Oracle', $fieldDefinition));
//            if ($field->primary === 'true' || $field->unique === 'true' || $field->mul === 'true') {
//                $index = Setup_Backend_Schema_Index_Factory::factory('Oracle', $tableColumn);
//                        
//                // get foreign keys
//                $select = $this->_db->select()
//                  ->from('information_schema.KEY_COLUMN_USAGE')
//                  ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX .  $_tableName)
//                  ->where($this->_db->quoteIdentifier('COLUMN_NAME') . ' = ?', $tableColumn['COLUMN_NAME']);
//
//                $stmt = $select->query();
//                $keyUsage = $stmt->fetchAll();
//
//                foreach ($keyUsage as $keyUse) {
//                    if ($keyUse['REFERENCED_TABLE_NAME'] != NULL) {
//                        $index->setForeignKey($keyUse);
//                    }
//                }
//                $existingTable->addIndex($index);
//            }
        }
    }
}