<?php
class Setup_Backend_Schema_Table
{
    /**
     * the name of the table
     *
     * @var string
     */
	public $name;
    
    /**
     * the table comment
     *
     * @var string
     */
    public $comment;
	
	/**
	 * the table version
	 *
	 * @var int
	 */
	public $version;
	
	/**
	 * the table definition
	 *
	 * @var array
	 */
	public $declaration = array();

	public static function __set($prop, $value)
    {
		if (isset(self::$prop)) {
			self::$prop = $value;
		} else {
			throw new Exception('Undefined property [' . $prop . ']',1);
		}
	}

	/**
	 * the constructor
	 *
	 * @param string $_tableName
	 * @param int $_tableVersion
	 * @param string $_tableComment
	 */
	public function __construct($_tableName, $_tableVersion, $_tableComment = '')
	{
		$this->name = $_tableName;
		$this->version = $_tableVersion;
		$this->comment = $_tableComment;
	}
    
    /**
     * the constructor
     *
     * @param unknown_type $prop
     * @param unknown_type $value
     */
/*    public function __construct($_tableInfo = array('TABLE_NAME'=> NULL, 'TABLE_COMMENT' => NULL ))
    {
        $this->name = substr($_tableInfo['TABLE_NAME'], strlen( SQL_TABLE_PREFIX ));

        $version = explode(';', $_tableInfo['TABLE_COMMENT']);
        $this->version = substr($version[0],9);
    } */
	
	/**
	 * add one field to the table definition
	 *
	 * @param Setup_Backend_Schema_Field $_declaration
	 */
	public function addDeclarationField(Setup_Backend_Schema_Field $_declaration)
	{
		//var_dump(get_object_vars($_declaration));
		$this->declaration['field'][] = $_declaration;
	}
    
	public function setDeclarationField($_declaration)
    {
        //var_dump(get_object_vars($_declaration));
        //$this->declaration['field'][] = new Setup_Backend_Schema_Field($_declaration);
    }
    
    public function addDeclarationIndex(Setup_Backend_Mysql_SchemaIndex $_index)
    {
        $this->declaration['index'][] = new Setup_Backend_Mysql_SchemaIndex($_index);
    }
    
	public function setDeclarationIndex($_index)
	{
/*	//	var_dump($_index);
	//	echo "<hr>";
	//	foreach ($_index as $index) {
			$this->declaration['index'][] = new Setup_Backend_Mysql_SchemaIndex($_index);
	//	}*/
	}

	/**
	 * add one index to the table definition
	 *
	 * @param unknown_type $_definition
	 */
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
	
	public function fixFieldKey()
	{
		foreach($this->declaration['index'] as $index) {
			foreach ($index->field as $fieldname) {
				foreach ($this->declaration['field'] as $field) {
					if($fieldname == $field->name) {
						if ($index->primary == 'true') {
							$field->primary = 'true';
						} elseif ($index->unique == 'true') {
							$field->unique = 'true';
						}
					}
				}
			}
		}
	}
}
