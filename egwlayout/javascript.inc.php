<?php

function convertPHPArrayJSArray($name, array $array, $new=true)
{
	if (!is_array($array))
	{
		return '';
	}
	
	if ($new)
	{
		$jsCode = "$name = new Object();\n";
	}
	else
	{
		$jsCode = '';
	}

	foreach ($array as $index => $value)
	{
		if (is_array($value))
		{
			$jsCode .= $name."['".$index."'] = new Object();\n";
			$jsCode .= $this->convert_phparray_jsarray($name."['".$index."']", $value,false);
			continue;
		}

		switch(gettype($value))
		{
			case 'string':
				$value = "'".str_replace(array("\n","\r"),'\n',addslashes($value))."'";
				break;

			case 'boolean':
				if ($value)
				{
					$value = 'true';
				}
				else
				{
					$value = 'false';
				}
				break;

			default:
				$value = 'null';
		}
		
		$jsCode .= $name."['".$index."'] = ".$value.";\n";
	}

	return $jsCode;
}

?>
