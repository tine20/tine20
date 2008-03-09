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
    protected $_prefix;
    
    public function __construct($_prefix)
    {
        $this->_prefix = $_prefix;
    }
    
    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function createTable($_table)
    {
        $statement = "CREATE TABLE IF NOT EXISTS `" . $this->_prefix . $_table['name'] . "` (\n";

        foreach ($_table->fields[0] as $field) {
            if($field['name'] != '') {
                $statement .= "`" . $field['name'] . "` " . $field['type'] . " " . $field['NULL'];
                if (isset($field['extra'])) {
                    $statement .= " " . $field['extra'];
                }
                $statement .=",\n";
            }
        }

        foreach ($_table->keys[0] as $key) {
            $statement .= " " . $key['type'] . " `" . $this->_prefix . $key['name'] . "` (" ;

            foreach ($key->keyfield as $keyfield) {
                $statement .= "`"  . (string)$keyfield . "`,";
            }
            	
            $statement = substr($statement, 0, (strlen($statement)-1)) . "),";
        }

        $statement = substr($statement, 0, (strlen($statement)-1)) ;
        $statement .= ")";

        $statement .= 	"\n ENGINE=" . $_table->engine . " DEFAULT CHARSET=" . $_table->charset;

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
}