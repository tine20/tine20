<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 *
 */

/**
 * setup backend class for MySQL 5.0 +
 * 
 * @package     Setup
 */
class Setup_Backend_Mysql
{
    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function createTable($_table)
    {
        $statement = "CREATE TABLE `" . SQL_TABLE_PREFIX . $_table->name . "` (\n";

        foreach ($_table->declaration->field as $field) {
            if(isset($field->name)) {
                print_r($field);
               $statement .= $this->_getMysqlDeclarations($field) . ",\n";
            }
        }

        foreach ($_table->declaration->index as $key) {
        
            $statement .= $this->_getMysqlIndexDeclarations($key) . " `" . SQL_TABLE_PREFIX . $key->name . "` (" ;

            foreach ($key->field as $keyfield) {
                $statement .= "`"  . (string)$keyfield->name . "`,";
            }
                
            $statement = substr($statement, 0, (strlen($statement)-1)) . "),\n";
        }

        $statement = substr($statement, 0, (strlen($statement)-2)) ;
        $statement .= ")";

        if (isset($_table->engine))
        {
            $statement .=     "\n ENGINE=" . $_table->engine . " DEFAULT CHARSET=" . $_table->charset;
        }
        else
        {
            $statement .=     "\n ENGINE=InnoDB DEFAULT CHARSET=utf8";
        }
        
        echo "<pre>$statement</pre>";

        Zend_Registry::get('dbAdapter')->query($statement);
    }

    /**
     * check's if a given table exists
     *
     * @param string $_tableSchema
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableSchema, $_tableName)
    {
        $select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.tables')
          ->where('TABLE_SCHEMA = ?', $_tableSchema)
          ->where('TABLE_NAME = ?', $_tableName);
          
        $stmt = $select->query();
        
        $table = $stmt->fetchObject();
        
        if($table === false) {
          return false;
        }
      
        return true;
    }
    
    private function _getMysqlDeclarations($_field)
    {
        $definition = '`' . $_field->name . '`';

        switch ($_field->type)
        {
            case('text'):
            {
                if (isset($_field->length))
                {
                    $definition .= ' varchar(' . $_field->length . ') ';
                }
                else
                {
                    $definition .= ' ' . $_field->type . ' ';
                }
                break;
            }
            case ('integer'):
            {
                if (isset($_field->length))
                {
                    if ($_field->length > 19)
                    {
                        $definition .= ' bigint(' . $_field->length . ') ';}
                    else if($_field->length < 5)
                    {
                        $definition .= ' tinyint(' . $_field->length . ') ';
                    }
                    else
                    {
                        $definition .= ' int(' . $_field->length . ') ';
                    }
                }
                else
                {
                    $definition .= ' int(11) ';
                }
                break;
            }
            case ('clob'):
            {
                $definition .= ' text ';
                break;
            }
            case ('blob'):
            {
                $definition .= ' longblob ';
                break;
            }
            case ('enum'):
            {
                foreach ($_field->value as $value)
                {
                    $values[] = $value;
                }
                $definition .= " enum('" . implode("','", $values) . "') ";
            
                break;
            }
            case ('datetime'):
            {
                $definition .= ' datetime ';
                break;
            }
            case ('double'):
            {
                $definition .= ' double ';
                break;
            }
            case ('decimal'):
            {
                $definition .= " decimal (" . $_field->value . ")" ;
            }
        }
            
        if (isset($_field->unsigned))    
        {
            $definition .= ' unsigned ';
        }
        
        
        if (isset($_field->autoincrement))    
        {
            $definition .= ' auto_increment';
        }
        
        if (isset($_field->default))
        {
            $definition .= "default '" . $_field->default . "'";
        }
        
        if (isset($_field->notnull))
        {
            if ($_field->notnull)
            {
                $definition .= ' NOT NULL ';
            }
        }
        else
        {
         //   $definition .= ' default NULL ';
        }
        
        return $definition;
    }
    
    private function _getMysqlIndexDeclarations($_key)
    {
        $definition = '';
        if (isset($_key->primary))
        {
            if ($_key->primary)
            {
                $definition = 'PRIMARY KEY';
            }
        } 
        else if (isset($_key->unique))
        {
            if ($_key->unique)
            {
                $definition = 'UNIQUE KEY';
            }
        }
        else if (isset($_key->foreign))
        {
            if ($_key->foreign)
            {
                $definition = 'FOREIGN KEY';
            }
        }
        {
            $definition = 'KEY';
        }
        return $definition;
    }
    
}