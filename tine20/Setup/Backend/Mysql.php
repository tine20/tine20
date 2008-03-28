<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
        $statementSnippets = array();

        foreach ($_table->declaration->field as $field) 
        {
            if(isset($field->name)) 
            {
               $statementSnippets[] = $this->_getMysqlDeclarations($field);
            }
        }
        
        foreach ($_table->declaration->index as $key) 
        {
            if (!$key->foreign)
            {
                $statementSnippets[] = $this->_getMysqlIndexDeclarations($key);
            }
            else 
            {
                $statementSnippets[] = $this->_getMysqlForeignKeyDeclarations($key);
            }
        }
        
        $statement .= implode(",\n", $statementSnippets) . ")";

        if (isset($_table->engine))
        {
            $statement .= "\n ENGINE=" . $_table->engine . " DEFAULT CHARSET=" . $_table->charset;
        }
        else
        {
            $statement .= "\n ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        }
        
        if (isset($_table->comment))
        {
            if ($_table->comment)
            {
                $statement .= " COMMENT '" .  $_table->comment . "';";
            }
        }
        
        echo "<pre>$statement</pre>";
        try 
        {
            Zend_Registry::get('dbAdapter')->query($statement);
        }
        catch (Zend_Db_Exception $e) 
        {
            var_dump($e);
            exit;
        }
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
        
    public function execInsertStatement($_record)
    {
        $table = new Tinebase_Db_Table(array(
           'name' => SQL_TABLE_PREFIX . $_record->table->name
        ));
        
        foreach ($_record->field as $field) {
            if(isset($field->value['special'])) {
                switch(strtolower($field->value['special'])) {
                    case 'now':
                    {
                        $value = Zend_Date::now()->getIso();
                        break;
                    }   
                    case 'account_id':
                    {   
                        break;
                    }    
                    case 'application_id':
                    { 
                        $application = Tinebase_Application::getInstance()->getApplicationByName($field->value);
                        
                        $value = $application->id;

                        break;
                    }    
                    default:
                    {
                        throw new Exception('unsuported special type ' . strtolower($field->value['special']));
                        break;
                    }    
                }
            } else {
                $value = $field->value;
            }

            $data[(string)$field->name] = $value;
        }

        $table->insert($data);
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
            case ('float'):
            {
                $definition .= ' float ';
                break;
            }
            case ('boolean'):
            {
                $definition .= ' tinyint(1) ';
                if ($_field->default == 'false')
                {
                    $_field->default = 0;
                }
                else
                {
                    $_field->default = 1;
                }
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
        
        if (isset($_field->notnull) && $_field->notnull == 'true') {
                $definition .= ' NOT NULL ';
        } else {
         //   $definition .= ' default NULL ';
        }
        
        if (isset($_field->comment))
        {
            if ($_field->comment)
            {
                $definition .= "COMMENT '" .  $_field->comment . "'";
            }
        }
        
        return $definition;
    }
    
    /**
     * create the right mysql-statement-snippet for keys
     *
     * @param object $_key the xml index definition
     * @return string
     */
    private function _getMysqlIndexDeclarations($_key)
    {
        $snippet = '';
        $keys = array();
        
        $definition = ' KEY';
        if (!empty($_key->primary)) {
            $definition = ' PRIMARY KEY';
        } else if (!empty($_key->unique)) {
            $definition = ' UNIQUE KEY';
        }
       
        $snippet .= $definition . " `" . $_key->name . "`" ;
        
        foreach ($_key->field as $keyfield) {
            $key    = '`' . (string)$keyfield->name . '`';
            if(!empty($keyfield->length)) {
                $key .= ' (' . $keyfield->length . ')';
            }
            $keys[] = $key;
        }
        
        if(empty($keys)) {
            throw new Exception('now keys for index found');
        }
                
        $snippet .= ' (' . implode(",", $keys) . ') ';            
        
        return $snippet;
    }
    
    /**
     *  create the right mysql-statement-snippet for foreign keys
     *
     * @param object $_key the xml index definition
     * @return string
     */
     
    private function _getMysqlForeignKeyDeclarations($_key)
    {
        $snippet = '';
        $snippet = 'CONSTRAINT `' . SQL_TABLE_PREFIX . $_key->name . '` FOREIGN KEY';
        
        $snippet .= '(`' .$_key->field->name . "`) REFERENCES `" . SQL_TABLE_PREFIX
                    . $_key->reference->table . "` (`" . $_key->reference->field . "`) ";
        
        if(!empty($_key->reference->ondelete)) {
            $snippet .= "ON DELETE " . strtoupper($_key->reference->ondelete);
        }
        if(!empty($_key->reference->onupdate)) {
            $snippet .= "ON UPDATE " . strtoupper($_key->reference->onupdate);
        }
        
        return $snippet;
    }
                
}
