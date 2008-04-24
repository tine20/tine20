<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Field.php 1703 2008-04-03 18:16:32Z lkneschke $
 */


class Setup_Backend_Schema_Field
{
    /**
     * the name of the column / field
     *
     * @var string
     */
	public $name;
	
	/**
     * the data type (int/varchar/etc)
     *
     * @var string
     */	
	public $type;
	
	/**
     * mysql- feature
     *
     * @var boolean
     */
	public $autoincrement;

	/**
     * only positive values are allowed
     *
     * @var boolean
     */
	public $unsigned;

	/**
     * the data precision
     *
     * @var int
     */
	public $length;

	/**
     * if true, there have to be some values
     *
     * @var string
     */
	public $notnull;

	/**
     * value / decimal definition / enum values / default values
     *
     * @var mixed
     */
	public $value;

	/**
     * field/ column comment
     *
     * @var string
     */
	public $comment;

	/**
     * is index (mysql specific setting)
     *
     * @var boolean
     */
	public $mul;

	/**
     * is primary key
     *
     * @var boolean
     */
	public $primary;

	/**
     * value has to be unique
     *
     * @var boolean
     */
	public $unique;
	

	

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
		// some field doesn't need special dealing 
		foreach ($_declaration as $key => $val) {
				$this->$key = (string) $val;
		}

		if ($this->autoincrement) {
			$this->notnull = 'true';
		}
		
		if (!isset ($this->notnull)) {
			$this->notnull = 'false';
		}

		
		switch ($_declaration->type) {
			case('text'):
				$this->type = 'varchar';
				if (!isset($this->length)) {
					$this->type = 'text';
					$this->length = 65535;
				}
				break;
			
			case ('integer'):
				if (!isset($this->length)) {
					$this->length = 11;
				}
				break;
			
			case('tinyint'):
				$this->type = 'integer';
				$this->length = 4;
				break;
			
			case ('clob'):
				$this->type = 'text';
				$this->length = 65535;
				break;
			
			case ('blob'):
				$this->type = 'longblob';
				$this->length = 65535;
				break;
			
			case ('enum'):
			   if (isset($_declaration->value[0])) {
					$i = 0;
					$array = array();
					while (isset($_declaration->value[$i])) {
						$array[] = (string) $_declaration->value[$i];
						$i++;
					}
					$this->value = $array;
				}
				break;

			case ('datetime'):
			   $this->type = 'datetime';
				break;
	
			case ('double'):
				$this->type = 'double';
				break;
			
			case ('float'):
				$this->type = 'float';
				break;
			
			case ('boolean'):
				$this->type =  'tinyint';
				if ($this->default == 'false') {
					$this->default = 0;
				} else {
					$this->default = 1;
				}
				break;
			
			case ('decimal'):
			  $this->type =  "decimal (" . $this->value . ")" ;
			  break;
		
			default :
				$this->type = 'undefined';
		}

		$this->mul = 'false';
		$this->primary = 'false';
		$this->unique = 'false';
				
	}

	
	protected function _setFromMySQL(stdClass $_declaration)
	{	
		$this->name = $_declaration['COLUMN_NAME'];
		$type = '';
		$length= '';
		
		switch ($_declaration['DATA_TYPE']) {
			case('int'):
				$type = 'integer';
				$length = $_declaration['NUMERIC_PRECISION'] + 1;
				break;
		
			case('tinyint'):
				$type = 'integer';
				$length = $_declaration['NUMERIC_PRECISION'] + 1;
				break;
			
			case('enum'):
				$type = $_declaration['DATA_TYPE'];
				$this->value = explode(',', str_replace("'", '', substr($_declaration['COLUMN_TYPE'], 5, (strlen($_declaration['COLUMN_TYPE']) - 6))));
				break;
			
			case('varchar'):
				$length = $_declaration['CHARACTER_MAXIMUM_LENGTH'];
				$type = 'text';
			
			default:
				$length = $_declaration['CHARACTER_MAXIMUM_LENGTH'];
				$type = $_declaration['DATA_TYPE'];
		}

		if ($_declaration['EXTRA'] == 'auto_increment') {
			$this->autoincrement = 'true';
		}

		if (preg_match('/unsigned/', $_declaration['COLUMN_TYPE'])) {
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
	

	
	public function fixFieldKey($_indices)
	{
		foreach($_indices as $index) {
			if($this->name == $index->name) {
				if ($index->primary == 'true') {
					$this->primary = 'true';
				} elseif ($index->unique == 'true') {
					$this->unique = 'true';
				} else {
					$this->mul = 'true';
				}
			}
		}
	}
}