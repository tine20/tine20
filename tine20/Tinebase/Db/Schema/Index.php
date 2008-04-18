<?php
class Tinebase_Db_Schema_Index
{
	public $name;
	public $primary;
	public $field;
	public $foreign;
	public $reference;
	public $unique;
	public $mul;
	
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
	public function __construct($_index)
	{
		if (!$_index instanceof SimpleXMLElement)
		{
			$this->name = $this->field['name'] = $_index->name;

			
			
		}
		else
		{
		//	echo "y";
			foreach ($_index as $key => $val)
			{
				if ($key != 'field')
				{
					$this->$key = (string) $val;
				}
				else
				{
		//			echo "<span style=color:#ff0000>";
		//			print_r($val);
		//			echo "</span>||";
					$this->field = get_object_vars($val);
					$buffer[] = get_object_vars($val);
				}
			}
		//	if (sizeOf($buffer) > 1)
		//	{
		//		$this->field = $buffer;
		//		unset($buffer);
		//<	}
		}
		($_index->mul === 'true')? $this->mul = 'true': $this->mul = 'false';
		($_index->unique == 'true')? $this->unique = 'true': $this->unique = 'false';
		if ($_index->primary == 'true')
		{
			$this->primary = 'true';
		//	$this->unique = 'true';
		}
	}

	public function setName($_name)
	{
		if (SQL_TABLE_PREFIX == substr($_name, 0, strlen( SQL_TABLE_PREFIX )))
		{
			$this->name = substr($_name,  strlen( SQL_TABLE_PREFIX ));
		}
		else 
		{
			$this->name == $_name;
		}
	}

	public function setForeignKey($_foreign)
	{
		$this->foreign = 'true';
		$this->reference['table'] = substr($_foreign['REFERENCED_TABLE_NAME'], strlen( SQL_TABLE_PREFIX));
		$this->reference['field'] = $_foreign['REFERENCED_COLUMN_NAME'];
	}
	*/
}
