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
	private $_config = '';
	
	public function __construct()
	{
		$this->_config = Zend_Registry::get('configFile');
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

        foreach ($_table->declaration->field as $field) {
            if(isset($field->name)) {
               $statementSnippets[] = $this->getMysqlDeclarations($field);
            }
        }

        foreach ($_table->declaration->index as $key) {
            if (!$key->foreign) {
                $statementSnippets[] = $this->getMysqlIndexDeclarations($key);
            } else {
                $statementSnippets[] = $this->getMysqlForeignKeyDeclarations($key);
            }
        }

        $statement .= implode(",\n", $statementSnippets) . ")";

        if (isset($_table->engine)) {
            $statement .= "\n ENGINE=" . $_table->engine . " DEFAULT CHARSET=" . $_table->charset;
        } else {
            $statement .= "\n ENGINE=InnoDB DEFAULT CHARSET=utf8 ";
        }

		$statement .= " COMMENT='VERSION: " .  $_table->version  ;
        if (isset($_table->comment)) {
          $statement .= "; " . $_table->comment . "';";
        } else {
			$statement .= "';";
		}

		echo "<pre>$statement</pre>";
		$this->execQueryVoid($statement);
		echo "<hr>";
	}
	
	    /**
     * checks if application is installed at all
     *
     * @param unknown_type $_application
     * @return unknown
     */
    public function applicationExists($_application)
    {
		 if($this->tableExists('applications')) {
            if($this->applicationVersionQuery($_application) != false) {
                return true;
            }
        }
        
        return false;
    }
	
    /**
     * check's if a given table exists
     *
     * @param string $_tableSchema
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableName)
    {
         $select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.tables')
          ->where('TABLE_SCHEMA = ?', $this->_config->database->dbname)
          ->where('TABLE_NAME = ?',  SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $table = $stmt->fetchObject();
		
        if($table === false) {
	        return false;
        }
		return true; 
    }
    
    /**
     * check's a given database table version 
     *
     * @param string $_tableName
     * @return boolean return string "version" if the table exists, otherwise false
     */
    
    public function tableVersionQuery($_tableName)
    {
        $select = Zend_Registry::get('dbAdapter')->select()
                ->from( SQL_TABLE_PREFIX . 'application_tables')
                ->where('name = ?',  SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        
        return $version[0]['version'];
    }
    
    /**
     * check's a given application version
     *
     * @param string $_application
     * @return boolean return string "version" if the table exists, otherwise false
     */
    public function applicationVersionQuery($_application)
    {    
        $select = Zend_Registry::get('dbAdapter')->select()
                ->from( SQL_TABLE_PREFIX . 'applications')
                ->where('name = ?', $_application);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        if(empty($version)) {
            return false;
        } else {
            return $version[0]['version'];
        }
    }
	
	
    public function addTable(Tinebase_Model_Application $_application, $_name, $_version)
    {
        $applicationTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'application_tables'));

        $applicationData = array(
            'application_id'    => $_application->id,
            'name'              =>  SQL_TABLE_PREFIX . $_name,
            'version'           => $_version
        );

        $applicationID = $applicationTable->insert($applicationData);

        return $applicationID;
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

	
	
	
	public function dropTable($_tableName)
	{
		$statement = "DROP TABLE `" . SQL_TABLE_PREFIX . $_tableName . "`;";
		$this->execQueryVoid($statement);
	}
	
	public function renameTable($_tableName, $_newName )
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` RENAME TO `" . SQL_TABLE_PREFIX . $_newName . "` ;";
		$this->execQueryVoid($statement);
	}
	
	public function addCol($_tableName, $_declaration , $_position = NULL)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD COLUMN " ;
		
		$statement .= $this->getMysqlDeclarations($_declaration);
		
		if($_position != NULL) {
			if ($_position == 0) {
				$statement .= ' FIRST ';
			} else {
				$before = $this->execQuery('DESCRIBE `' . SQL_TABLE_PREFIX . $_tableName . '` ');
				$statement .= ' AFTER `' . $before[$_position]['Field'] . '`';
			}
		}
		
		$this->execQueryVoid($statement);
	}
	
	public function alterCol($_tableName, $_declaration, $_oldName = NULL)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` CHANGE COLUMN " ;
		$oldName = $_oldName ;
		
		if ($_oldName == NULL) {
			$oldName = SQL_TABLE_PREFIX . $_declaration->name;
		}
		
		$statement .= " `" . $oldName .  "` " . $this->getMysqlDeclarations($_declaration) ;
		$this->execQueryVoid($statement);	
	}
	
	public function dropCol($_tableName, $_colName)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP COLUMN `" . $_colName . "`" ;
		$this->execQueryVoid($statement);	
	}


	
	public function addForeignKey($_tableName, $_declaration)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD " 
					. $this->getMysqlForeignKeyDeclarations($_declaration)  ;
		$this->execQueryVoid($statement);	
	}

	
	public function dropForeignKey($_tableName, $_name)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP FOREIGN KEY `" . $_name . "`" ;
		$this->execQueryVoid($statement);	
	}
	
	public function dropPrimaryKey($_tableName)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP PRIMARY KEY " ;
		$this->execQueryVoid($statement);	
	}
	
	public function addPrimaryKey($_tableName, $_declaration)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD "
					. $this->getMysqlIndexDeclarations($_declaration);
		$this->execQueryVoid($statement);	
	}
	
	public function addIndex($_tableName , $_declaration)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD "
					. $this->getMysqlIndexDeclarations($_declaration);
		$this->execQueryVoid($statement);	
	}
	
	
	public function dropIndex($_tableName, $_indexName)
	{
		$statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP INDEX `"  . $_indexName. "`" ;
		$this->execQueryVoid($statement);	
	}
	
	public function execQueryVoid($_statement)
	{
		try {
            $stmt = Zend_Registry::get('dbAdapter')->query($_statement);
        } catch (Zend_Db_Exception $e) {
            var_dump($e);
        }
	}
	
	public function execQuery($_statement)
	{
		try {
            $stmt = Zend_Registry::get('dbAdapter')->query($_statement);
        } catch (Zend_Db_Exception $e) {
            var_dump($e);
            return false;
        }
		return $stmt->fetchAll();
	}
	
	

    public function getMysqlDeclarations($_field)
    {
        $definition = '`' . $_field->name . '`';

        switch ($_field->type) {
            case('text'):
				if (isset($_field->length)) {
                    $definition .= ' varchar(' . $_field->length . ') ';
                } else {
                    $definition .= ' ' . $_field->type . ' ';
                }
                break;
            
            case ('integer'):
                if (isset($_field->length)) {
                    if ($_field->length > 19) {
                        $definition .= ' bigint(' . $_field->length . ') ';
					} else if($_field->length < 5) {
                        $definition .= ' tinyint(' . $_field->length . ') ';
                    } else {
                        $definition .= ' int(' . $_field->length . ') ';
                    }
                } else {
                    $definition .= ' int(11) ';
                }
                break;
            
            case ('clob'):
                $definition .= ' text ';
                break;
            
			case ('blob'):
                $definition .= ' longblob ';
                break;
            
			case ('enum'):
                foreach ($_field->value as $value) {
                    $values[] = $value;
                }
                $definition .= " enum('" . implode("','", $values) . "') ";
                break;
            
			case ('datetime'):
                $definition .= ' datetime ';
                break;
            
			case ('double'):
                $definition .= ' double ';
                break;
            
			case ('float'):
                $definition .= ' float ';
                break;
            
			case ('boolean'):
                $definition .= ' tinyint(1) ';
                if ($_field->default == 'false') {
                    $_field->default = 0;
                } else {
                    $_field->default = 1;
                }
                break;
            
			case ('decimal'):
                $definition .= " decimal (" . $_field->value . ")" ;
				break;
			}

        if (isset($_field->unsigned)) {
            $definition .= ' unsigned ';
        }

        if (isset($_field->autoincrement)) {
            $definition .= ' auto_increment';
        }

        if (isset($_field->default)) {
            $definition .= "default '" . $_field->default . "'";
        }

        if (isset($_field->notnull) && $_field->notnull == 'true') {
                $definition .= ' NOT NULL ';
        } else {
         //   $definition .= ' default NULL ';
        }

        if (isset($_field->comment)) {
            if ($_field->comment) {
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
    public function getMysqlIndexDeclarations($_key)
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

    public function getMysqlForeignKeyDeclarations($_key)
    {
        $snippet = '';
        $snippet = 'CONSTRAINT `' . SQL_TABLE_PREFIX .  $_key->name . '` FOREIGN KEY';

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
