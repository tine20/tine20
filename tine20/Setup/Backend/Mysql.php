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
 
class SchemaTable
{
	public $name;
	public $version;
	public $declaration;
//	public $engine;
	
	public static function __set($prop, $value)
    {
		if (isset(self::$prop))
        {
			self::$prop = $value;
		}	
		else
		{
			throw new Exception('Undefined property [' . $prop . ']',1);
		}
	}
   
 
	
	
	
	public function __construct($_tableInfo = array('TABLE_NAME'=> NULL, 'TABLE_COMMENT' => NULL ))
	{
		$this->name = substr($_tableInfo['TABLE_NAME'], strlen( SQL_TABLE_PREFIX ));
		//$this->engine = $_tableInfo['ENGINE'];
		
		$version = explode(';', $_tableInfo['TABLE_COMMENT']);
		$this->version = substr($version[0],9);
	}
 
	
	
	public function setDeclarationField($_declaration)
	{
		var_dump(get_object_vars($_declaration->value));
		$this->declaration['field'][] = new SchemaField($_declaration);
	}
	
	public function setDeclarationIndex($_field)
	{
		$this->declaration['index'][] = new SchemaIndex($_field);
	}

	public function setIndex($_definition)
	{
		foreach ($this->declaration['index'] as $index)
		{
			if ($index->field->name == $_definition['COLUMN_NAME'])
			{
				$index->setName($_definition['CONSTRAINT_NAME']);
			}
		}
	}
	
	public function setForeign($_definition)
	{
		foreach ($this->declaration['index'] as $index)
		{
			if ($index->field->name == substr($_definition['CONSTRAINT_NAME'], strlen( SQL_TABLE_PREFIX )))
			{
				$index->setForeignKey($_definition);
			}
		}
	}
	
	
	
} 

class SchemaField
{
	public $name;
	public $type;
	public $autoincrement;
	public $unsigned;
	public $length;
	public $notnull;
	public $values;
	public $comment;
	public $mul; 
	public $primary;
	public $unique;
	
	public function __construct($_declaration = NULL)
	{
	
	
		if (!$_declaration instanceof SimpleXMLElement)
		{	
			$this->name = $_declaration['COLUMN_NAME'];
			$type = '';
			$length= '';
			switch ($_declaration['DATA_TYPE'])
			{
				case('int'):
				{
					$type = 'integer';
					$length = $_declaration['NUMERIC_PRECISION'];
					break;
				}
				case('enum'):
				{
					$type = $_declaration['DATA_TYPE'];
					$this->values = explode(',', str_replace("'", '', substr($_declaration['COLUMN_TYPE'], 5, (strlen($_declaration['COLUMN_TYPE']) - 6))));
					break;
				}
				case('varchar'):
				{
					$length = $_declaration['CHARACTER_MAXIMUM_LENGTH'];
					$type = 'text';
				}
				
				
				default:
				{
					$length = $_declaration['CHARACTER_MAXIMUM_LENGTH'];
					$type = $_declaration['DATA_TYPE'];
				}
			}
			
			if ($_declaration['EXTRA'] == 'auto_increment')
			{
				$this->autoincrement = 'true';
			}
			
			if (preg_match('/unsigned/', $_declaration['COLUMN_TYPE']))
			{
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
		else
		{
		
		//	var_dump($_declaration->value);
			
			$this->name = (string) $_declaration->name;
			$this->type = (string)$_declaration->type;
			
			echo 	get_object_vars ($_declaration->value);
			if (0)
			{
			$this->values = $_declaration->value;
			}
			
			$this->primary = (string)$_declaration->primary;
			$this->autoincrement =(string) $_declaration->autoincrement;
			$this->notnull = (string)$_declaration->notnull;
			$this->length = (string)$_declaration->length;
			$this->comment = (string)$_declaration->comment;
			$this->unique = (string)$_declaration->unique;
			$this->unsigned = (string)$_declaration->unsigned;
			$this->mul = (string)$_declaration->mul;
			
				
			
			//foreach ($_declaration as $key => $val)
			//{
				//	$this->$key = (string) $val;
			//}
			
//			var_dump($_declaration);
		}
//		var_dump($this);
	}
}

class SchemaIndex
{
	public $name;
	public $primary;
	public $field;
	public $foreign;
	public $reference;
	public $unique;
	public $mul;
	
	public function __construct($_field)
	{
		$this->name = $this->field->name = $_field->name;
		
		if ($_field->primary === 'true')
		{
			$this->primary = 'true';
			$this->unique = 'true';			
		}
		else 
		{
			($_field->unique === 'true')? $this->unique = 'true': $this->unique = 'false'; 
			
			($_field->mul === 'true')? $this->mul = 'true': $this->mul = 'false'; 
		}
	}
	
	public function setName($_name)
	{
		$this->name = substr($_name,  strlen( SQL_TABLE_PREFIX ));
	}
	
	public function setForeignKey($_foreign)
	{
		$this->foreign = 'true';
		$this->reference['table'] = substr($_foreign['REFERENCED_TABLE_NAME'], strlen( SQL_TABLE_PREFIX));
		$this->reference['field'] = $_foreign['REFERENCED_COLUMN_NAME'];
		
	}		
	
	
	
}
 
class Setup_Backend_Mysql
{

	public function tableCheck($_dbname, $_tableName, $_table)
	{
		echo "<pre>";
//		print_r($_table);
		echo "</pre>";
		
		// step 1: get existing schema
		$existent = $this->getExistingSchema($_dbname, $_tableName);
	
	
		// step 2: find differnences and log variations
		$Differences = $this->compare($existent, $_table);
		
		
		// step 3: return true or array
		
		
		
		echo "<pre>";
		//print_r($existent);
		echo "</pre>";
		
		

		return false;
	}
	
	public function alterTable($_dbname, $_tableName, $_table)
	{
	
		return true;
	}
	
	public function getExistingSchema($_dbname, $_tableName)
	{
		// Get common table information
	    $select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.tables')
          ->where('TABLE_NAME = ?', $_tableName);
          
        $stmt = $select->query();
        $tableInfo = $stmt->fetchAll();
		
		$existingTable = new SchemaTable($tableInfo[0]);
        
	   // get field informations
		$select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.COLUMNS')
          ->where('TABLE_NAME = ?', $_tableName);
          
        $stmt = $select->query();
        $tableColumns = $stmt->fetchAll();
		
		foreach($tableColumns as $tableColumn)
		{
			$existingTable->setDeclarationField($tableColumn);
		}

		foreach ($existingTable->declaration['field'] as $field)
		{
			if ($field->primary === 'true' || $field->unique === 'true')
			{
				$existingTable->setDeclarationIndex($field);
			}
		}
		
		// get foreign keys
		$select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.KEY_COLUMN_USAGE')
          ->where('TABLE_NAME = ?', $_tableName);
          
        $stmt = $select->query();
        $keyUsage = $stmt->fetchAll();
       
		foreach ($keyUsage as $keyUse)
		{
//			var_dump($keyUse);
			$existingTable->setIndex($keyUse);
			if($keyUse['REFERENCED_TABLE_NAME'] != NULL)
			{
				$existingTable->setForeign($keyUse);
			}
		}
		
        
		
		
		//var_dump($tableInfo);
		//var_dump($keyUsage);
		//var_dump($tableColumns);
		
		//var_dump($result);
		return $existingTable;
	}
	
	
	
	public function compare($_existent, $_future)
	{
		
		
		$future = $this->toSchema($_future);
		echo "<pre><hr>";
		
		print_r($_existent);
		print_r($future);
		
		echo "</pre><hr>";
//		var_dump($_future);
		
		
		
		$similar = true;

		return $similar; 
	}
	
	
	
	
	public function toSchema($_table)
	{
		$schema = new SchemaTable();
		
		
		$schema->name = (string) $_table->name;
		$schema->version = (string) $_table->version;
		
		foreach($_table->declaration->field as $field)
		{	
			$field['TABLE_NAME'] = (string) $_table->name;
			$field['TABLE_COMMENT'] = (string) $_table->comment;
			
			var_dump($field);
			$schema->setDeclarationField($field);
		}
		
		return $schema; 
	}
	
	
	
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
            $statement .= "\n ENGINE=InnoDB DEFAULT CHARSET=utf8 ";
        }
       
		$statement .= " COMMENT='VERSION: " .  $_table->version  ;
        if (isset($_table->comment))
        {
          $statement .= "; " . $_table->comment . "';";
        }    
		else
		{
			$statement .= "';";
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
                        throw new Exception('unsupported special type ' . strtolower($field->value['special']));
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
