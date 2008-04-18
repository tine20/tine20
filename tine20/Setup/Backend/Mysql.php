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
	public $DBMS = 'mysql';
    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function _createTable($_table)
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
		$this->_execQueryVoid($statement);
		echo "<hr>";
	}
	

	
	public function _dropTable($_tableName)
	{
		$statement = "DROP TABLE `" . $_tableName . "`;";
		
		if ($this->_execQuery($statement))
		{
			echo  "dropped table " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function _renameTable($_tableName, $_newName )
	{
		$statement = "ALTER TABLE `" . $_tableName . "` RENAME TO `" . $_newName . "` ;";
		
		if ($this->_execQuery($statement))
		{
			return true;
		}
		else
		{
			return false;
		}
	
	}
	
	
	public function _addCol($_tableName, $_declaration , $_position = NULL)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` ADD COLUMN " ;
		
		$statement .= $this->_getMysqlDeclarations($_declaration);
		
		if($_position != NULL)
		{
			if ($_position == 0)
			{
				$statement .= ' FIRST ';
			}
			else
			{
				$before = $this->_execQuery('DESCRIBE `' .  $_tableName . '` ');
				$statement .= ' AFTER `' . $before[$_position]['Field'] . '`';
			}
		}
		
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function _alterCol($_tableName, $_declaration, $_oldName = NULL)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` CHANGE COLUMN " ;
		$oldName = $_oldName ;
		
		if ($_oldName == NULL)
		{
			$oldName = $_declaration->name;
		}
		
		$statement .= " `" . $oldName .  "` " . $this->_getMysqlDeclarations($_declaration) ;
		
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}	
	}
	
	public function _dropCol($_tableName, $_colName)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` DROP COLUMN `" . $_colName . "`" ;
		
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}	
	}


	
	public function _addForeignKey($_tableName, $_declaration)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` ADD " 
					. $this->_getMysqlForeignKeyDeclarations($_declaration)  ;
		
		echo $statement;
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}
	}

	
	public function _dropForeignKey($_tableName, $_name)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` DROP FOREIGN KEY `" . $_name . "`" ;
		
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}	
	}
	
	public function _dropPrimaryKey($_tableName)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` DROP PRIMARY KEY " ;
		
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}	
	}
	
	public function _addPrimaryKey($_tableName)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` ADD "
					. $this->_getMysqlIndexDeclarations($_declaration)  ; ;
		
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}	
	
	}
	
	public function _addIndex($_tableName , $_name)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` ADD "
					. $this->_getMysqlForeignKeyDeclarations($_declaration)  ; ;
		
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}
	}
	
	
	public function _dropIndex($_tableName, $_indexName)
	{
		$statement = "ALTER TABLE `" . $_tableName . "` DROP INDEX `"  . $_indexName. "`" ;
		
		if ($this->_execQuery($statement))
		{
			echo  "modified " . $_tableName . "\n";
			return true;
		}
		else
		{
			return false;
		}	
	}
	
	public function _execQueryVoid($_statement)
	{
		try
        {
            $stmt = Zend_Registry::get('dbAdapter')->query($_statement);
        }
        catch (Zend_Db_Exception $e)
        {
            var_dump($e);
        }
	}
	
	public function _execQuery($_statement)
	{
		try
        {
            $stmt = Zend_Registry::get('dbAdapter')->query($_statement);
        }
        catch (Zend_Db_Exception $e)
        {
            var_dump($e);
            return false;
        }
		return $stmt->fetchAll();
	}
	
	

    public function _getMysqlDeclarations($_field)
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
    public function _getMysqlIndexDeclarations($_key)
    {
        $snippet = '';
        $keys = array();

        $definition = ' KEY';
        if (!empty($_key->primary)) {
            $definition = ' PRIMARY KEY';
        } else if (!empty($_key->unique)) {
            $definition = ' UNIQUE KEY';
        }

        //$snippet .= $definition . " `" . SQL_TABLE_PREFIX .  $_key->name . "`" ;
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

    public function _getMysqlForeignKeyDeclarations($_key)
    {
	
        $snippet = '';
        $snippet = 'CONSTRAINT `' . $_key->name . '` FOREIGN KEY';

        $snippet .= '(`' . $_key->field->name . "`) REFERENCES `" . SQL_TABLE_PREFIX
                    . $_key->reference->table . 
					"` (`" . $_key->reference->field . "`) ";

        if(!empty($_key->reference->ondelete)) {
            $snippet .= "ON DELETE " . strtoupper($_key->reference->ondelete);
        }
        if(!empty($_key->reference->onupdate)) {
            $snippet .= "ON UPDATE " . strtoupper($_key->reference->onupdate);
        }

        return $snippet;
    }

}
