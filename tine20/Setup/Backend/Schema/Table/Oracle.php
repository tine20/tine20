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
         $field = current($_tableDefinition);
         $this->setName($field['TABLE_NAME']);
    }
      
    public function setFields($_tableDefinition)
    {
        foreach ($_tableDefinition as $fieldDefinition) {
            $this->addField(Setup_Backend_Schema_Field_Factory::factory('Oracle', $fieldDefinition));
        }
    }
}