<?php
class Tinebase_Db_Schema_Field
{
	public $name;
	public $type;
	public $autoincrement;
	public $unsigned;
	public $length;
	public $notnull;
	public $value;
	public $comment;
	//public $mul;
	//public $primary;
	//public $unique;
	
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

	
/*
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
					$length = $_declaration['NUMERIC_PRECISION'] + 1;
					break;
				}
				case('tinyint'):
				{
					$type = 'integer';
					$length = $_declaration['NUMERIC_PRECISION'] + 1;
					break;
				}				
				case('enum'):
				{
					$type = $_declaration['DATA_TYPE'];
					$this->value = explode(',', str_replace("'", '', substr($_declaration['COLUMN_TYPE'], 5, (strlen($_declaration['COLUMN_TYPE']) - 6))));
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
			// this is from the XML-setup 
			
//			$this->name = (string) $_declaration->name;
//			$this->type = (string) $_declaration->type;

			foreach ($_declaration as $key => $val)
			{
					$this->$key = (string) $val;
			}

			if ($this->autoincrement)
			{
				$this->notnull = 'true';
			}
			
			if (!isset ($this->notnull))
			{
				$this->notnull = 'false';
			}

			
	        switch ($this->type)
	        {
	            case('text'):
	            {
	            	$this->type = 'varchar';
	                break;
	            }

	            case ('integer'):
	            {
	                if (!isset($this->length))
	                {
						$this->length =  11;
	                }
	                break;
	            }
	            case('tinyint'):
	            {
	            	$this->type = 'integer';
					$this->length =  4;
	                break;
	            }
	            case ('clob'):
	            {
	                $this->type = 'text';
					$this->length =  65535;
	                break;
	            }
	            case ('blob'):
	            {
	                $this->type = 'longblob';
					$this->length =  65535;
	                break;
	            }
	            case ('enum'):
	            {
	               if (isset($_declaration->value[0]) )
					{
						$i = 0;
						$array = array();
						while (isset($_declaration->value[$i]))
						{
							$array[] = (string) $_declaration->value[$i];

							$i++;
						}
						$this->value = $array;
					}
	                break;
	            }
	            case ('datetime'):
	            {
	               $this->type = 'datetime';
	                break;
	            }
	            case ('double'):
	            {
	                $this->type = 'double';
	                break;
	            }
	            case ('float'):
	            {
	                $this->type = 'float';
	                break;
	            }
	            case ('boolean'):
	            {
	                $this->type =  'tinyint';
	                if ($this->default == 'false')
	                {
	                    $this->default = 0;
	                }
	                else
	                {
	                    $this->default = 1;
	                }
	                break;
	            }
	            case ('decimal'):
	            {
	              //   $this->type =  "decimal (" . $this->value . ")" ;
	              //   $f
	            }
				default :
				{
					$this->type = 'undefined';
				}
	        }

			$this->mul = 'false';
			$this->primary = 'false';
			$this->unique = 'false';

		}

	}
*/



}