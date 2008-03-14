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
                
               $statement .= $this->_getMysqlDeclarations($field) . ",\n";
            }
        }

        foreach ($_table->declaration->index as $key) 
		{
			if (!$key->foreign)
			{
            	$statement .= $this->_getMysqlIndexDeclarations($key) . " `" . SQL_TABLE_PREFIX . $key->name . "` (" ;
				foreach ($key->field as $keyfield) {
					$statement .= "`"  . (string)$keyfield->name . "`,";
	            }
	            $statement = substr($statement, 0, (strlen($statement)-1)) . "),\n";
			}
			else 
			{
				$statement .= $this->_getMysqlIndexDeclarations($key);
			}
		}

        $statement = substr($statement, 0, (strlen($statement)-2)) ;
        $statement .= ")";

        if (isset($_table->engine))
        {
            $statement .= "\n ENGINE=" . $_table->engine . " DEFAULT CHARSET=" . $_table->charset;
        }
        else
        {
            $statement .= "\n ENGINE=InnoDB DEFAULT CHARSET=utf8";
        }
        
		if (isset($_table->comment))
		{
			if ($_table->comment)
			{
				$statement .= " COMMENT '" .  $_table->comment . "'";
			}
		}
		$statement .= ";";
		
       // echo "<pre>$statement</pre>";
		
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
    	
	public function execInsertStatement($_record)
	{
		$statement = '';
		$statement .= 'INSERT INTO `' . SQL_TABLE_PREFIX . $_record->table->name . '` (';

		foreach ($_record->field as $field)
		{
			$fields[] = '`' . $field->name . '`';
			if ($field->value == 'NOW')
			{
				$values[] = Zend_Registry::get('dbAdapter')->quote(Zend_Date::now()->getIso());
			} 
			else if ($field->value == 'ACCOUNT_ID')
			{
			//	if (isset(Zend_Registry::get('currentAccount')->accountId))
			//	{
			//		$values[] = "'" . Zend_Registry::get('currentAccount')->accountId . "'";
			//	}
			//	else
			//	{
					$values[] = "''";
			//	}
			}
			else
			{
				$values[] = "'" . $field->value . "'";
			}
			
		}
		
		$statement .= implode(',', $fields) . ") VALUES (" . implode(',', $values) . ");"; 
		
       // Zend_Registry::get('dbAdapter')->query($statement);
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
     * get the type of index to create
     *
     * @param object $_key the xml index definition
     * @return string
     */
    private function _getMysqlIndexDeclarations($_key)
    {
        $definition = ' KEY ';
        if (!empty($_key->primary))
        {
			$definition = 'PRIMARY KEY';
        } 
        else if (!empty($_key->unique))
        {
            $definition = 'UNIQUE KEY';
        }
        else if (!empty($_key->foreign))
        {
            $definition = 'FOREIGN KEY';
			
			$definition .= '(`' .$_key->field->name . "`) REFERENCES `" . SQL_TABLE_PREFIX
						. $_key->reference->table . "`(`" . $_key->reference->field . "`) "
						. $_key->reference->action . ', ';
        }
		

        return $definition;
    }

    
}
