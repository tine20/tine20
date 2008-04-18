<?php
class Tinebase_Db_Schema_Table
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
		//var_dump(get_object_vars($_declaration));
		$this->declaration['field'][] = new Setup_Backend_Mysql_SchemaField($_declaration);
	}

	public function setDeclarationIndex($_index)
	{
	//	var_dump($_index);
	//	echo "<hr>";
	//	foreach ($_index as $index) {
			$this->declaration['index'][] = new Setup_Backend_Mysql_SchemaIndex($_index);
	//	}
	}

	public function setIndex($_definition)
	{
//	var_dump($_definition);
	
		foreach ($this->declaration['index'] as $index)
		{
			if ($index->field['name'] == $_definition['COLUMN_NAME'])
			{
				if ($_definition['CONSTRAINT_NAME'] == 'PRIMARY')
				{
				//var_dump($this->declaration['index']);
				//echo "<hr>";
					$index->setName($_definition['COLUMN_NAME']);
				//	var_dump($this->declaration['index']);
				}
				else
				{
					$index->setName($_definition['CONSTRAINT_NAME']);
				}
			}
		}
	}

	public function setForeign($_definition)
	{
		foreach ($this->declaration['index'] as $index)
		{
//			echo "<h1>"  . substr($_definition['CONSTRAINT_NAME'], strlen( SQL_TABLE_PREFIX )) . "/" .$index->field->name.  "</h1>";
		
//			if ($index->field->name == substr($_definition['CONSTRAINT_NAME'], strlen( SQL_TABLE_PREFIX )))
			{
				$index->setForeignKey($_definition);
			}
		}
	}
	
	public function fixFieldKey()
	{
	
		foreach($this->declaration['index'] as $index)
		{
			foreach ($index->field as $fieldname)
			{
				foreach ($this->declaration['field'] as $field)
				{
				//var_dump($fieldname);
				
					if($fieldname == $field->name)
					{
						if ($index->primary == 'true')
						{
							$field->primary = 'true';
						//	$field->unique = 'true';
						}
						if ($index->unique == 'true')
						{
							$field->unique = 'true';
						}
						//if ($index->primary == 'true')
						//{
						//	$field->primary = 'true';
						//}
					}
				}
			}
		}
	}



}
