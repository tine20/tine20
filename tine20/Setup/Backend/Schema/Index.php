<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Index.php 1703 2008-04-03 18:16:32Z lkneschke $
 */


class Setup_Backend_Schema_Index
{
	public $name;
	public $primary;
	public $field = array();
	public $foreign;
	public $referencetable;
	public $referencefield;
	public $referenceOnDelete;
	public $referenceOnUpdate;
	public $unique;
	public $mul;
	

	
	public function __construct($_declaration, $type = 'XML')
	{
		switch ($type) {
			case('XML'): 
				$this->_setFromXML($_declaration);
				break;
			case('MySQL'):
				$this->_setFromMySQL($_declaration);
				break;
			default:	
		}
	}

	
	protected function _setFromXML(SimpleXMLElement $_declaration)
	{
		
		foreach ($_declaration as $key => $val) {
		
			if ($key != 'field' && $key != 'reference') {
				$this->$key = (string) $val;
				
			} else if ($key == 'field') {
				if ($val instanceof SimpleXMLElement) {
					$this->field[] = (string) $val->name;
				} else {
					$this->field = (string) $val;
				}
				
			} else if ($key == 'reference') {
				$this->referenceTable = $val->table;
				$this->referenceField = $val->field;
				$this->referenceOnUpdate = $val->onupdate;
				$this->referenceOnDelete= $val->ondelete;
				$this->field = $this->field[0];
			}
		}
	}
	
	protected function _addField($_field) {
	//	var_dump($_field);
	}
	
	protected function _setFromMySQL(stdClass $_declaration)
	{
		$this->name = $this->field['name'] = $_index->name;
	}
	
	public function setName($_name)
	{
		if (SQL_TABLE_PREFIX == substr($_name, 0, strlen( SQL_TABLE_PREFIX ))) {
			$this->name = substr($_name,  strlen( SQL_TABLE_PREFIX ));
		} else {
			$this->name == $_name;
		}
	}

	public function setForeignKey($_foreign)
	{
		$this->foreign = 'true';
		$this->reference['table'] = substr($_foreign['REFERENCED_TABLE_NAME'], strlen( SQL_TABLE_PREFIX));
		$this->reference['field'] = $_foreign['REFERENCED_COLUMN_NAME'];
	}
	
	public function addIndex($_definition)
    {
        foreach ($this->declaration['index'] as $index) {
            if ($index->field['name'] == $_definition['COLUMN_NAME']) {
                if ($_definition['CONSTRAINT_NAME'] == 'PRIMARY') {
                    $index->setName($_definition['COLUMN_NAME']);
                } else {
                    $index->setName($_definition['CONSTRAINT_NAME']);
                }
            }
        }
    }
	
    public function setIndex($_definition)
	{
		foreach ($this->declaration['index'] as $index) {
			if ($index->field['name'] == $_definition['COLUMN_NAME']) {
				if ($_definition['CONSTRAINT_NAME'] == 'PRIMARY') {
					$index->setName($_definition['COLUMN_NAME']);
				} else {
					$index->setName($_definition['CONSTRAINT_NAME']);
				}
			}
		}
	}

	public function setForeign($_definition)
	{
		foreach ($this->declaration['index'] as $index) {
            //echo "<h1>"  . substr($_definition['CONSTRAINT_NAME'], strlen( SQL_TABLE_PREFIX )) . "/" .$index->field->name.  "</h1>";
            
            //if ($index->field->name == substr($_definition['CONSTRAINT_NAME'], strlen( SQL_TABLE_PREFIX )))
			//{
				$index->setForeignKey($_definition);
			//}
		}
	}
}
