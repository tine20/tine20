<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Tables.php 1063 2008-03-14 16:28:12Z lkneschke $
 *
 */

/*
* checks local PHP installation for needed configuration details defined in essentials.xml
*/


/*
* Output factory
*/

interface TableFactory
{
    public function createTable();
    public function createRow();
    public function createHeader();
    public function createCell($content, $color = NULL);
}

/*
* abstract output classes
*/

abstract class Table
{
    protected $header = NULL;
    protected $rows = array();

    public function setHeader(Header $header)
    {
        $this->header = $header;
    }

    public function addRow(Row $row)
    {
        $this->rows[] = $row;
    }

    abstract public function display();
}

abstract class Row
{
    protected $cells = array();

    public function addCell(Cell $cell)
    {
        $this->cells[] = $cell;
    }

    abstract public function display();
}

abstract class Header extends Row {}


abstract class Cell
{
    protected $content = NULL;
    protected $color = NULL;

    public function __construct($content, $color = NULL)
    {
        $this->content = $content;
        $this->color = $color;
    }

    abstract public function display();
}

/*
Text Output
*/

class TextCell extends Cell
{
    public function display()
    {
        echo "|" . str_pad($this->content, 20) ;
    }

}

class TextRow extends Row
{
    public function display()
    {
        echo "\n";
        foreach ($this->cells as $cell)
        {
            $cell->display();
        }

        echo "|";
        echo "\n+" . str_repeat("-", (count($this->cells) * 21) -1) . "+";
    }
}

class TextHeader extends Header
{
    public function display()
    {
        echo "+" . str_repeat("-", (count($this->cells) * 21) -1) . "+\n";

        foreach ($this->cells as $cell)
        {
            $cell->display();
        }

        echo "|";
        echo "\n+" . str_repeat("-", (count($this->cells) * 21) -1) . "+";
    }
}

class TextTable extends Table
{
    public function display()
    {
        $this->header->display();

        foreach ($this->rows as $row)
        {
            $row->display();
        }
        echo "\n";

    }

}

class TextTableFactory implements TableFactory
{
    public function createTable()
    {
        $table = new TextTable();
        return $table;
    }

    public function createRow()
    {
        $row = new TextRow();
        return $row;
    }

    public function createHeader()
    {
        $header = new TextHeader();
        return $header;
    }

    public function createCell($content, $color = NULL)
    {
        $cell = new TextCell($content);
        return $cell;
    }

}

/*
HTML Output
*/

class HTMLCell extends Cell
{
    public function display()
    {
        if ($this->color)
        {
            return "\n\t\t\t<td style=\"color:" . $this->color . "\">" . $this->content . "</td>";
        }
        else
        {
            return "\n\t\t\t<td>" . $this->content . "</td>";
        }
    }

}

class HTMLRow extends Row
{
    public function display()
    {
        $buffer =  "\n\t\t<tr>";

        foreach ($this->cells as $cell)
        {
            $buffer .= $cell->display();
        }
        return $buffer . "\n\t\t</tr>";
    }
}

class HTMLHeader extends Header
{
    public function display()
    {
        $buffer = "\n\t\t<tr style=\"font-weight: bold;\">";

        foreach ($this->cells as $cell)
        {
            $buffer .=$cell->display();
        }

        return $buffer .  "\n\t\t</tr>";
    }
}

class HTMLTable extends Table
{
    public function display()
    {
        $buffer = "\n\t<table border>";

        $this->header->display();

        foreach ($this->rows as $row)
        {
            $buffer .= $row->display();
        }

        return $buffer . "\n\t</table>";
    }

}

class HTMLTableFactory implements TableFactory
{
    public function createTable()
    {
        $table = new HtmlTable();
        return $table;
    }

    public function createRow()
    {
        $row = new HTMLRow();
        return $row;
    }

    public function createHeader()
    {
        $header = new HTMLHeader();
        return $header;
    }

    public function createCell($content, $color= NULL)
    {
        $cell = new HTMLCell($content, $color);
        return $cell;
    }

}

/*
* XML parsing
*/

class ExtensionList
{
    protected $tableFactory = NULL;

    public function __construct(TableFactory $tableFactory)
    {
        $this->tableFactory = $tableFactory;
    }

    public function showTable($data)
    {
        $table = $this->tableFactory->createTable();

        $header = $this->tableFactory->createHeader();
        $header->addCell($this->tableFactory->createCell('Item checked'));
        $header->addCell($this->tableFactory->createCell('Status'));

        $table->setHeader($header);

        foreach ($data as $line)
        {
            $row = $this->tableFactory->createRow();
            $table->addRow($row);

            foreach ($line as $field)
            {
                $cell = $this->tableFactory->createCell($field);

                if ($field == 'FAILURE')
                {
                    $color = "#ff0000";
                    $cell = $this->tableFactory->createCell($field, $color);
                }
                else if ($field == 'SUCCESS')
                {
                    $color = "#00ff00";
                    $cell = $this->tableFactory->createCell($field, $color);
                }


                $row->addCell($cell);
            }

        }

        return $table->display();
    }

}


class Setup_ExtCheck
{
    private $loadedExtensions = array();

    public $values = array();

    public $output = '';
    /*
    * * read configuration
    */
    private function _getConfiguration($_file)
    {
        if ($fileHandle = fopen($_file, 'r'))
        {
            $buffer = '';
            while (!feof($fileHandle))
            {
               $buffer .= fgets($fileHandle, 4096);
            }
            fclose($fileHandle);

            $values = array();
            $parser = xml_parser_create();
            xml_parse_into_struct($parser, $buffer, $values);
            xml_parser_free($parser);
        }
        return $values;
    }

    private function _check()
    {
        foreach ($this->values as $key => $value)
        {
            if ($value['tag'] == 'ENVIROMENT')
            {
                if ($value['attributes']['NAME'] == 'Zend')
                {
                    if (version_compare($value['attributes']['VERSION'], zend_version(), '<'))
                    {
                        $data[] = array($value['attributes']['NAME'], 'SUCCESS');
                    }
                    else
                    {
                        $data[] = array($value['attributes']['NAME'], 'FAILURE');
                    }
                }
                else if ($value['attributes']['NAME'] == 'PHP')
                {
                    if (version_compare($value['attributes']['VERSION'], phpversion(), '<'))
                    {
                        $data[] = array($value['attributes']['NAME'], 'SUCCESS');
                    }
                    else
                    {
                        $data[] = array($value['attributes']['NAME'], 'FAILURE');
                    }
                }
            }

            else if ($value['tag'] == 'EXTENSION')
            {
                foreach ($value as $extensionArray)
                {
                    if (is_array($extensionArray))
                    {
                        $succeeded = false;

                        if (in_array($extensionArray['NAME'], $this->loadedExtensions))
                        {
                            $passed[] = true;

                            if ($this->values[($key + 1)]['tag'] == 'INISET')
                            {
                                $iniSettings = ini_get_all($extensionArray['NAME']);
            //($iniSettings);
                                $i = 1;
                                while($values[($key + $i)]['tag'] == 'INISET')
                                {
                                    switch($values[($key + $i)]['attributes']['OPERATOR'])
                                    {
                                        case('<='):
                                        {
                                            if (!$iniSettings[$values[($key + $i)]['attributes']['NAME']][$values[($key + $i)]['attributes']['SCOPE']] <= $values[($key + $i)]['attributes']['VALUE'])
                                            {
                                                $passed[] = false;
                                            }
                                            break;
                                        }
                                        case('=='):
                                        {
                                            if (!$iniSettings[$values[($key + $i)]['attributes']['NAME']][$values[($key + $i)]['attributes']['SCOPE']] == $values[($key + $i)]['attributes']['VALUE'])
                                            {
                                                $passed[] = false;
                                            }
                                            break;
                                        }
                                        case('>='):
                                        {
                                            if (!$iniSettings[$values[($key + $i)]['attributes']['NAME']][$values[($key + $i)]['attributes']['SCOPE']] >= $values[($key + $i)]['attributes']['VALUE'])
                                            {
                                                $passed[] = false;
                                            }
                                            break;
                                        }
                                        default:
                                        {
                                            break;
                                        }
                                    }
                                    $i++;
                                }

                            }
                        if (!in_array(false, $passed))
                            {
                                $succeeded = true;
                            }
                            else
                            {
                                $succeeded = false;
                            }
                            unset($passed);
                            unset($iniSettings);
                        }

                        if($succeeded)
                        {
                            $data[] = array($extensionArray['NAME'], 'SUCCESS');
                        }
                        else
                        {
                            $data[] = array($extensionArray['NAME'], 'FAILURE');
                        }
                    }
                }
            }
        }
        return $data;
    }

    public function getOutput()
    {
        return $this->output = $this->list->showTable($this->_check());
    }

    public function __construct($_file = NULL)
    {
        if (isset($_SERVER['SHELL']) || isset($_SERVER['ProgramFiles']))  // Unix-Shell; Windows-Kommandozeile
        {
            $this->list = new ExtensionList(new TextTableFactory());
        }
        else
        {
            $this->list = new ExtensionList(new HTMLTableFactory());
        }

        /*
        * fetch local server info
        */
        $this->loadedExtensions = get_loaded_extensions();

        $this->values = $this->_getConfiguration($_file);
    }
}


